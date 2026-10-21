import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source=readFileSync(new URL('../../public/js/admin-delete-tools.js',import.meta.url),'utf8');
function harness({bootstrap=true}={}) {
    const element=()=>({checked:false,disabled:true,textContent:'',hidden:false,listeners:{},dataset:{},
        addEventListener(name,fn){this.listeners[name]=fn;}});
    const ids=new Map();
    for(const id of ['delete-confirmation','confirm-permanent-delete','confirm-delete-button','select-all-questions','delete-selected',
        'delete-selection-count','bulk-question-delete','delete-confirmation-description','delete-controls-status'])ids.set(id,element());
    const selected=[element(),element()], triggers=[element(),element()];
    const row=element(); row.dataset.label='question #7';
    const forms=[ids.get('bulk-question-delete'),row];
    let shown=0, submissions=0;
    const submit=form=>{let prevented=false;form.listeners.submit({preventDefault(){prevented=true;}}); if(!prevented)submissions++;};
    forms.forEach(form=>form.requestSubmit=()=>submit(form));
    const context=vm.createContext({document:{getElementById:id=>ids.get(id)||null,
        querySelectorAll:selector=>selector==='.delete-question-selection'?selected:selector==='[data-delete-form]'?forms:triggers},
        bootstrap:bootstrap?{Modal:class {show(){shown++;}}}:undefined});
    context.window=context;vm.runInContext(source,context);
    return {ids,forms,selected,triggers,submit,shown:()=>shown,submissions:()=>submissions};
}
test('controls stay disabled when the confirmation dialog cannot load',()=>{
    const h=harness({bootstrap:false});
    assert.equal(h.triggers.every(button=>button.disabled),true);
    assert.match(h.ids.get('delete-controls-status').textContent,/Refresh/);
});
test('bulk selection counts and select-all indeterminate state stay consistent',()=>{
    const h=harness();
    assert.equal(h.ids.get('delete-selected').disabled,true);
    h.selected[0].checked=true;h.selected[0].listeners.change();
    assert.equal(h.ids.get('delete-selection-count').textContent,1);
    assert.equal(h.ids.get('select-all-questions').indeterminate,true);
    h.ids.get('select-all-questions').checked=true;h.ids.get('select-all-questions').listeners.change();
    assert.equal(h.selected.every(input=>input.checked),true);
    assert.equal(h.ids.get('delete-selection-count').textContent,2);
    assert.equal(h.ids.get('select-all-questions').indeterminate,false);
});
test('empty bulk submissions are blocked and single deletion requires explicit confirmation',()=>{
    const h=harness();h.submit(h.forms[0]);assert.equal(h.shown(),0);
    h.submit(h.forms[1]);assert.equal(h.shown(),1);assert.equal(h.submissions(),0);
    h.ids.get('confirm-delete-button').listeners.click();assert.equal(h.submissions(),0);
    h.ids.get('confirm-permanent-delete').checked=true;h.ids.get('confirm-permanent-delete').listeners.change();
    h.ids.get('confirm-delete-button').listeners.click();
    assert.equal(h.submissions(),1);
    h.ids.get('confirm-delete-button').listeners.click();h.submit(h.forms[1]);h.submit(h.forms[0]);
    assert.equal(h.submissions(),1);
});
test('cancelled confirmation cannot delete a different selection without a new acknowledgement',()=>{
    const h=harness();h.submit(h.forms[1]);h.ids.get('confirm-permanent-delete').checked=true;
    h.ids.get('delete-confirmation').listeners['hidden.bs.modal']();
    h.ids.get('confirm-delete-button').listeners.click();assert.equal(h.submissions(),0);
    h.selected[0].checked=true;h.submit(h.forms[0]);
    assert.equal(h.ids.get('confirm-permanent-delete').checked,false);
    assert.match(h.ids.get('delete-confirmation-description').textContent,/1 selected question/);
});
