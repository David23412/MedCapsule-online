(function () {
    document.querySelectorAll('[data-json-form]').forEach(form => {
        let pending = false;
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (pending) return;
            pending = true;
            const errorBox = form.querySelector('[data-form-error]');
            const buttons = Array.from(form.querySelectorAll('button'));
            const payload = new FormData(form);
            // Empty personal extensions preserve the current deadline.
            for (const [key, value] of Array.from(payload.entries())) {
                if (key.startsWith('user_extensions[') && value === '') payload.delete(key);
            }
            buttons.forEach(button => button.disabled = true);
            errorBox.hidden = true;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 12000);
            try {
                const response = await fetch(form.action, {method:'POST', body:payload, signal:controller.signal,
                    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Changes could not be saved. Please try again.');
                if (form.dataset.successUrl) {
                    window.location.assign(form.dataset.successUrl);
                } else {
                    errorBox.className = 'alert alert-success';
                    errorBox.textContent = data.message || 'Changes saved.';
                    errorBox.hidden = false;
                }
            } catch (error) {
                errorBox.textContent = error.name === 'AbortError' ? 'The request timed out. Check the saved status before trying again.' : error.message;
                errorBox.hidden = false;
                pending = false;
                buttons.forEach(button => button.disabled = false);
            } finally {
                clearTimeout(timeout);
            }
        });
    });
})();
