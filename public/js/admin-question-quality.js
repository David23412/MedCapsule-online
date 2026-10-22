(() => {
    'use strict';
    const init = () => {
        const panel = document.getElementById('question-quality');
        if (!panel) return;
        const byId = id => document.getElementById(id);
        const config = window.MANAGE_ACCESS_CONFIG;
        const results = byId('quality-results');
        const summary = byId('quality-summary');
        const form = byId('quality-edit-form');
        const editor = byId('quality-editor');
        document.body.append(editor);
        const labels = {broken_answer: 'Broken answer', repeated_explanation: 'Answer-only explanation'};
        let page = 1, lastPage = 1, rows = [], selected = null, activeRequest = null, requestId = 0;
        const text = (tag, value, classes = '') => {
            const node = document.createElement(tag);
            node.textContent = value;
            node.className = classes;
            return node;
        };
        async function jsonRequest(url, options = {}) {
            const response = await fetch(url, {...options, headers: {
                Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken, ...options.headers
            }});
            const data = await response.json();
            if (!response.ok || data.success === false) {
                throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Request failed. Please retry.');
            }
            return data;
        }
        function render() {
            results.replaceChildren();
            if (!rows.length) results.append(text('p', 'No matching issues found.', 'text-muted py-3'));
            rows.forEach(row => {
                const card = text('article', '', 'border rounded p-3 mb-3');
                card.style.overflowWrap = 'anywhere';
                card.append(text('p', `#${row.id} · ${row.course_name || 'No course'} · ${row.topic_name || 'No topic'}`, 'small text-muted mb-2'));
                row.issues.forEach(issue => {
                    const flag = text('strong', labels[issue], 'd-block mb-2');
                    flag.style.color = issue === 'broken_answer' ? '#b42318' : '#855400';
                    card.append(flag);
                });
                card.append(text('h3', row.question, 'h6'));
                if (row.question_type !== 'written') {
                    ['a','b','c','d','e'].forEach(letter => {
                        if (row[`option_${letter}`]) card.append(text('div', `${letter.toUpperCase()}. ${row[`option_${letter}`]}`, 'small mb-1'));
                    });
                }
                card.append(text('p', `Saved answer: ${row.correct_answer || '(empty)'}`, 'fw-bold mt-2 mb-2'));
                card.append(text('p', `Explanation: ${row.explanation || '(empty)'}`, 'mb-3'));
                const edit = text('button', 'Edit question', 'btn btn-outline-primary');
                edit.type = 'button';
                edit.addEventListener('click', () => openEditor(row));
                card.append(edit);
                results.append(card);
            });
        }
        async function load() {
            activeRequest?.abort();
            const controller = new AbortController();
            activeRequest = controller;
            const current = ++requestId;
            const timeout = setTimeout(() => controller.abort(), 30000);
            results.setAttribute('aria-busy', 'true');
            summary.textContent = 'Checking questions…';
            byId('quality-prev').disabled = byId('quality-next').disabled = true;
            const url = new URL(config.routes.questionQuality, window.location.href);
            url.searchParams.set('page', page);
            url.searchParams.set('issue', byId('quality-issue').value);
            if (byId('quality-course').value) url.searchParams.set('course_id', byId('quality-course').value);
            try {
                const data = await jsonRequest(url, {signal: controller.signal});
                if (current !== requestId) return;
                lastPage = data.last_page;
                if (page > lastPage) { page = lastPage; return load(); }
                rows = data.items;
                summary.textContent = `${data.counts.broken_answer} broken answers · ${data.counts.repeated_explanation} answer-only explanations · ${data.total} questions match this filter.`;
                byId('quality-page').textContent = `Page ${page} of ${lastPage}`;
                render();
                byId('quality-prev').disabled = page <= 1;
                byId('quality-next').disabled = page >= lastPage;
            } catch (error) {
                if (current !== requestId) return;
                results.replaceChildren();
                summary.textContent = error.name === 'AbortError' ? 'The check timed out. Refresh to retry.' : error.message;
                byId('quality-page').textContent = '';
            } finally {
                clearTimeout(timeout);
                if (current === requestId) results.setAttribute('aria-busy', 'false');
            }
        }
        function openEditor(row) {
            selected = row;
            byId('quality-editor-title').textContent = `Edit question #${row.id}`;
            byId('quality-question').value = row.question || '';
            byId('quality-explanation').value = row.explanation || '';
            ['a','b','c','d','e'].forEach(letter => { byId(`quality-option-${letter}`).value = row[`option_${letter}`] || ''; });
            const written = row.question_type === 'written';
            byId('quality-options').hidden = written;
            byId('quality-written-fields').hidden = !written;
            byId('quality-answer').required = !written;
            byId('quality-written-answer').required = written;
            byId('quality-answer').value = (row.correct_answer || '').trim().toUpperCase();
            byId('quality-written-answer').value = row.correct_answer || '';
            byId('quality-edit-error').classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(editor).show();
        }
        let saving = false;
        editor.addEventListener('hide.bs.modal', event => { if (saving) event.preventDefault(); });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving || !selected) return;
            const payload = Object.fromEntries(new FormData(form));
            payload.question_type = selected.question_type === 'written' ? 'written' : 'multiple_choice';
            payload.correct_answer = byId(payload.question_type === 'written' ? 'quality-written-answer' : 'quality-answer').value;
            saving = true;
            byId('quality-save').disabled = true;
            byId('quality-save').textContent = 'Saving…';
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                await jsonRequest(selected.update_url, {method: 'PUT', body: JSON.stringify(payload), signal: controller.signal});
                saving = false;
                bootstrap.Modal.getOrCreateInstance(editor).hide();
                window.showNotification?.('success', 'Question saved');
                await load();
            } catch (error) {
                byId('quality-edit-error').textContent = error.name === 'AbortError' ? 'Save could not be confirmed. Refresh the list before retrying.' : error.message;
                byId('quality-edit-error').classList.remove('d-none');
            } finally {
                clearTimeout(timeout);
                saving = false;
                byId('quality-save').disabled = false;
                byId('quality-save').textContent = 'Save changes';
            }
        });
        ['quality-course','quality-issue'].forEach(id => byId(id).addEventListener('change', () => { page = 1; load(); }));
        byId('quality-refresh').addEventListener('click', () => load());
        byId('quality-prev').addEventListener('click', () => { if (page > 1) { page--; load(); } });
        byId('quality-next').addEventListener('click', () => { if (page < lastPage) { page++; load(); } });
        byId('question-quality-tab').addEventListener('shown.bs.tab', () => load());
        if (config.activeTab === 'question-quality') load();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
