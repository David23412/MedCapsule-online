(function () {
    'use strict';
    const form = document.getElementById('courseSourceRenameForm');
    if (!form) return;
    const course = document.getElementById('sourceRenameCourse');
    const source = document.getElementById('sourceRenameOriginal');
    const name = document.getElementById('sourceRenameNewName');
    const suggestions = document.getElementById('sourceRenameSuggestions');
    const save = document.getElementById('sourceRenameSave');
    const retry = document.getElementById('sourceRenameRetry');
    const status = document.getElementById('sourceRenameStatus');
    const scope = document.getElementById('sourceRenameScope');
    let generation = 0;
    let controller = null;
    let sources = [];
    let saving = false;
    const defaultScope = scope.textContent;

    function update() {
        const selected = sources.find(item => item.name === source.value);
        const valid = !!selected && !!name.value.trim() && name.value.trim() !== selected.name;
        save.disabled = saving || !valid;
        const destination = sources.find(item => item.name !== selected?.name && item.name.toLowerCase() === name.value.trim().toLowerCase());
        save.textContent = destination ? 'Combine sources' : 'Rename source';
        if (selected) scope.textContent = destination
            ? `Moves ${selected.question_count} questions into ${destination.name} (${destination.question_count} existing questions). Assignments and saved progress are combined. Other courses stay unchanged.`
            : `Renames ${selected.question_count} questions in ${course.selectedOptions[0].textContent}. Assignments and saved progress follow the new name. Other courses stay unchanged.`;
        else scope.textContent = defaultScope;
    }

    async function request(url, options = {}) {
        const timeoutController = new AbortController();
        const timeout = setTimeout(() => timeoutController.abort(), 15000);
        const abort = () => timeoutController.abort();
        if (options.signal?.aborted) abort();
        else options.signal?.addEventListener('abort', abort, {once: true});
        try {
            const response = await fetch(url, {
                ...options, signal: timeoutController.signal, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            let result;
            try { result = await response.json(); }
            catch (_error) { throw new Error('Could not read the response. Reload sources and try again.'); }
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Could not save. Please try again.');
            return result;
        } finally { clearTimeout(timeout); options.signal?.removeEventListener('abort', abort); }
    }

    async function loadSources(message = '') {
        const current = ++generation;
        controller?.abort();
        controller = new AbortController();
        sources = [];
        suggestions.replaceChildren();
        source.replaceChildren(new Option(course.value ? 'Loading sources…' : 'Choose a course first', ''));
        source.disabled = true;
        name.disabled = true;
        name.value = '';
        save.disabled = true;
        retry.hidden = true;
        status.textContent = message;
        scope.textContent = defaultScope;
        if (!course.value) return;
        try {
            const result = await request(`${form.dataset.baseUrl}/${encodeURIComponent(course.value)}/sources`, {signal: controller.signal});
            if (current !== generation) return;
            sources = result.sources;
            suggestions.replaceChildren(...sources.map(item => new Option(item.name, item.name)));
            source.replaceChildren(new Option(sources.length ? 'Choose a source' : 'No named sources in this course', ''));
            sources.forEach(item => source.add(new Option(`${item.name} (${item.question_count} questions)`, item.name)));
            source.disabled = !sources.length;
            name.disabled = !sources.length;
        } catch (error) {
            if (current !== generation) return;
            source.replaceChildren(new Option('Could not load sources', ''));
            status.textContent = 'Could not load sources. Please try again.';
            retry.hidden = false;
        }
    }

    course.addEventListener('change', () => loadSources());
    source.addEventListener('change', update);
    name.addEventListener('input', update);
    retry.addEventListener('click', () => loadSources());
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled || !form.reportValidity()) return;
        const old = source.value;
        const newName = name.value.trim();
        saving = true;
        course.disabled = source.disabled = name.disabled = save.disabled = true;
        status.textContent = 'Renaming source…';
        try {
            const result = await request(`${form.dataset.baseUrl}/${encodeURIComponent(course.value)}/sources/rename`, {
                method: 'POST', body: JSON.stringify({source: old, new_name: newName})
            });
            await loadSources(result.message);
        } catch (error) {
            status.textContent = error.name === 'AbortError' ? 'Could not confirm the rename. Reload sources before trying again.' : error.message;
            retry.hidden = false;
        } finally {
            saving = false;
            course.disabled = false;
            source.disabled = name.disabled = !sources.length;
            update();
        }
    });
})();
