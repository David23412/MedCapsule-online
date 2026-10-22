import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/tutor-mode.js', import.meta.url), 'utf8');

function harness(storage = {}) {
    const listeners = new Map();
    const button = { style: {}, disabled: false, getAttribute: () => 'checkAnswer(1, 42)' };
    const card = { id: 'question-1', querySelector: selector => selector === '.check-answer' ? button : null };
    const context = vm.createContext({
        console: { log() {}, warn() {}, error() {} }, Date, performance,
        TUTOR_CONFIG: { totalQuestions: 2 }, localStorage: storage,
        document: {
            addEventListener(name, handler) {
                if (!listeners.has(name)) listeners.set(name, []);
                listeners.get(name).push(handler);
            },
            getElementById: id => id === 'question-1' ? card : null,
            querySelectorAll: () => [], querySelector: () => null,
        },
        addEventListener() {},
    });
    context.window = context;
    vm.runInContext(source, context);
    context.checks = 0;
    context.moves = 0;
    context.checkAnswer = () => context.checks++;
    context.navigateNext = () => context.moves++;
    return { context, listeners, key(overrides = {}) {
        const event = {
            key: 'Enter', target: { tagName: 'TEXTAREA' }, defaultPrevented: false,
            preventDefault() { this.defaultPrevented = true; }, ...overrides,
        };
        for (const handler of listeners.get('keydown') || []) handler(event);
        return event;
    } };
}

test('Shift+Enter allows a written answer line break; Enter still checks the answer', () => {
    const h = harness();
    assert.equal(h.key({ shiftKey: true }).defaultPrevented, false);
    assert.equal(h.context.checks, 0);
    assert.equal(h.key().defaultPrevented, true);
    assert.equal(h.context.checks, 1);
});

test('composition, browser shortcuts, focused controls and editable content keep native keyboard behavior', () => {
    const h = harness();
    for (const overrides of [
        { isComposing: true }, { keyCode: 229 }, { ctrlKey: true }, { metaKey: true },
        { altKey: true }, { defaultPrevented: true },
        { target: { tagName: 'BUTTON' } }, { target: { tagName: 'A' } },
        { target: { tagName: 'SPAN', closest: () => ({ tagName: 'BUTTON' }) } },
        { target: { tagName: 'SPAN', isContentEditable: true } },
    ]) {
        const event = h.key(overrides);
        assert.equal(event.defaultPrevented, Boolean(overrides.defaultPrevented));
    }
    assert.equal(h.context.checks, 0);
    assert.equal(h.key({ key: 'ArrowRight', target: { tagName: 'DIV' } }).defaultPrevented, true);
    assert.equal(h.context.moves, 1);
});

test('blocked highlight storage does not stop selection handlers from initializing', () => {
    const h = harness({ getItem() { throw new Error('Storage blocked'); } });
    assert.doesNotThrow(() => vm.runInContext('initializeHighlighting()', h.context));
    assert.equal(h.listeners.get('mouseup').length, 1);
    assert.equal(h.listeners.get('touchend').length, 1);
    assert.doesNotThrow(() => h.listeners.get('mousedown')[0]({ target: { closest: () => null } }));
});

test('invalid saved highlights are ignored; failed writes retain highlights in memory', () => {
    for (const value of ['null', '[]', 'false', '{invalid']) {
        const h = harness({ getItem: () => value });
        assert.doesNotThrow(() => vm.runInContext('initializeHighlighting()', h.context));
        assert.equal(h.listeners.get('mouseup').length, 1);
        assert.equal(vm.runInContext('Object.keys(highlights).length', h.context), 0);
    }
    const h = harness({ setItem() { throw new Error('Quota exceeded'); } });
    vm.runInContext('serializeHighlightIntervals = () => [{start: 0, end: 3, color: "yellow"}]', h.context);
    assert.doesNotThrow(() => vm.runInContext('persistQuestionHighlights("question-42", {}, [])', h.context));
    assert.equal(vm.runInContext('highlights["question-42"][0].end', h.context), 3);
});

test('switching tutor choices exposes exactly one pressed answer to assistive technology', () => {
    const h = harness();
    const choices = ['A', 'B'].map(() => {
        const classes = new Set();
        return { attributes: {}, classList: { add: name => classes.add(name), remove: name => classes.delete(name) },
            setAttribute(name, value) { this.attributes[name] = value; },
            closest: () => ({ querySelectorAll: () => choices }) };
    });
    h.context.selectOption(choices[0]);
    assert.deepEqual(choices.map(choice => choice.attributes['aria-pressed']), ['true', 'false']);
    h.context.selectOption(choices[1]);
    assert.deepEqual(choices.map(choice => choice.attributes['aria-pressed']), ['false', 'true']);
});

