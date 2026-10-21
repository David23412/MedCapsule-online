(function () {
    const overlay = document.getElementById('delete-confirmation');
    if (!overlay) return;
    const status = document.getElementById('delete-controls-status');
    if (!window.bootstrap) {
        if (status) status.textContent = 'Deletion controls could not load. Refresh the page to try again.';
        return;
    }
    const modal = new bootstrap.Modal(overlay);
    const confirmation = document.getElementById('confirm-permanent-delete');
    const confirmButton = document.getElementById('confirm-delete-button');
    const selected = Array.from(document.querySelectorAll('.delete-question-selection'));
    const selectAll = document.getElementById('select-all-questions');
    const selectedButton = document.getElementById('delete-selected');
    const bulkForm = document.getElementById('bulk-question-delete');
    let activeForm;
    let submitting = false;
    let submitted = false;

    function updateSelection() {
        const count = selected.filter(input => input.checked).length;
        if (selectedButton) selectedButton.disabled = count === 0;
        const label = document.getElementById('delete-selection-count');
        if (label) label.textContent = count;
        if (selectAll) {
            selectAll.checked = count > 0 && count === selected.length;
            selectAll.indeterminate = count > 0 && count < selected.length;
        }
    }
    selectAll?.addEventListener('change', () => {
        selected.forEach(input => input.checked = selectAll.checked);
        updateSelection();
    });
    selected.forEach(input => input.addEventListener('change', updateSelection));
    document.getElementById('delete-course')?.addEventListener('change', event => {
        document.getElementById('delete-topic').value = '';
        event.target.form.requestSubmit();
    });
    document.querySelectorAll('[data-delete-form]').forEach(form => {
        form.addEventListener('submit', event => {
            if (submitting) {
                if (form === activeForm && !submitted) {
                    submitted = true;
                    return;
                }
                event.preventDefault();
                return;
            }
            event.preventDefault();
            if (form === bulkForm && !selected.some(input => input.checked)) return;
            activeForm = form;
            confirmation.checked = false;
            confirmButton.disabled = true;
            const count = selected.filter(input => input.checked).length;
            document.getElementById('delete-confirmation-description').textContent =
                `Permanently delete ${form === bulkForm ? count + ' selected question(s)' : form.dataset.label}? This cannot be undone.`;
            modal.show();
        });
    });
    confirmation.addEventListener('change', () => confirmButton.disabled = !confirmation.checked);
    confirmButton.addEventListener('click', () => {
        if (!activeForm || !confirmation.checked || submitting) return;
        submitting = true;
        confirmButton.disabled = true;
        activeForm.requestSubmit();
    });
    overlay.addEventListener('hidden.bs.modal', () => {
        if (!submitting) activeForm = null;
    });
    document.querySelectorAll('[data-delete-trigger]').forEach(button => button.disabled = false);
    if (status) status.hidden = true;
    updateSelection();
})();
