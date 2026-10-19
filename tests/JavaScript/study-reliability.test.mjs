import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { randomUUID } from 'node:crypto';
const read = file => readFileSync(new URL(`../../public/js/${file}`, import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));

function requestsHarness(fetch) {
    const requests = [], timers = new Map();
    let id = 0;
    const context = vm.createContext({
        crypto: { randomUUID }, AbortController,
        document: { querySelector: () => ({ content: 'token' }) },
        fetch: (url, options) => { requests.push({ url, ...options }); return fetch(url, options); },
        setTimeout: fn => { timers.set(++id, fn); return id; }, clearTimeout: id => timers.delete(id),
    });
    context.window = context;
    vm.runInContext(read('study-requests.js'), context);
    return { post: context.StudyRequests.post, requests, timers };
}
const ok = () => ({ ok: true, json: async () => ({ success: true, is_correct: true }) });
test('lost confirmation retains the original request body/key and retry is not a second attempt', async () => {
    let fail = true;
    const h = requestsHarness(async () => { if (fail) throw new Error('Connection lost'); return ok(); });
    await assert.rejects(h.post('/learn/review/1', { selected_answer: 'A', mode: 'practice', time_spent: 4 }));
    fail = false;
    await h.post('/learn/review/1', { selected_answer: 'A', mode: 'practice', time_spent: 20 });
    assert.equal(h.requests[0].body, h.requests[1].body);
    assert.equal(h.requests[0].headers['X-Study-Request'], h.requests[1].headers['X-Study-Request']);
    await h.post('/learn/review/1', { selected_answer: 'A', mode: 'practice', time_spent: 6 });
    assert.notEqual(h.requests[1].headers['X-Study-Request'], h.requests[2].headers['X-Study-Request']);
    assert.equal(h.timers.size, 0);
});
test('duplicate clicks share one in-flight save and timed out saves can retry', async () => {
    const h = requestsHarness((url, options) => new Promise((resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(Object.assign(new Error(), { name: 'AbortError' })));
    }));
    const body = { selected_answer: 'A' };
    const first = h.post('/learn/review/1', body);
    assert.equal(h.post('/learn/review/1', body), first);
    const rejection = assert.rejects(first, /connection took too long/);
    for (const fn of h.timers.values()) fn();
    await rejection;
    assert.equal(h.requests.length, 1);
    assert.equal(h.timers.size, 0);
});
test('HTML/server errors stay retryable and changing an answer uses a fresh key', async () => {
    const h = requestsHarness(async () => ({ ok: false, json: async () => { throw new Error('HTML'); } }));
    await assert.rejects(h.post('/learn/review/1', { selected_answer: 'A' }), /confirm the save/);
    await assert.rejects(h.post('/learn/review/1', { selected_answer: 'B' }));
    assert.notEqual(h.requests[0].headers['X-Study-Request'], h.requests[1].headers['X-Study-Request']);
});

function sessionHarness() {
    const events = new Map(), redirects = [], timers = [];
    const context = vm.createContext({
        URL, console: { warn() {}, error() {} },
        document: {
            readyState: 'complete', addEventListener: (name, fn) => events.set(name, fn),
            querySelector: () => ({ content: 'fresh-token' }),
            createElement: () => ({}),
        },
        location: { origin: 'https://example.test', href: 'https://example.test/profile', replace: url => redirects.push(url), reload() {} },
        fetch: async () => ({ status: 200 }),
        setInterval: fn => timers.push(fn),
    });
    context.window = context;
    vm.runInContext(read('session-handler.js'), context);
    return { events, redirects, timers, context };
}
test('active study sessions have no independent login-age timeout', async () => {
    const h = sessionHarness();
    assert.equal(h.timers.length, 0);
    await h.context.fetch('/api/progress');
    assert.equal(h.redirects.length, 0);
});
test('GET and external forms are untouched; missing POST token is added without bypassing submit handlers', () => {
    const h = sessionHarness();
    for (const method of ['get', 'post']) {
        let prevented = false, submitted = false;
        const form = {
            method, action: 'https://example.test/profile', hasAttribute: () => false,
            querySelector: () => null, appendChild(node) { this.token = node; },
            submit() { submitted = true; },
        };
        h.events.get('submit')({ target: form, preventDefault() { prevented = true; } });
        assert.equal(prevented, false);
        assert.equal(submitted, false);
        assert.equal(form.token?.value, method === 'post' ? 'fresh-token' : undefined);
    }
    const form = { method: 'post', action: 'https://external.test', hasAttribute: () => false };
    h.events.get('submit')({ target: form, preventDefault() { assert.fail('external form blocked'); } });
});

