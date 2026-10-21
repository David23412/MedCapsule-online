import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source=readFileSync(new URL('../../public/js/admin-json-forms.js',import.meta.url),'utf8');
function harness(fetcher,{redirect=false}={}) {
    const error={hidden:true,textContent:'',className:'alert alert-danger'},button={disabled:false};
    const form={action:'/assignment',dataset:redirect?{successUrl:'/saved'}:{},
        querySelector:()=>error,querySelectorAll:()=>[button],addEventListener(name,handler){this[name]=handler;}};
    let calls=0,body,timeout,redirected;
    const context=vm.createContext({document:{querySelectorAll:()=>[form]},
        FormData:class extends Map{constructor(){super([['title','Preserved'],['user_extensions[1]',''],['user_extensions[2]','2026-11-01']]);}},
        AbortController,setTimeout:fn=>(timeout=fn,1),clearTimeout(){},
        window:{location:{assign:url=>redirected=url}},fetch:async(url,options)=>{calls++;body=options.body;return fetcher(options);}});
    vm.runInContext(source,context);
    return {submit:()=>form.submit({preventDefault(){}}),error,button,calls:()=>calls,body:()=>body,timeout:()=>timeout(),redirected:()=>redirected};
}
test('one submission preserves nonempty extensions and blocks duplicate saves',async()=>{
    let finish;const h=harness(()=>new Promise(resolve=>finish=resolve),{redirect:true});
    const pending=h.submit();await h.submit();assert.equal(h.calls(),1);assert.equal(h.button.disabled,true);
    assert.equal(h.body().has('user_extensions[1]'),false);assert.equal(h.body().get('user_extensions[2]'),'2026-11-01');
    finish({ok:true,json:async()=>({success:true})});await pending;assert.equal(h.redirected(),'/saved');
});
test('validation failure preserves inputs and allows retry',async()=>{
    const h=harness(async()=>({ok:false,json:async()=>({success:false,message:'Choose a valid deadline.'})}));
    await h.submit();assert.equal(h.button.disabled,false);assert.equal(h.error.hidden,false);
    assert.equal(h.error.textContent,'Choose a valid deadline.');assert.equal(h.body().get('title'),'Preserved');
    await h.submit();assert.equal(h.calls(),2);
});
test('timeout restores controls with an honest recovery message',async()=>{
    const h=harness(({signal})=>new Promise((resolve,reject)=>signal.addEventListener('abort',()=>reject(Object.assign(new Error(),{name:'AbortError'})))));
    const pending=h.submit();h.timeout();await pending;
    assert.equal(h.button.disabled,false);assert.match(h.error.textContent,/Check the saved status/);
});
test('successful review request displays acknowledgement and prevents duplicate sending',async()=>{
    const h=harness(async()=>({ok:true,json:async()=>({success:true,message:'Sent for review.'})}));
    await h.submit();assert.equal(h.error.textContent,'Sent for review.');assert.equal(h.error.className,'alert alert-success');
    await h.submit();assert.equal(h.calls(),1);
});
