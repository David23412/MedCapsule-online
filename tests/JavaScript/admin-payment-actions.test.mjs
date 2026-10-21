import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../public/js/admin-payment-actions.js', import.meta.url), 'utf8');
function harness() {
    const requests = [], notifications = [], timers = new Map();
    const buttons = [{disabled:false}, {disabled:false}];
    let refreshes = 0, timerId = 0;
    const context = vm.createContext({AbortController,
        document: {querySelectorAll: () => buttons}, MANAGE_ACCESS_CONFIG: {csrfToken:'sample'},
        showNotification: (...args) => notifications.push(args), loadPayments: () => refreshes++,
        setTimeout(fn) {const id = ++timerId; timers.set(id, fn); return id;}, clearTimeout(id) {timers.delete(id);},
        fetch(url, options) {return new Promise((resolve, reject) => requests.push({url, options, reject,
            resolve(data, status=200) {resolve({ok:status<400, status, json:async()=>data});}}));},
    });
    context.window = context; vm.runInContext(source, context);
    return {context, requests, notifications, buttons, timers, refreshes: () => refreshes};
}
test('reject uses the registered endpoint and blocks duplicate or conflicting clicks', async () => {
    const h = harness(); const pending = h.context.rejectPayment(3);
    await h.context.rejectPayment(3); await h.context.acceptPayment(3);
    assert.equal(h.requests.length, 1);
    assert.equal(h.requests[0].url, '/admin/payments/reject/3');
    assert.equal(h.buttons.every(button => button.disabled), true);
    h.requests[0].resolve({success:true, message:'Rejected'}); await pending;
    assert.equal(h.refreshes(), 1);
    assert.equal(h.buttons.every(button => !button.disabled), true);
    assert.equal(h.timers.size, 0);
});
test('approval failures do not show success or refresh away the error', async () => {
    const h = harness(); const pending = h.context.acceptPayment(3);
    assert.equal(h.requests[0].url, '/admin/payments/verify/3');
    h.requests[0].resolve({success:false, message:'Already processed'}, 409); await pending;
    assert.equal(h.refreshes(), 0);
    assert.equal(h.notifications[0][0], 'error');
    assert.match(h.notifications[0][2], /Already processed/);
    assert.equal(h.buttons[0].disabled, false);
});
test('timeouts restore controls and explain how to check an uncertain result', async () => {
    const h = harness(); const pending = h.context.acceptPayment(3);
    [...h.timers.values()][0](); assert.equal(h.requests[0].options.signal.aborted, true);
    h.requests[0].reject(Object.assign(new Error('aborted'), {name:'AbortError'})); await pending;
    assert.match(h.notifications[0][2], /Refresh the payment list/);
    assert.equal(h.buttons[0].disabled, false);
    assert.equal(h.timers.size, 0);
});
test('expired sessions show an actionable message and allow a retry', async () => {
    const h = harness(); const pending = h.context.rejectPayment(3);
    h.requests[0].resolve({}, 419); await pending;
    assert.match(h.notifications[0][2], /session expired/);
    const retry = h.context.rejectPayment(3);
    assert.equal(h.requests.length, 2);
    h.requests[1].resolve({success:true}); await retry;
    assert.equal(h.refreshes(), 1);
});
test('detail-page notes and rejection reason use the same guarded request', async () => {
    const h = harness();
    const pending = h.context.rejectPayment(3, {reason:'Amount mismatch', admin_notes:'Sample review note'});
    assert.deepEqual(JSON.parse(h.requests[0].options.body), {reason:'Amount mismatch', admin_notes:'Sample review note'});
    assert.equal(h.requests[0].options.headers['Content-Type'], 'application/json');
    h.requests[0].resolve({success:true}); await pending;
});
