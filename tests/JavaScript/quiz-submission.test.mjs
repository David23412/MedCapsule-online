import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

function harness(file, {expired=false, outcome='pending'}={}) {
    const elements=new Map();
    const listeners=new Map();
    const intervals=new Map();
    const now=Date.now();
    let requests=0, finish;
    function element(id) {
        if(!elements.has(id)) elements.set(id, {style:{},disabled:false,value:'30',innerHTML:'',textContent:'',
            classList:{add(){},remove(){},contains(){return false;}},
            addEventListener:(event,fn)=>listeners.set(`${id}:${event}`,fn),
            setAttribute(){},getAttribute(){return null;},querySelector(){return null;},querySelectorAll(){return [];}});
        return elements.get(id);
    }
    const document={
        getElementById:element,
        querySelector:()=>({content:'csrf'}),querySelectorAll:()=>[],
        addEventListener:(event,fn)=>listeners.set(`document:${event}`,fn),
    };
    const context=vm.createContext({document, Date, console:{log(){},warn(){},error(){}},
        Audio:class {play(){return Promise.resolve();} pause(){} load(){}},
        IntersectionObserver:class {observe(){}}, CustomEvent:class {},
        requestAnimationFrame(){},setTimeout(){return 1;},clearTimeout(){},
        setInterval:fn=>{const id=intervals.size+1;intervals.set(id,fn);return id;},
        clearInterval:id=>intervals.delete(id),addEventListener(){},dispatchEvent(){},
        fetch:()=>{requests++; if(outcome==='error')return Promise.reject(new Error('offline'));
            if(outcome==='success')return Promise.resolve({ok:true,json:async()=>({success:true,score:1,total:1})});
            return new Promise(resolve=>finish=resolve);},
        QuizSessionDraft:{create:()=>({load:()=>({startedAt:now-(expired?61000:0),answers:{}}),save(){},clear(){}})},
        QuizSolvePageConfig:{totalQuestions:1,timeLimitSeconds:60},
        RandomQuizPageConfig:{totalQuestions:1,timeLimitSeconds:60,gradeUrl:'/grade'},
    });
    context.window=context;
    vm.runInContext(readFileSync(new URL(`../../public/js/${file}`,import.meta.url),'utf8'),context);
    listeners.get('document:DOMContentLoaded')();
    return {requests:()=>requests, submit:()=>listeners.get('quiz-form:submit')({preventDefault(){}}),
        finish:()=>finish({ok:false,json:async()=>({error:'offline'})}),intervals,elements};
}

for(const file of ['quiz-solve-page.js','random-quiz-page.js']) {
    test(`${file}: an expired saved quiz initializes before auto-submit`,async()=>{
        const h=harness(file,{expired:true,outcome:'success'});
        await new Promise(setImmediate);
        assert.equal(h.requests(),1);
        h.submit();
        assert.equal(h.requests(),1);
    });
    test(`${file}: repeated submit sends one request, failure permits retry`,async()=>{
        const h=harness(file);
        h.submit();h.submit();
        assert.equal(h.requests(),1);
        h.finish();
        await new Promise(setImmediate);
        assert.equal(h.intervals.size,1,'the timer must resume after a failed manual submit');
        h.submit();
        assert.equal(h.requests(),2);
    });
}
