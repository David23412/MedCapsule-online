import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source=readFileSync(new URL('../../public/js/topic-source-selection.js',import.meta.url),'utf8');
function harness(){
    const timers=new Map();let nextTimer=0;
    const context=vm.createContext({window:{},URLSearchParams,encodeURIComponent,AbortController,
        setTimeout(fn){timers.set(++nextTimer,fn);return nextTimer;},clearTimeout(id){timers.delete(id);}});
    vm.runInContext(source,context);
    const requests=[],events=[];
    const loader=context.window.TopicSourceSelection.createLoader({
        load(topic,mode,signal){return new Promise((resolve,reject)=>requests.push({topic,mode,signal,resolve,reject}));},
        onLoading(){events.push('loading');},onLoaded(data){events.push(data.topic);},onError(){events.push('error');}
    });
    return {loader,requests,events,timers,api:context.window.TopicSourceSelection};
}
const data=topic=>({topic,availableSources:['Sample'],progress:{}});
test('switching topics or modes aborts the old request and ignores its late result',async()=>{
    const h=harness();const first=h.loader.request(1,'quiz');const second=h.loader.request(2,'tutor');
    assert.equal(h.requests[0].signal.aborted,true);
    h.requests[1].resolve(data('new'));await second;
    h.requests[0].resolve(data('old'));await first;
    assert.deepEqual(h.events,['loading','loading','new']);assert.equal(h.timers.size,0);
});
test('closing the picker discards late responses and a failed request can be retried',async()=>{
    const h=harness();const first=h.loader.request(1,'quiz');h.loader.cancel();
    h.requests[0].resolve(data('closed'));await first;
    assert.deepEqual(h.events,['loading']);
    const failed=h.loader.request(1,'quiz');h.requests[1].reject(new Error('offline'));await failed;
    assert.equal(h.events.at(-1),'error');
    const retry=h.loader.request(1,'read');assert.equal(h.requests[2].mode,'quiz');
    h.requests[2].resolve(data('retried'));await retry;assert.equal(h.events.at(-1),'retried');
});
test('timeouts abort only their own request and malformed responses never enable starting',async()=>{
    const h=harness();const first=h.loader.request(1,'quiz');const oldTimeout=[...h.timers.values()][0];
    const second=h.loader.request(2,'quiz');oldTimeout();
    assert.equal(h.requests[1].signal.aborted,false);
    h.requests[0].reject(new Error('aborted'));await first;
    h.requests[1].resolve({availableSources:'invalid',progress:{}});await second;
    assert.equal(h.events.at(-1),'error');assert.equal(h.timers.size,0);
});
test('quiz links preserve source names with ampersands, plus signs, spaces and Unicode in every mode',()=>{
    const h=harness();const names=['A & B','C+D','طب الأعصاب', '"quoted" <source>'];
    for(const mode of ['quiz','tutor','read']){
        const url=new URL(h.api.quizUrl({courseId:1,topicId:2,mode,sources:names,tutorScope:'remaining'}),'https://example.test');
        assert.deepEqual(url.searchParams.getAll('sources[]'),names);
        assert.equal(url.searchParams.has('B'),false);
        assert.equal(url.pathname,`/quiz/1/2${mode==='quiz'?'':'/'+mode}`);
        assert.equal(url.searchParams.get('tutor_scope'),mode==='tutor'?'remaining':null);
    }
});