test('question navigation exposes the current question without relying on its color', () => {
    const h = harness();
    const circles = [1, 2].map(number => ({ dataset: { question: String(number) }, attributes: {},
        classList: { add() {}, remove() {} }, setAttribute(name, value) { this.attributes[name] = value; } }));
    h.context.document.querySelectorAll = () => circles;
    h.context.updateSidebarActiveState(2);
    assert.deepEqual(circles.map(circle => circle.attributes['aria-current']), ['false', 'true']);
});

function keyboardChoices() {
    const h = harness();
    let now = 0;
    h.context.performance = {now: () => now};
    h.tick = ms => {now += ms;};
    h.context.requestAnimationFrame = () => 1;
    h.context.innerHeight = 900;
    h.context.scrollY = 0;
    h.context.scrollTo = value => {h.scroll = value;};
    const card = h.context.document.getElementById('question-1');
    const choices = ['A','B','C','D','E'].map(letter => {
        const classes = new Set();
        const option = {tagName:'BUTTON',disabled:false,style:{},attributes:{},
            classList:{add:key=>classes.add(key),remove:key=>classes.delete(key),contains:key=>classes.has(key)},
            setAttribute(key,value){this.attributes[key]=value;},
            focus(){h.focused=option;}, getBoundingClientRect:()=>({top:200,bottom:250}),
            getAttribute:key=>key==='data-option'?letter:null,
            closest: selector => selector === '.question-card' ? card
                : selector === '.options-container' ? {querySelectorAll:()=>choices}
                : selector.includes('.option-card') || selector === 'button, a' ? option : null,
        };
        return option;
    });
    card.querySelectorAll = () => choices;
    h.context.document.body = {classList:{contains:()=>false}};
    h.choices = choices;
    h.card = card;
    h.selected = () => choices.findIndex(choice=>choice.classList.contains('selected'));
    return h;
}

test('Up/Down work from focused option buttons, wrap choices, and keep one selected answer', () => {
    const h = keyboardChoices();
    assert.equal(h.key({key:'ArrowDown',target:h.choices[0]}).defaultPrevented,true);
    assert.equal(h.selected(),0);
    h.key({key:'ArrowDown',target:h.focused});
    assert.equal(h.selected(),1);
    assert.equal(h.focused,h.choices[1]);
    h.key({key:'ArrowUp',target:h.focused});
    h.key({key:'ArrowUp',target:h.focused});
    assert.equal(h.selected(),4);
    assert.deepEqual(h.choices.map(choice=>choice.attributes['aria-pressed']),['false','false','false','false','true']);
    h.key({target:h.focused});
    assert.equal(h.context.checks,1);
});

test('holding arrows has a controlled repeat rate while separate presses respond immediately', () => {
    const h = keyboardChoices();
    h.key({key:'ArrowDown',target:h.choices[0]});
    h.key({key:'ArrowDown',repeat:true,target:h.focused});
    assert.equal(h.selected(),0);
    h.tick(120);
    h.key({key:'ArrowDown',repeat:true,target:h.focused});
    assert.equal(h.selected(),1);
    h.key({key:'ArrowDown',target:h.focused});
    assert.equal(h.selected(),2);
    h.key({key:'ArrowRight',target:h.focused});
    h.key({key:'ArrowRight',repeat:true,target:h.focused});
    assert.equal(h.context.moves,1);
});

test('held Enter cannot repeatedly check or advance, and open dialogs keep their own keys', () => {
    const h = keyboardChoices();
    h.key({target:h.choices[0],repeat:true});
    assert.equal(h.context.checks,0);
    h.context.document.querySelector=()=>({});
    assert.equal(h.key({key:'ArrowDown',target:h.choices[0]}).defaultPrevented,false);
    assert.equal(h.selected(),-1);
});

test('graded, pending, disabled and review-mode choices cannot be changed by keyboard or clicks', () => {
    for (const state of ['graded','pending','disabled','review']) {
        const h = keyboardChoices();
        h.context.selectOption(h.choices[0]);
        if(state==='graded') vm.runInContext('questionStates[1] = true',h.context);
        if(state==='pending') h.context.checkAnswer.processing_1=true;
        if(state==='disabled') h.choices.forEach(choice=>choice.disabled=true);
        if(state==='review') h.context.document.body.classList.contains=()=>true;
        h.key({key:'ArrowDown',target:h.choices[0]});
        h.context.selectOption(h.choices[1]);
        assert.equal(h.selected(),0,state);
    }
});

test('keyboard question navigation hides only the previous card and restores focus without scroll animation', () => {
    const h = keyboardChoices();
    const target={style:{display:'none'},querySelector:()=>null,focus:options=>{h.targetFocus=options;}};
    h.card.style={};
    const original=h.context.document.getElementById;
    h.context.document.getElementById=id=>id==='question-2'?target:original(id);
    h.context.jumpToQuestion(2,{keyboard:true});
    assert.equal(h.card.style.display,'none');
    assert.equal(target.style.display,'block');
    assert.equal(h.targetFocus.preventScroll,true);
    assert.equal(h.scroll.behavior,'instant');
    h.context.jumpToQuestion(999,{keyboard:true});
    assert.equal(target.style.display,'block');
});
