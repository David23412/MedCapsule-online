import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/profile-page.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));
function element(properties = {}) {
    const listeners = new Map();
    return {
        style: {}, attributes: {}, disabled: false, value: '', innerHTML: 'Save',
        classList: { add() {}, remove() {}, toggle() {}, contains: () => false },
        addEventListener(name, fn) { listeners.set(name, fn); },
        emit(name, event = {}) { return listeners.get(name)?.call(this, event); },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        closest: () => null,
        ...properties,
    };
}
function harness(fetch, { brokenChart = false } = {}) {
    const listeners = new Map();
    const elements = new Map();
    const timeouts = new Map();
    let timerId = 0;
    const addEventListener = (name, fn) => {
        if (!listeners.has(name)) listeners.set(name, []);
        listeners.get(name).push(fn);
    };
    const save = element();
    const privacy = element({ checked: true });
    const photo = element({ files: [{ size: 1024, type: 'image/png' }] });
    const picture = element();
    const image = element({ src: 'old.png' });
    const modal = element({ remove: () => elements.delete('goalModal') });
    for (const [key, value] of Object.entries({
        goalModal: modal, goalInput: element({ value: '100' }), dailyStudyMinutesInput: element({ value: '90' }),
        examDateInput: element(), plannerFocusInput: element({ value: 'balanced' }),
        leaderboardToggle: privacy, profilePictureInput: photo, profilePictureContainer: picture,
        profileImage: image, profileForm: { action: '/profile/update-picture' },
        profileWeeklyQuestions: element({ textContent: '4' }), profileWeeklyGoal: element({ textContent: '/ 50' }),
        profileWeeklyPercent: element({ textContent: '8%' }), profileWeeklyBar: element(),
    })) elements.set(key, value);
    if (brokenChart) elements.set('performanceChart', { getContext() { throw new Error('Chart unavailable'); } });
    const requests = [];
    const alerts = [];
    const document = {
        visibilityState: 'visible', addEventListener, body: element({ appendChild() {} }),
        getElementById: id => elements.get(id) || null,
        querySelector: selector => selector.startsWith('meta[') ? { content: 'test-token' }
            : selector === '#goalModal .btn-primary' ? save : null,
        querySelectorAll: () => [],
    };
    const context = vm.createContext({
        console: { warn() {}, error() {}, log() {} }, document, Date, Intl, Map, AbortController,
        ProfilePageConfig: { updateGoalRoute: '/profile/update-goal' }, navigator: { onLine: true },
        innerWidth: 1200, addEventListener, localStorage: { getItem: () => null },
        FormData: class { append() {} }, alert: value => alerts.push(value),
        setTimeout: (fn, ms) => { const id = ++timerId; timeouts.set(id, { fn, ms }); return id; },
        clearTimeout: id => timeouts.delete(id),
        fetch: (url, options) => { requests.push({ url, options }); return fetch(url, options); },
        ...(brokenChart ? { Chart: class {} } : {}),
    });
    context.window = context;
    vm.runInContext(source, context);
    const emit = (name, event = {}) => { for (const fn of listeners.get(name) || []) fn(event); };
    emit('DOMContentLoaded');
    return { context, document, elements, save, privacy, photo, picture, image, requests, alerts, timeouts, emit };
}

test('photo upload requests JSON, keeps the image on rejection and permits retrying the same file', async () => {
    let attempts = 0;
    const h = harness(async () => ++attempts === 1
        ? { ok: false, json: async () => ({ message: 'Please choose a smaller image.' }) }
        : { ok: true, json: async () => ({ success: true, new_url: '/uploads/new.png?v=42' }) });
    await h.photo.emit('change');
    assert.equal(h.requests[0].options.headers.Accept, 'application/json');
    assert.equal(h.image.src, 'old.png');
    assert.equal(h.photo.disabled, false);
    assert.equal(h.photo.value, '');
    assert.match(h.alerts[0], /smaller image/);
    await h.photo.emit('change');
    assert.equal(h.image.src, '/uploads/new.png?v=42');
});

