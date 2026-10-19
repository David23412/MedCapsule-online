import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/quiz-session-draft.js', import.meta.url), 'utf8');
function harness() {
    let now = 1_800_000_000_000;
    const records = new Map();
    const timers = new Map();
    const pendingTimers = new Map();
    const events = new Map();
    const window = {
        location: { pathname: '/quiz', search: '' },
        addEventListener(event, callback) { const callbacks=events.get(event)||[];callbacks.push(callback);events.set(event,callbacks); },
        setTimeout(callback) { const id=pendingTimers.size+1;pendingTimers.set(id,callback);return id; },
        clearTimeout(id) {pendingTimers.delete(id);},
        setInterval(callback) { const id = timers.size + 1; timers.set(id, callback); return id; },
        clearInterval(id) { timers.delete(id); },
    };
    const storage = {
        getItem: key => records.get(key) ?? null,
        setItem: (key, value) => records.set(key, value),
        removeItem: key => records.delete(key),
        key: index => [...records.keys()][index],
        get length() { return records.size; },
    };
    vm.runInNewContext(source, { window, document: {}, localStorage: storage, Date: { now: () => now } });
    return { create: options => window.QuizSessionDraft.create({ key: 'test', ...options }),
        advance: ms => { now += ms; }, tick: () => [...timers.values()].forEach(callback => callback()), storage, pagehide:()=>events.get('pagehide')?.forEach(callback=>callback()), flush:()=>[...pendingTimers.values()].forEach(callback=>callback()) };
}

test('a changed question set starts with fresh answers and timer', () => {
    const h = harness();
    const first = h.create({ questionIds: [1, 2] });
    h.advance(300);
    first.save({ answers: { 1: 'A' }, startedAt: 123 });
    const next = h.create({ questionIds: [2, 3] });
    assert.equal(next.load().answers, undefined);
    assert.notEqual(next.load().startedAt, 123);
});

test('reordering the same questions preserves the saved draft', () => {
    const h = harness();
    const first = h.create({ questionIds: [1, 2] });
    h.advance(300);
    first.save({ answers: { 1: 'A' }, startedAt: 123 });
    const next = h.create({ questionIds: ['2', '1'] });
    assert.equal(next.load().answers['1'], 'A');
    assert.equal(next.load().startedAt, 123);
});

test('inactivity expiry notifies the page once and removes the draft', () => {
    const h = harness();
    let expired = 0;
    const draft = h.create({ questionIds: [1], onExpire: () => expired++ });
    h.advance(1_799_999);
    h.tick();
    assert.equal(expired, 0);
    h.advance(1);
    h.tick();
    h.tick();
    assert.equal(expired, 1);
    assert.equal(h.storage.getItem(draft.storageKey), null);
});

test('activity extends the inactivity timer', () => {
    const h = harness();
    let expired = false;
    const draft = h.create({ questionIds: [1], onExpire: () => expired = true });
    h.advance(1_000_000);
    draft.touch(true);
    h.advance(1_000_000);
    h.tick();
    assert.equal(expired, false);
    assert.ok(draft.load());
});

test('saving an expired draft cannot silently revive it', () => {
    const h = harness();
    let expired = 0;
    const draft = h.create({ questionIds: [1], onExpire: () => expired++ });
    h.advance(1_800_000);
    assert.equal(draft.save({ answers: { 1: 'A' } }), false);
    assert.equal(expired, 1);
    assert.equal(h.storage.getItem(draft.storageKey), null);
});

test('opening an expired draft starts fresh without a reload loop', () => {
    const h = harness();
    const first = h.create({ questionIds: [1] });
    h.advance(300);
    first.save({ answers: { 1: 'A' } });
    h.advance(1_800_000);
    let expired = 0;
    const reopened = h.create({ questionIds: [1], onExpire: () => expired++ });
    assert.equal(reopened.load().answers, undefined);
    assert.equal(expired, 0);
});

test('rapid typing is coalesced and the latest input is flushed on page exit', () => {
    const h=harness();
    const draft=h.create({questionIds:[1]});
    draft.save({answers:{1:'kid'}});
    draft.save({answers:{1:'kidny'}});
    h.pagehide();
    assert.equal(draft.load().answers['1'],'kidny');
});

test('a pending save cannot recreate a completed draft after it is cleared',()=>{
    const h=harness();
    const draft=h.create({questionIds:[1]});
    draft.save({answers:{1:'kidny'}});
    draft.clear();
    h.flush();h.pagehide();
    assert.equal(h.storage.getItem(draft.storageKey),null);
});
