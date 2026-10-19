import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/tutor-mode.js', import.meta.url), 'utf8');
function harness(fetch) {
    const feedbackParts = new Map(['.alert', '.result-text', '.explanation-content'].map(key => [key, {style:{},textContent:'',innerHTML:''}]));
    const feedback = {style:{}, querySelector: key => feedbackParts.get(key)};
    const input = {value:'kidny',disabled:false};
    const button = {disabled:false, textContent:'Check Answer', style:{}};
    const card = {querySelector: key => ({'.written-answer':input,'.check-answer':button,'.answer-feedback':feedback}[key] || null), querySelectorAll:()=>[], dataset:{}};
    const timeouts = new Map();
    const context = vm.createContext({
        console:{log(){},warn(){},error(){}}, Date, performance, AbortController,
        TUTOR_CONFIG: {totalQuestions:2},
        document:{addEventListener(){},getElementById:id=>id==='question-1'?card:null,
            querySelector:()=>({content:'test-token'}),querySelectorAll:()=>[]},
        setTimeout: (fn,ms)=>{const id=timeouts.size+1;timeouts.set(id,{fn,ms});return id;},
        clearTimeout:id=>timeouts.delete(id), fetch, addEventListener(){},
    });
    context.window=context;
    vm.runInContext(source,context);
    vm.runInContext(`questionData = {1:{question_type:'written',correct_answer:'kidney'}};
        window.performanceStats = {instantChecks:0,cacheHits:0,totalTime:0};
        window.renderedResults = [];
        processAnswerResult = function(number,id,data) { renderedResults.push(data); };
        updateQuestionUI = function() { throw new Error('Error must not be rendered as a graded result'); };
        resetCorrectAnswerStreak = function() { throw new Error('A connection error must not reset the streak'); };`,context);
    return { context,input,button,card,feedbackParts,timeouts,
        state:()=>vm.runInContext('({totalAnswers,correctAnswers,questionStates,userAnswers})',context),
        check:()=>context.checkAnswer(1,1) };
}

test('a failed check preserves the answer, score and ability to retry', async()=>{
    let attempts=0;
    const h=harness(async()=>{if(++attempts===1)throw new Error('offline'); return {ok:true,json:async()=>({success:true,is_correct:true,correct_answer:'kidney'})};});
    await h.check();
    assert.equal(h.state().totalAnswers,0);
    assert.equal(Boolean(h.state().questionStates[1]),false);
    assert.equal(h.input.value,'kidny');
    assert.equal(h.input.disabled,false);
    assert.equal(h.button.disabled,false);
    assert.match(h.feedbackParts.get('.result-text').textContent,/retry|try again|check/i);
    await h.check();
    assert.equal(h.state().totalAnswers,1);
    assert.equal(h.state().correctAnswers,1);
    assert.equal(h.context.renderedResults.length,1);
    assert.equal(h.timeouts.size,0);
});

test('repeated checks while pending or after success count once', async()=>{
    let finish;
    let requests=0;
    const h=harness(()=>{requests++;return new Promise(resolve=>finish=resolve);});
    const pending=h.check();
    await h.check();
    assert.equal(requests,1);
    h.context.handleDontKnow(1,1);
    assert.equal(h.state().totalAnswers,0);
    finish({ok:true,json:async()=>({success:true,is_correct:true,correct_answer:'kidney'})});
    await pending;
    await h.check();
    assert.equal(h.state().totalAnswers,1);
    assert.equal(requests,1);
});

test('blank or missing questions do not lock the question state',async()=>{
    const h=harness(async()=>{throw new Error('No request expected');});
    h.input.value='';
    await h.check();
    await h.context.checkAnswer(99,99);
    assert.equal(Boolean(h.state().questionStates[1]),false);
    assert.equal(Boolean(h.state().questionStates[99]),false);
    assert.equal(h.state().totalAnswers,0);
});

test('next-question navigation updates both question counters',()=>{
    const h=harness(async()=>{throw new Error('No request expected');});
    const elements={
        'question-2':{style:{}}, 'np-current-q':{textContent:''}, 'np-mobile-current':{textContent:''}
    };
    const original=h.context.document.getElementById;
    h.context.document.getElementById=id=>elements[id]||original(id);
    h.context.scrollTo=()=>{};
    vm.runInContext('updateSidebarActiveState = function() {}; showNextQuestion(1);',h.context);
    assert.equal(String(elements['np-current-q'].textContent),'2');
    assert.equal(String(elements['np-mobile-current'].textContent),'2');
    assert.equal(vm.runInContext('currentQuestionIndex',h.context),2);
});

test('multiple choice feedback is local and counts only once',async()=>{
    let requests=0;
    const h=harness(async()=>{requests++;throw new Error('No request expected');});
    const original=h.card.querySelector;
    h.card.querySelector=selector=>selector==='.written-answer'?null:selector==='.option-card.selected'?{getAttribute:()=> 'E'}:original(selector);
    vm.runInContext("questionData[1] = {question_type:'multiple_choice',correct_answer:'E'}",h.context);
    await h.check();await h.check();
    assert.equal(requests,0);
    assert.equal(h.state().totalAnswers,1);
    assert.equal(h.state().correctAnswers,1);
});

test('timed out and invalid responses remain ungraded and retryable',async()=>{
    const h=harness((_url,options)=>new Promise((_resolve,reject)=>options.signal.addEventListener('abort',()=>reject(new Error('timeout')))));
    const pending=h.check();
    const timeout=[...h.timeouts.values()][0];
    assert.equal(timeout.ms,12000);
    timeout.fn();
    await pending;
    assert.equal(h.state().totalAnswers,0);
    assert.equal(h.button.disabled,false);
    assert.equal(h.timeouts.size,0);
    const invalid=harness(async()=>({ok:true,json:async()=>({success:false})}));
    await invalid.check();
    assert.equal(invalid.state().totalAnswers,0);
});