function topicsHarness(fetch, countdowns = []) {
    const listeners = new Map(), intervals = new Map(), timeouts = new Map(), requests = [];
    let id = 0;
    const on = (name, fn) => { if (!listeners.has(name)) listeners.set(name, []); listeners.get(name).push(fn); };
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, Date, console: { error() {}, warn() {}, log() {} },
        document: {
            visibilityState: 'visible', addEventListener: on, querySelector: () => null, querySelectorAll: selector => selector === '.assignment-countdown-minimal' ? countdowns : [],
            getElementById: id => id === 'sourceSelectionForm' ? { addEventListener() {} } : null, body: { classList: { toggle() {} } },
        },
        navigator: { onLine: true }, addEventListener: on, innerWidth: 1200,
        location: { origin: 'https://example.test', href: 'https://example.test/topics/1' },
        localStorage: { getItem: () => null }, sessionStorage: { getItem: () => null },
        IntersectionObserver: class { constructor(callback) { this.callback = callback; } observe(target) { this.callback([{ target, isIntersecting: true }]); } unobserve() {} },
        fetch: (url, options) => { requests.push({ url, options }); return fetch(url, options); },
        setInterval: (fn, ms) => { intervals.set(++id, { fn, ms }); return id; }, clearInterval: id => intervals.delete(id),
        setTimeout: (fn, ms) => { timeouts.set(++id, { fn, ms }); return id; }, clearTimeout: id => timeouts.delete(id),
    });
    context.window = context;
    vm.runInContext(read('topics-for-course-page.js'), context);
    const emit = (name, event = {}) => { for (const fn of listeners.get(name) || []) fn(event); };
    emit('DOMContentLoaded');
    return { context, emit, requests, intervals, timeouts };
}
test('topic progress shares pending request, stops offline/hidden, and recovers after timeout', async () => {
    const h = topicsHarness((url, options) => new Promise((resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(Object.assign(new Error(), { name: 'AbortError' })));
    }));
    assert.equal(h.requests.length, 1);
    for (const timer of h.intervals.values()) timer.fn();
    assert.equal(h.requests.length, 1);
    for (const timer of h.timeouts.values()) if (timer.ms === 12000) timer.fn();
    await settle();
    h.context.navigator.onLine = false;
    h.emit('offline');
    assert.equal(h.intervals.size, 0);
    h.context.navigator.onLine = true;
    h.emit('online');
    assert.equal(h.requests.length, 2);
    h.context.document.visibilityState = 'hidden';
    h.emit('visibilitychange');
    assert.equal(h.intervals.size, 0);
});
test('topic progress stops polling after server authentication failure', async () => {
    const h = topicsHarness(async () => ({ status: 401, ok: false }));
    await settle();
    assert.equal(h.intervals.size, 0);
    h.emit('online');
    assert.equal(h.requests.length, 1);
});

test('assignment duration keeps 24-hour hours and countdowns pause when hidden', async () => {
    const display = { textContent: '' };
    const countdown = {
        dataset: { deadline: new Date(Date.now() + (38 * 3600 + 5 * 60 + 1) * 1000).toISOString() },
        classList: { add() {}, remove() {} }, querySelector: () => display, closest: () => null,
    };
    const h = topicsHarness(async () => ({ ok: true, json: async () => ({ success: true, updates: [] }) }), [countdown]);
    assert.match(display.textContent, /^1d 14:05:0[01]$/);
    assert.doesNotMatch(display.textContent, /AM|PM/);
    assert.equal([...h.intervals.values()].filter(timer => timer.ms === 1000).length, 1);
    h.context.document.visibilityState = 'hidden';
    h.emit('visibilitychange');
    assert.equal(h.intervals.size, 0);
    h.context.document.visibilityState = 'visible';
    h.emit('visibilitychange');
    assert.equal([...h.intervals.values()].filter(timer => timer.ms === 1000).length, 1);
    await settle();
});