test('pending photo uploads are not duplicated and invalid files stay local', async () => {
    let finish;
    const h = harness(() => new Promise(resolve => { finish = resolve; }));
    const pending = h.photo.emit('change');
    await h.photo.emit('change');
    assert.equal(h.requests.length, 1);
    finish({ ok: true, json: async () => ({ success: true }) });
    await pending;
    h.photo.files = [{ size: 6 * 1024 * 1024, type: 'image/png' }];
    await h.photo.emit('change');
    assert.equal(h.requests.length, 1);
    assert.equal(h.photo.disabled, false);
});

test('goal saves reject empty values, prevent duplicates and show server validation errors', async () => {
    let finish;
    const h = harness(() => new Promise(resolve => { finish = resolve; }));
    h.elements.get('goalInput').value = '';
    h.context.saveGoal();
    assert.equal(h.requests.length, 0);
    h.elements.get('goalInput').value = '100';
    h.context.saveGoal();
    h.context.saveGoal();
    assert.equal(h.requests.length, 1);
    finish({ ok: false, json: async () => ({ errors: { exam_date: ['Choose today or a future date.'] } }) });
    await settle();
    assert.equal(h.save.disabled, false);
    assert.ok(h.elements.has('goalModal'));
    assert.match(h.alerts.at(-1), /future date/);
});

test('failed privacy saves restore the prior setting and unlock the switch', async () => {
    let finish;
    const h = harness(() => new Promise(resolve => { finish = resolve; }));
    h.privacy.checked = false;
    h.privacy.emit('change');
    h.privacy.emit('change');
    assert.equal(h.requests.length, 1);
    assert.equal(h.privacy.disabled, true);
    finish({ ok: false, json: async () => ({ message: 'Please sign in again.' }) });
    await settle();
    assert.equal(h.privacy.checked, true);
    assert.equal(h.privacy.disabled, false);
    assert.equal(h.alerts.at(-1), 'Please sign in again.');
});

test('progress events share a request, pause when hidden, and recover after timeout', async () => {
    const h = harness((_url, options) => new Promise((_resolve, reject) =>
        options.signal.addEventListener('abort', () => reject(new Error('timeout')))));
    h.emit('sourceProgressUpdated');
    h.emit('storage', { key: 'quizProgressUpdate' });
    h.emit('assignmentCompleted');
    assert.equal(h.requests.length, 1);
    [...h.timeouts.values()].find(x => x.ms === 15000).fn();
    await settle();
    h.document.visibilityState = 'hidden';
    h.emit('sourceProgressUpdated');
    assert.equal(h.requests.length, 1);
    h.document.visibilityState = 'visible';
    h.emit('visibilitychange');
    assert.equal(h.requests.length, 2);
});

test('failed chart setup does not break profile settings and photo handlers', async () => {
    const h = harness(async () => ({ ok: true, json: async () => ({ success: true }) }), { brokenChart: true });
    h.privacy.checked = false;
    h.privacy.emit('change');
    await settle();
    assert.equal(h.requests[0].url, '/profile/update-privacy');
    assert.equal(h.privacy.disabled, false);
    assert.equal(typeof h.context.saveGoal, 'function');
});

test('successful goal saves update the goal and percentage as separate fields', async () => {
    const h = harness(async () => ({ ok: true, json: async () => ({ success: true }) }));
    h.context.saveGoal();
    await settle();
    assert.equal(h.elements.get('profileWeeklyGoal').textContent, '/ 100');
    assert.equal(h.elements.get('profileWeeklyPercent').textContent, '4%');
    assert.equal(h.elements.get('profileWeeklyQuestions').textContent, '4');
    assert.equal(h.elements.get('profileWeeklyBar').style.width, '4%');
    assert.equal(h.elements.has('goalModal'), false);
});
