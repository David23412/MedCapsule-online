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
            querySelectorAll: () => [],
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
