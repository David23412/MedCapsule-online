import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/admin-course-sources.js', import.meta.url), 'utf8');
test('existing source names are suggested and can be submitted as a merge', async () => {
    const nodes = new Map();
    for (const id of ['courseSourceRenameForm', 'sourceRenameCourse', 'sourceRenameOriginal', 'sourceRenameNewName', 'sourceRenameSuggestions', 'sourceRenameSave', 'sourceRenameRetry', 'sourceRenameStatus', 'sourceRenameScope']) {
        nodes.set(id, {
            value: '', textContent: '', disabled: false, dataset: {baseUrl: '/admin/courses'}, listeners: {},
            selectedOptions: [{textContent: 'CNS'}],
            addEventListener(event, handler) {this.listeners[event] = handler;},
            replaceChildren(...children) {this.children = children;},
            add(child) {this.children.push(child);},
            reportValidity() {return true;},
        });
    }
    const requests = [];
    let merged = false;
    const context = vm.createContext({
        document: {getElementById: id => nodes.get(id), querySelector: () => ({content: 'csrf'})},
        Option: function (text, value) {this.text = text; this.value = value;},
        AbortController, setTimeout, clearTimeout, encodeURIComponent,
        fetch: async (url, options) => {
            requests.push({url, options});
            if (options.method === 'POST') {merged = true; return {ok: true, json: async () => ({message: 'Updated source for 2 questions.'})};}
            return {ok: true, json: async () => ({sources: merged
                ? [{name: 'MedCapsule', question_count: 5}]
                : [{name: 'Downloaded CNS', question_count: 2}, {name: 'MedCapsule', question_count: 3}]})};
        },
    });
    vm.runInContext(script, context);
    const course = nodes.get('sourceRenameCourse');
    const source = nodes.get('sourceRenameOriginal');
    const name = nodes.get('sourceRenameNewName');
    const save = nodes.get('sourceRenameSave');
    course.value = '1';
    await course.listeners.change();
    assert.equal(nodes.get('sourceRenameSuggestions').children[1].value, 'MedCapsule');
    source.value = 'Downloaded CNS';
    name.value = 'MEDCAPSULE';
    name.listeners.input();
    assert.equal(save.disabled, false);
    assert.equal(save.textContent, 'Combine sources');
    assert.match(nodes.get('sourceRenameScope').textContent, /2 questions into MedCapsule \(3 existing questions\)/);
    await nodes.get('courseSourceRenameForm').listeners.submit({preventDefault() {}});
    const request = requests.find(item => item.options.method === 'POST');
    assert.deepEqual(JSON.parse(request.options.body), {source: 'Downloaded CNS', new_name: 'MEDCAPSULE'});
    assert.equal(source.children.length, 2);
    assert.equal(source.children[1].value, 'MedCapsule');
    assert.equal(nodes.get('sourceRenameStatus').textContent, 'Updated source for 2 questions.');
});
