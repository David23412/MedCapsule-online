(function () {
    'use strict';
    const pending = new Set();

    async function decidePayment(paymentId, approve) {
        const id = String(paymentId);
        if (!/^\d+$/.test(id) || pending.has(id)) return;
        pending.add(id);
        const buttons = document.querySelectorAll(`[data-payment-id="${id}"] button`);
        buttons.forEach(button => button.disabled = true);
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 12000);
        try {
            const response = await fetch(`/admin/payments/${approve ? 'verify' : 'reject'}/${id}`, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: {
                    'X-CSRF-TOKEN': window.MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.status === 419 || response.status === 401) {
                throw new Error('Your session expired. Refresh the page and sign in again.');
            }
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Payment was not updated. Please try again.');
            window.showNotification(approve ? 'success' : 'warning', approve ? 'Payment Accepted' : 'Payment Rejected', data.message);
            // Refresh from the server so the row reflects the committed decision.
            loadPayments();
        } catch (error) {
            window.showNotification('error', 'Payment Not Updated', error.name === 'AbortError'
                ? 'The request timed out. Refresh the payment list to check its status before trying again.'
                : error.message || 'Please try again.');
        } finally {
            clearTimeout(timeout);
            pending.delete(id);
            buttons.forEach(button => button.disabled = false);
        }
    }

    window.acceptPayment = paymentId => decidePayment(paymentId, true);
    window.rejectPayment = paymentId => decidePayment(paymentId, false);
})();
