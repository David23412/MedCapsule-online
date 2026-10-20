import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const whole=readFileSync(new URL('../../public/js/admin-manage-access.js',import.meta.url),'utf8');
const source=whole.slice(whole.indexOf('(function initSystemStats()'),whole.indexOf('// Payment History Tab Handler'));
function harness() {
    const requests=[], elements=new Map(), listeners={}, intervals=[];
    const document={hidden:false,addEventListener(name,fn){listeners[name]=fn;}};
    function $(key) {
        if (typeof key === 'object' && key !== null) return key;
        if (!elements.has(key)) elements.set(key,{value:'',styles:{},spinning:false,
            attr(){return 'token';}, text(value){this.value=value;return this;}, html(value){this.value=value;return this;},
            css(key,value){if(typeof key==='object')Object.assign(this.styles,key);else this.styles[key]=value;return this;},
            on(name,fn){this.click=fn;return this;}, find(){return this;},
            addClass(){this.spinning=true;return this;},removeClass(){this.spinning=false;return this;},
        }); return elements.get(key);
    }
    $.ajax=options=>{const r={options};requests.push(r);return r;};
    vm.runInNewContext(source,{$,document,setInterval(fn){intervals.push(fn);},});
    return {requests,elements,listeners,intervals,document};
}
test('system polling avoids overlapping requests, pauses while hidden, and resumes on visibility',()=>{
    const h=harness(); assert.equal(h.requests.length,1);
    h.intervals[0](); assert.equal(h.requests.length,1);
    h.requests[0].options.complete(); h.document.hidden=true; h.intervals[0](); assert.equal(h.requests.length,1);
    h.document.hidden=false;h.listeners.visibilitychange();assert.equal(h.requests.length,2);
    assert.equal(h.requests[1].options.timeout,12000);
});
test('recovered database health clears the prior warning state and refresh spinner',()=>{
    const h=harness(); const options=h.requests[0].options;
    options.success({success:true,db_online:false});
    assert.match(h.elements.get('#sysHealthBadge').value,/Issue Detected/);
    options.complete();h.intervals[0]();
    h.requests[1].options.success({success:true,db_online:true});
    assert.match(h.elements.get('#sysHealthBadge').value,/All Systems Operational/);
    assert.equal(h.elements.get('#sysHealthBadge').styles.color,'');
    h.elements.get('#refreshSystemStats').click.call(h.elements.get('#refreshSystemStats'));
    assert.equal(h.elements.get('#refreshSystemStats').spinning,true);
    h.requests[1].options.complete();assert.equal(h.elements.get('#refreshSystemStats').spinning,false);
});
