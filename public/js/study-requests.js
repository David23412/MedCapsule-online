/** Confirm writes before moving on, and keep a stable identifier for a failed retry. */
(function () {
    'use strict';
    const pending = new Map();
    window.StudyRequests = {
        post(url, body) {
            const identity = JSON.stringify([body.selected_answer, body.mode, body.confidence]);
            let entry = pending.get(url);
            if (!entry || entry.identity !== identity) {
                entry = { identity, body: JSON.stringify(body), key: crypto.randomUUID(), promise: null };
                pending.set(url, entry);
            }
            if (entry.promise) return entry.promise;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 12000);
            entry.promise = (async () => {
                try {
                    const response = await fetch(url, {
                        method: 'POST', signal: controller.signal,
                        headers: {
                            'Accept': 'application/json', 'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Study-Request': entry.key,
                        },
                        body: entry.body,
                    });
                    const payload = await response.json().catch(() => null);
                    if (!response.ok || payload?.success !== true) {
                        throw new Error(payload?.message || 'Could not confirm the save. Please try again.');
                    }
                    if (pending.get(url) === entry) pending.delete(url);
                    return payload;
                } catch (error) {
                    throw new Error(error.name === 'AbortError'
                        ? 'The connection took too long. Your answer is still here. Please try again.'
                        : error.message || 'Could not confirm the save. Please try again.');
                } finally {
                    clearTimeout(timeout);
                    entry.promise = null;
                }
            })();
            return entry.promise;
        },
    };
})();
