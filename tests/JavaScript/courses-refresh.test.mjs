import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/courses-page.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));

function harness({ user = 1, pending = [], fetch = async () => ({ ok: true, json: async () => ({}) }) } = {}) {
    const listeners = new Map();
    const intervals = new Map();
    const timeouts = new Map();
    let nextId = 0;
    const addEventListener = (name, handler) => {
        if (!listeners.has(name)) listeners.set(name, []);
        listeners.get(name).push(handler);
    };
    const elements = new Map();
    const makeElement = () => ({
        style: {}, classList: { add() {}, remove() {}, toggle() {}, contains: () => false },
        appendChild() {}, remove() {}, addEventListener() {},
    });
    const requests = [];
    const document = {
        visibilityState: 'visible', readyState: 'loading', addEventListener,
        getElementById: id => elements.get(id) || null,
        querySelector: selector => selector.startsWith('meta[')
            ? { getAttribute: () => 'test-token', content: 'test-token' }
            : elements.get(selector) || null,
        querySelectorAll: () => [], createElement: makeElement,
        head: makeElement(), body: makeElement(), documentElement: {},
    };
    const context = vm.createContext({
        console: { log() {}, warn() {}, error() {} }, Date, performance, AbortController,
        CoursesPageConfig: { authUserId: user, pendingCourseIds: pending }, document,
        navigator: { onLine: true }, location: { origin: 'https://sample.test' },
        addEventListener, removeEventListener() {}, requestAnimationFrame: fn => fn(),
        bootstrap: { Dropdown: class {}, Modal: class { show() {} } },
        fetch: (url, options) => { requests.push({ url, options }); return fetch(url, options); },
        setInterval: (fn, ms) => { const id = ++nextId; intervals.set(id, { fn, ms }); return id; },
        clearInterval: id => intervals.delete(id),
        setTimeout: (fn, ms) => { const id = ++nextId; timeouts.set(id, { fn, ms }); return id; },
        clearTimeout: id => timeouts.delete(id),
    });
    // Storage is deliberately unavailable. Payment tracking must still work.
    Object.defineProperty(context, 'localStorage', { get() { throw new Error('Storage blocked'); } });
    context.window = context;
    vm.runInContext(source, context);
    return { context, document, elements, intervals, timeouts, requests,
        emit(name, event = {}) { for (const fn of listeners.get(name) || []) fn(event); },
    };
}

test('guest courses never start progress/payment requests or timers', async () => {
    const h = harness({ user: null, pending: [4] });
    h.emit('DOMContentLoaded');
    h.emit('visibilitychange');
    h.emit('online');
    h.emit('assignmentCompleted');
    h.emit('storage', { key: 'quizProgressUpdate' });
    await settle();
    assert.equal(h.requests.length, 0);
    assert.equal(h.intervals.size, 0);
});

test('progress events share one slow request and use a one-minute fallback', async () => {
    let finish;
    const h = harness({ fetch: () => new Promise(resolve => { finish = resolve; }) });
    assert.deepEqual([...h.intervals.values()].map(x => x.ms), [60000]);
    h.emit('assignmentCompleted');
    h.emit('sourceProgressUpdated');
    h.emit('storage', { key: 'quizProgressUpdate' });
    [...h.intervals.values()][0].fn();
    assert.equal(h.requests.length, 1);
    finish({ ok: true, json: async () => ({ correct_answers_count: 2 }) });
    await settle();
    assert.equal(h.timeouts.size, 0);
    h.document.visibilityState = 'hidden';
    h.emit('visibilitychange');
    h.emit('assignmentCompleted');
    assert.equal(h.intervals.size, 0);
    assert.equal(h.requests.length, 1);
    h.document.visibilityState = 'visible';
    h.emit('visibilitychange');
    assert.equal(h.requests.length, 2);
});

test('server-rendered pending payments refresh without browser storage and stop after approval', async () => {
    let finish;
    const h = harness({ pending: [4], fetch: () => new Promise(resolve => { finish = resolve; }) });
    let replacement;
    h.elements.set('#paymentBtn_4', {
        textContent: 'Payment Pending', style: {},
        parentNode: { replaceChild(link) { replacement = link; } },
    });
    h.emit('DOMContentLoaded');
    assert.equal(h.requests[0].url, '/api/check-payment-status');
    assert.deepEqual(JSON.parse(h.requests[0].options.body).course_ids, ['4']);
    [...h.intervals.values()].find(x => x.ms === 5000).fn();
    assert.equal(h.requests.length, 1);
    finish({ ok: true, json: async () => ({ success: true, completed_courses: [4] }) });
    await settle();
    assert.equal(replacement.href, '/topics/4');
    assert.equal(replacement.className, 'action-btn continue');
    assert.equal([...h.intervals.values()].some(x => x.ms === 5000), false);
});

test('failed payment checks remain retryable; session expiry stops background polling', async () => {
    let attempts = 0;
    const h = harness({ pending: [4], fetch: async () => {
        if (++attempts === 1) throw new Error('Offline');
        return { ok: false, status: 401 };
    } });
    h.emit('DOMContentLoaded');
    await settle();
    const interval = [...h.intervals.values()].find(x => x.ms === 5000);
    assert.ok(interval);
    interval.fn();
    await settle();
    assert.equal([...h.intervals.values()].some(x => x.ms === 5000), false);
    h.emit('visibilitychange');
    await settle();
    assert.equal(h.requests.filter(x => x.url === '/api/check-payment-status').length, 2);
});

test('expired progress sessions stop retrying and timed-out requests can recover', async () => {
    let attempts = 0;
    const h = harness({ fetch: (_url, options) => {
        if (++attempts > 1) return Promise.resolve({ ok: false, status: 401 });
        return new Promise((_resolve, reject) => options.signal.addEventListener('abort', () => reject(new Error('timeout'))));
    } });
    h.emit('assignmentCompleted');
    [...h.timeouts.values()].find(x => x.ms === 15000).fn();
    await settle();
    h.emit('assignmentCompleted');
    await settle();
    assert.equal(h.intervals.size, 0);
    h.emit('assignmentCompleted');
    await settle();
    assert.equal(h.requests.length, 2);
});

test('rejected server pending payments regain a working delegated Retry Payment action', async () => {
    const h = harness({ pending: [4], fetch: async url => ({ ok: true, json: async () => url.startsWith('/api/')
        ? { success: true, rejected_courses: [4] } : { success: false, message: 'Sample response' } }) });
    const button = {
        textContent: 'Payment Pending', style: {}, disabled: true, dataset: { courseId: '4' },
        classList: { add() {}, remove() {} },
    };
    h.elements.set('paymentModal', {});
    h.elements.set('#paymentBtn_4', button);
    h.context.alert = () => {};
    h.emit('DOMContentLoaded');
    await settle();
    [...h.timeouts.values()].find(x => x.ms === 3000).fn();
    assert.equal(button.disabled, false);
    h.emit('click', { target: { closest: selector => selector === '.show-payment-modal' ? button : null } });
    assert.equal(h.requests.at(-1).url, '/payments/4/options');
});
