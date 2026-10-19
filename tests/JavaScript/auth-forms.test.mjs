import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/auth-forms.js', import.meta.url), 'utf8');
function element(properties = {}) {
    const listeners = new Map();
    return {
        value: '', attributes: {}, validationMessage: '',
        addEventListener(name, fn) { listeners.set(name, fn); },
        emit(name, event = {}) { listeners.get(name)?.(event); },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        setCustomValidity(value) { this.validationMessage = value; },
        ...properties,
    };
}
function harness(kind = 'register') {
    const fields = {
        password: element({ value: 'ExamplePassword123!', type: 'password' }),
        ...(kind === 'register' ? {
            number: element({ value: '01012345678' }),
            password_confirmation: element({ value: 'ExamplePassword123!' }),
        } : {}),
    };
    const submit = element({ textContent: kind === 'register' ? 'Register' : 'Login', disabled: false });
    const form = element({
        dataset: { authForm: kind },
        querySelector(selector) {
            if (selector === 'button[type="submit"]') return submit;
            const name = selector.match(/\[name="(.+)"\]/)?.[1];
            return fields[name] || null;
        },
        checkValidity: () => Object.values(fields).every(field => !field.validationMessage),
        reportValidity() { this.reported = true; },
    });
    const toggle = element({ dataset: { passwordToggle: 'password' }, querySelector: () => ({ classList: { toggle() {} } }) });
    const events = new Map();
    const context = vm.createContext({
        document: {
            addEventListener: (name, fn) => events.set(name, fn),
            querySelectorAll: selector => selector === '[data-password-toggle]' ? [toggle] : [form],
            getElementById: () => fields.password,
        },
        window: { addEventListener: (name, fn) => events.set(name, fn) },
    });
    vm.runInContext(source, context);
    events.get('DOMContentLoaded')();
    return { fields, submit, form, toggle, events,
        send(overrides = {}) {
            const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...overrides };
            form.emit('submit', event);
            return event;
        },
    };
}

test('formatted and localized phones keep all digits and become the same server identity', () => {
    for (const value of ['+20 10 1234 5678', '0020 10 1234 5678', '٠١٠١٢٣٤٥٦٧٨', '۰۱۰۱۲۳۴۵۶۷۸']) {
        const h = harness();
        h.fields.number.value = value;
        h.fields.number.emit('blur');
        assert.equal(h.fields.number.value, '01012345678');
        assert.equal(h.send().defaultPrevented, false);
    }
});

test('invalid phones are not truncated into valid numbers or submitted', () => {
    const h = harness();
    h.fields.number.value = '010123456789';
    assert.equal(h.send().defaultPrevented, true);
    assert.equal(h.fields.number.value, '010123456789');
    assert.equal(h.submit.disabled, false);
    assert.ok(h.form.reported);
});

test('password mismatch is caught before submit and clears when corrected', () => {
    const h = harness();
    h.fields.password_confirmation.value = 'DifferentPassword';
    h.fields.password_confirmation.emit('input');
    assert.match(h.fields.password_confirmation.validationMessage, /does not match/);
    assert.equal(h.send().defaultPrevented, true);
    h.fields.password_confirmation.value = h.fields.password.value;
    h.fields.password_confirmation.emit('input');
    assert.equal(h.fields.password_confirmation.validationMessage, '');
    assert.equal(h.send().defaultPrevented, false);
});

test('one submit locks the form; restoring browser history unlocks it', () => {
    const h = harness();
    assert.equal(h.send().defaultPrevented, false);
    assert.equal(h.submit.disabled, true);
    assert.equal(h.form.attributes['aria-busy'], 'true');
    assert.equal(h.send().defaultPrevented, true);
    h.events.get('pageshow')();
    assert.equal(h.submit.disabled, false);
    assert.equal(h.submit.textContent, 'Register');
    assert.equal(h.send().defaultPrevented, false);
});

test('password visibility has accessible state and does not submit the form', () => {
    const h = harness();
    h.toggle.emit('click');
    assert.equal(h.fields.password.type, 'text');
    assert.equal(h.toggle.attributes['aria-pressed'], 'true');
    assert.equal(h.toggle.attributes['aria-label'], 'Hide password');
    assert.equal(h.submit.disabled, false);
    h.toggle.emit('click');
    assert.equal(h.fields.password.type, 'password');
    assert.equal(h.toggle.attributes['aria-pressed'], 'false');
});

test('login supports existing short passwords; cancelled submissions stay interactive', () => {
    const h = harness('login');
    h.fields.password.value = 'secret';
    h.send({ defaultPrevented: true });
    assert.equal(h.submit.disabled, false);
    assert.equal(h.send().defaultPrevented, false);
    assert.equal(h.submit.textContent, 'Signing in…');
});
