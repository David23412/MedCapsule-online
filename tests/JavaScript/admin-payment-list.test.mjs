import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../public/js/admin-payment-list.js', import.meta.url), 'utf8');
function harness() {
    const elements = new Map(), requests = [];
    function $(selector) {
        if (!elements.has(selector)) elements.set(selector, {
            value:'', html(value) {this.value=value; return this;}, empty(){this.value='';return this;},
            append(value) {this.value+=value;return this;}, text(value){this.value=value;return this;},
            find() {return this;}, on(name, fn) {this.click=fn;return this;}, trigger(){},
        });
        return elements.get(selector);
    }
    $.ajax = options => {const request={options, abort(){this.aborted=true; options.error?.({},'abort');}};requests.push(request);return request;};
    const context = vm.createContext({$, document:{}, MANAGE_ACCESS_CONFIG:{csrfToken:'sample'}, console:{error(){}}, Date});
    context.window=context; vm.runInContext(source,context);
    return {context, requests, elements};
}
function response(name='Student', page=1) {return {success:true,
    stats:{}, payments:[{id:3,user_name:name,user_email:'sample@example.test',course_name:'Sample course',
        amount:'100',reference_number:'<img src=x onerror=evil()>',status:'pending',created_at:'2026-10-20'}],
    pagination:{total:10,current_page:page,last_page:10}};}
test('payment list renders names and reference numbers as text, not executable markup', () => {
    const h=harness(); h.context.loadPayments(1, "O'Reilly <svg>");
    h.requests[0].options.success(response('<svg onload=evil()>'));
    const html=h.elements.get('#payments-table-body').value;
    assert.doesNotMatch(html, /<svg|<img/); assert.match(html,/&lt;svg/); assert.match(html,/&lt;img/);
    const pages=h.elements.get('#payment-pagination').value;
    assert.doesNotMatch(pages,/O'Reilly|<svg|onclick/);
});
test('an old response or aborted error cannot replace the latest payment search', () => {
    const h=harness(); h.context.loadPayments(1,'old'); h.context.loadPayments(1,'new');
    assert.equal(h.requests[0].aborted,true);
    h.requests[1].options.success(response('New student'));
    h.requests[0].options.success(response('Old student'));
    assert.match(h.elements.get('#payments-table-body').value,/New student/);
    assert.doesNotMatch(h.elements.get('#payments-table-body').value,/Old student/);
});
test('refresh and pagination preserve the current search and page without embedding search in code', () => {
    const h=harness(); const search="O'Reilly <svg>";
    h.context.loadPayments(4,search); h.requests[0].options.success(response('Student',4));
    let prevented=false;
    h.elements.get('#payment-pagination').click.call({dataset:{paymentPage:'5'}},{preventDefault(){prevented=true;}});
    assert.equal(prevented,true); assert.equal(h.requests[1].options.data.page,5);
    assert.equal(h.requests[1].options.data.search,search);
    h.context.loadPayments(); assert.equal(h.requests[2].options.data.page,5);
    assert.equal(h.requests[2].options.data.search,search);
    assert.equal(h.requests[2].options.timeout,12000);
});
