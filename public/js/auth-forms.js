(function () {
    function normalizePhone(value) {
        let digits = value.replace(/[٠-٩۰-۹]/g, digit => {
            const code = digit.charCodeAt(0);
            return String(code >= 0x06f0 ? code - 0x06f0 : code - 0x0660);
        }).replace(/\D/g, '');
        if (digits.startsWith('0020') && digits.length === 14) digits = digits.slice(2);
        if (digits.startsWith('20') && digits.length === 12) digits = '0' + digits.slice(2);
        return digits;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(button => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            button.addEventListener('click', function () {
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(visible));
                button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
                const icon = button.querySelector('i');
                icon?.classList.toggle('fa-eye', !visible);
                icon?.classList.toggle('fa-eye-slash', visible);
            });
        });

        document.querySelectorAll('[data-auth-form]').forEach(form => {
            const phone = form.querySelector('[name="number"]');
            const password = form.querySelector('[name="password"]');
            const confirmation = form.querySelector('[name="password_confirmation"]');
            const submit = form.querySelector('button[type="submit"]');
            let submitting = false;
            const originalText = submit?.textContent;

            function validateConfirmation() {
                confirmation?.setCustomValidity(confirmation.value && confirmation.value !== password.value
                    ? 'Password confirmation does not match.' : '');
            }

            function validatePhone() {
                if (!phone) return;
                phone.value = normalizePhone(phone.value);
                phone.setCustomValidity(phone.value && !/^01[0125][0-9]{8}$/.test(phone.value)
                    ? 'Enter an 11-digit Egyptian mobile number starting with 010, 011, 012, or 015.' : '');
            }

            phone?.addEventListener('blur', validatePhone);
            phone?.addEventListener('input', () => phone.setCustomValidity(''));
            password?.addEventListener('input', validateConfirmation);
            confirmation?.addEventListener('input', validateConfirmation);

            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) return;
                if (submitting) { event.preventDefault(); return; }
                validatePhone();
                validateConfirmation();
                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }
                submitting = true;
                form.setAttribute('aria-busy', 'true');
                if (submit) {
                    submit.disabled = true;
                    submit.textContent = form.dataset.authForm === 'register' ? 'Creating account…' : 'Signing in…';
                }
            });

            // A restored back/forward page must not retain a disabled submit button.
            window.addEventListener('pageshow', function () {
                submitting = false;
                form.removeAttribute('aria-busy');
                if (submit) { submit.disabled = false; submit.textContent = originalText; }
            });
            form.querySelector('[aria-invalid="true"]')?.focus();
        });
    });
})();
