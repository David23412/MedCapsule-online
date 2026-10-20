import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../public/js/course-question-search.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));
function harness() {
    const elements = new Map(), timers = new Map(), requests = [];
    let timerId = 0;
    const element = () => ({ value: '', innerHTML: '', textContent: '', style: {}, disabled: false, checked: false,
        listeners: {}, dataset: {}, setAttribute(){}, focus(){}, classList: {add(){}, remove(){}},
        addEventListener(name, fn) {this.listeners[name] = fn;}, querySelectorAll() { return []; } });
    for (const id of ['courseQuestionSearchInput', 'searchQuestionsResults', 'searchSpinner', 'sq-edit-overlay',
        'sq-q-id', 'sq-q-text', 'sq-explanation', 'sq-status', 'sq-del-confirm', 'sq-save-btn', 'sq-del-btn',
        'sq-confirm-del', 'sq-q-type', 'sq-type-mcq', 'sq-type-written', 'sq-mcq-section', 'sq-written-section',
        'sq-correct-text', 'sq-alt-answers', 'sq-oa', 'sq-ob', 'sq-oc', 'sq-od', 'sq-oe', 'sq-ca']) elements.set(id, element());
    const addButton = element(); addButton.dataset.id = '1';
    const editButton = element(); editButton.dataset.id = '1';
    elements.get('searchQuestionsResults').querySelectorAll = selector => selector.includes('add-mistake') ? [addButton] : [editButton];
    const context = vm.createContext({
        console: {error(){}}, AbortController, document: {
            getElementById: id => elements.get(id) || null,
            querySelector: selector => selector.startsWith('meta') ? {getAttribute: () => 'token'} : null,
            querySelectorAll: selector => selector.includes('sq-correct') ? [elements.get('sq-ca')] : [], body: {style:{}},
        }, TopicsPageConfig: {courseId: 1, canEditQuestions: true}, alert(){},
        setTimeout(fn, ms) {const id = ++timerId; timers.set(id, {fn, ms}); return id;},
        clearTimeout(id) {timers.delete(id);},
        fetch(url, options) {return new Promise((resolve, reject) => requests.push({url, options,
            resolve: data => resolve({ok: true, json: async () => data}), reject}));},
    });
    context.window = context;
    vm.runInContext(source, context);
    return {elements, timers, requests, context, addButton, editButton,
        input(value) {const input = elements.get('courseQuestionSearchInput'); input.value = value; input.listeners.input.call(input);},
        tick(ms) {for (const [id, timer] of [...timers]) if (timer.ms === ms) {timers.delete(id); timer.fn();}},
    };
}
const question = {id: 1, question: 'Blood regulation', correct_answer: 'A: Kidney', raw_correct_answer: 'A',
    option_a: 'Kidney', question_type: 'multiple_choice', explanation: 'Explanation', topic_titles: []};
test('an older response cannot overwrite the latest query, even if cancellation is ignored', async () => {
    const h = harness();
    h.input('blood'); h.tick(350);
    h.input('kidney'); h.tick(350);
    assert.equal(h.requests[0].options.signal.aborted, true);
    h.requests[1].resolve({success:true, questions:[{...question, question:'Newest result'}]}); await settle();
    h.requests[0].resolve({success:true, questions:[{...question, question:'Stale result'}]}); await settle();
    assert.match(h.elements.get('searchQuestionsResults').innerHTML, /Newest result/);
    assert.doesNotMatch(h.elements.get('searchQuestionsResults').innerHTML, /Stale result/);
});
test('clearing input hides the spinner and prevents a late response from reappearing', async () => {
    const h = harness(); h.input('blood'); h.tick(350); h.input('');
    h.requests[0].resolve({success:true, questions:[question]}); await settle();
    assert.equal(h.elements.get('searchSpinner').style.display, 'none');
    assert.match(h.elements.get('searchQuestionsResults').innerHTML, /Start typing/);
});
test('search escapes query and result content and recovers after timeout', async () => {
    const h = harness(); h.input('<img>'); h.tick(350);
    h.requests[0].resolve({success:true, questions:[]}); await settle();
    assert.match(h.elements.get('searchQuestionsResults').innerHTML, /&lt;img&gt;/);
    h.input('blood'); h.tick(350);
    h.requests[1].resolve({success:true, questions:[{...question, question:'<svg onload=evil()>', topic_titles:['<img>']}]}); await settle();
    assert.doesNotMatch(h.elements.get('searchQuestionsResults').innerHTML, /<svg|<img>/);
    h.input('timeout'); h.tick(350); h.tick(12000);
    assert.equal(h.requests[2].options.signal.aborted, true);
    h.requests[2].reject(Object.assign(new Error('timeout'), {name:'AbortError'})); await settle();
    assert.equal(h.elements.get('searchSpinner').style.display, 'none');
    assert.match(h.elements.get('searchQuestionsResults').innerHTML, /Search failed/);
});
test('bookmark clicks are deduplicated and replace only the bookmark button, preserving editing', async () => {
    const h = harness(); h.input('blood'); h.tick(350);
    h.requests[0].resolve({success:true, questions:[question]}); await settle();
    h.addButton.listeners.click.call(h.addButton); h.addButton.listeners.click.call(h.addButton);
    assert.equal(h.requests.length, 2);
    h.requests[1].resolve({success:true, message:'Added'}); await settle();
    assert.match(h.addButton.outerHTML, /mistake-status-pill/);
    assert.equal(typeof h.editButton.listeners.click, 'function');
    h.context.openSearchQuestionEditModal('1');
    assert.equal(h.elements.get('sq-q-id').value, '1');
});
test('saving locks the editor, prevents duplicate writes, and uses the server response', async () => {
    const h = harness(); h.input('blood'); h.tick(350);
    h.requests[0].resolve({success:true, questions:[{...question, question_type:'written', raw_correct_answer:'Kidney'}]}); await settle();
    h.context.openSearchQuestionEditModal('1');
    const first = h.context.sqSaveEdit(); await h.context.sqSaveEdit(); h.context.sqCloseModal();
    assert.equal(h.requests.length, 2);
    h.requests[1].resolve({success:true, data:{...question, question:'Server normalized', question_type:'written', correct_answer:'Kidney'}});
    await first;
    h.tick(900); h.context.openSearchQuestionEditModal('1');
    assert.equal(h.elements.get('sq-q-text').value, 'Server normalized');
    assert.equal(h.elements.get('sq-confirm-del').disabled, false);
});
