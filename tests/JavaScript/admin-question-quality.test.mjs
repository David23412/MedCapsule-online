import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../public/js/admin-question-quality.js', import.meta.url), 'utf8');
function harness() {
    class Element {
        constructor() { this.value = ''; this.children = []; this.handlers = {}; this.style = {}; this.classList = {add(){},remove(){}}; }
        addEventListener(event, handler) { this.handlers[event] = handler; }
        setAttribute() {}
        append(node) { this.children.push(node); }
        replaceChildren() { this.children = []; }
    }
    const elements = new Map(), requests = [];
    const element = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
    element('quality-issue').value = 'all';
    const context = vm.createContext({
        document: {body:new Element(), readyState:'complete', getElementById:element, createElement:() => new Element()},
        window:{MANAGE_ACCESS_CONFIG:{activeTab:'question-quality',csrfToken:'test',routes:{questionQuality:'/manage-access/question-quality'}},location:{href:'https://example.test/manage-access'}},
        URL, AbortController, setTimeout, clearTimeout,
        fetch(url, options) { return new Promise(resolve => requests.push({url,options,resolve})); }
    });
    vm.runInContext(source, context);
    return {element,requests};
}
const flush = () => new Promise(resolve => setImmediate(resolve));
function data(question = 'Question', id = 1) {return {items:[{id,question,correct_answer:'F',explanation:'Text',issues:['broken_answer']}],counts:{broken_answer:1,repeated_explanation:0},total:1,page:1,last_page:1};}
function complete(request, value) {request.resolve({ok:true,json:async () => value});}
test('quality list renders imported question content as text and ignores stale filter responses', async () => {
    const h = harness();
    h.element('quality-course').value = '7';
    h.element('quality-course').handlers.change();
    assert.equal(h.requests[0].options.signal.aborted, true);
    assert.equal(h.requests[1].url.searchParams.get('course_id'),'7');
    complete(h.requests[1],data('<img src=x onerror=evil()>',2)); await flush();
    complete(h.requests[0],data('Old question',1)); await flush();
    const card = h.element('quality-results').children[0];
    const heading = card.children.find(child => child.textContent === '<img src=x onerror=evil()>');
    assert.ok(heading);
    assert.equal(heading.innerHTML, undefined);
    assert.ok(!card.children.some(child => child.textContent === 'Old question'));
});
test('failed quality scans show a retry message and clear obsolete results', async () => {
    const h = harness();
    complete(h.requests[0],data()); await flush();
    h.element('quality-refresh').handlers.click();
    h.requests[1].resolve({ok:false,json:async () => ({message:'Please retry the scan.'})}); await flush();
    assert.equal(h.element('quality-results').children.length,0);
    assert.equal(h.element('quality-summary').textContent,'Please retry the scan.');
    assert.equal(h.element('quality-next').disabled,true);
});
