import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { randomUUID } from 'node:crypto';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/mistakes-learn-page.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));
const config = overrides => ({ reviewLearnUrl: '/review/learn', currentDeckMode: 'classic', currentMode: 'quiz', ...overrides });

function harness(fetch = async () => {}) {
    const nodes = new Map(), timers = new Map(), requests = [], history = [], initialized = [];
    let nextId = 0;
    const element = (selector, values = {}) => ({
        style: {}, disabled: false, isConnected: true,
        replaceWith(next) { this.isConnected = false; nodes.set(selector, next); next.isConnected = true; },
        ...values,
    });
    function page(settings, current = false) {
        const map = new Map();
        for (const selector of ['.sidebar', '.main-content', '#popup-overlay', '#course-filter']) {
            map.set(selector, element(selector));
        }
        map.set('#mistakes-learn-config', element('#mistakes-learn-config', { textContent: JSON.stringify(settings) }));
        if (current) for (const [key, node] of map) nodes.set(key, node);
        return { querySelector: selector => map.get(selector), getElementById: id => map.get(`#${id}`) };
    }
    page(config(), true);
    const events = new Map();
    const context = vm.createContext({
        AbortController, crypto: { randomUUID }, URL,
        document: { querySelector: selector => nodes.get(selector), getElementById: id => nodes.get(`#${id}`), addEventListener() {} },
        addEventListener: (name, fn) => events.set(name, fn),
        location: { href: 'https://example.test/review/learn' },
        history: { pushState: (a, b, url) => history.push(url), replaceState: (a, b, url) => history.push(url) },
        fetch: (url, options) => { requests.push({ url, options }); return fetch(url, options); },
        DOMParser: class { parseFromString(text) { return page(JSON.parse(text)); } },
        setTimeout: (fn, ms) => { timers.set(++nextId, { fn, ms }); return nextId; },
        clearTimeout: id => timers.delete(id),
        Function() { throw new Error('Executable configuration is blocked by CSP'); },
    });
    context.window = context;
    vm.runInContext(source, context);
    context.initializeMistakesLearnPage = () => initialized.push(vm.runInContext('currentLearnPageConfig', context));
    return { context, timers, nodes, requests, history, initialized, events,
        load: (url, options) => context.loadLearnFilterInPlace(url, options),
    };
}
const response = (settings, url) => ({ ok: true, url, text: async () => JSON.stringify(settings) });

test('filter changes read fresh JSON settings without eval and replace the completion popup', async () => {
    const h = harness(async () => response(config({ currentMode: 'spaced', currentDeckMode: 'spaced', selectedQuestionType: 'written' }), '/review/learn?mode=spaced'));
    const oldPopup = h.nodes.get('#popup-overlay');
    await h.load('/review/learn?mode=spaced');
    assert.equal(h.initialized[0].currentDeckMode, 'spaced');
    assert.equal(h.initialized[0].selectedQuestionType, 'written');
    assert.equal(oldPopup.isConnected, false);
    assert.notEqual(oldPopup, h.nodes.get('#popup-overlay'));
    assert.equal(h.nodes.get('#course-filter').disabled, false);
    assert.equal(h.timers.size, 0);
});

test('superseded filter requests cannot replace a newer deck or release its controller', async () => {
    const releases = [];
    const h = harness(() => new Promise(resolve => releases.push(resolve)));
    const first = h.load('/review/learn?course=1');
    const second = h.load('/review/learn?course=2');
    assert.equal(h.requests[0].options.signal.aborted, true);
    releases[0](response(config({ selectedTopicIds: [1] }), '/review/learn?course=1'));
    await first;
    assert.equal(h.initialized.length, 0);
    const third = h.load('/review/learn?course=3');
    assert.equal(h.requests[1].options.signal.aborted, true);
    releases[2](response(config({ selectedTopicIds: [3] }), '/review/learn?course=3'));
    await third;
    releases[1](response(config({ selectedTopicIds: [2] }), '/review/learn?course=2'));
    await second;
    assert.equal(h.initialized.length, 1);
    assert.deepEqual(Array.from(h.initialized[0].selectedTopicIds), [3]);
    assert.equal(h.history.length, 1);
});

test('timed out filter navigation restores controls and falls back to the requested page', async () => {
    const h = harness((url, options) => new Promise((resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(Object.assign(new Error(), { name: 'AbortError' })));
    }));
    const pending = h.load('/review/learn?course=1');
    for (const timer of h.timers.values()) timer.fn();
    await pending;
    assert.equal(h.context.location.href, '/review/learn?course=1');
    assert.equal(h.nodes.get('#course-filter').disabled, false);
    assert.equal(h.nodes.get('.main-content').style.pointerEvents, '');
    assert.equal(h.timers.size, 0);
});

test('login/empty-deck redirects navigate instead of leaving an old deck on screen', async () => {
    const h = harness(async () => ({ ok: true, url: '/review', text: async () => 'redirect' }));
    h.context.DOMParser = class { parseFromString() { return { getElementById: () => null }; } };
    await h.load('/review/learn?course=1');
    assert.equal(h.context.location.href, '/review');
    assert.equal(h.initialized.length, 0);
});

test('disposing a deck removes its listeners and scheduled transitions', () => {
    const h = harness();
    const target = new EventTarget();
    let calls = 0;
    const first = h.context.createLearnSessionLifecycle();
    first.listen(target, 'click', () => calls++);
    first.schedule(() => calls++, 600);
    const oldCallback = [...h.timers.values()][0].fn;
    first.dispose();
    oldCallback();
    target.dispatchEvent(new Event('click'));
    assert.equal(calls, 0);
    assert.equal(h.timers.size, 0);
    assert.equal(first.active, false);
    const second = h.context.createLearnSessionLifecycle();
    second.listen(target, 'click', () => calls++);
    target.dispatchEvent(new Event('click'));
    assert.equal(calls, 1);
});

test('study-time retry shares a stable key, and a second session gets a new key', async () => {
    const h = harness();
    const requests = [];
    let fail = true, release;
    h.context.StudyRequests = { post: (url, body, options) => {
        requests.push({ url, body, options });
        if (fail) return Promise.reject(new Error('lost confirmation'));
        return new Promise(resolve => { release = resolve; });
    } };
    const save = h.context.createStudyTimeSave('/learn/save-time', 180);
    await assert.rejects(save());
    fail = false;
    const first = save();
    assert.equal(first, save());
    release({ success: true });
    await first;
    await save();
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.requestKey, requests[1].options.requestKey);
    assert.equal(requests[1].body.study_time, 180);
    const second = h.context.createStudyTimeSave('/learn/save-time', 240)();
    release({ success: true });
    await second;
    assert.notEqual(requests[1].options.requestKey, requests[2].options.requestKey);
    await settle();
});
