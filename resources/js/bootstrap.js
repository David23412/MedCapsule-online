import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const loginFallbackUrl = '/';
let authRedirectInProgress = false;

function resolveRedirectUrl(payload) {
    if (payload && typeof payload.redirect === 'string' && payload.redirect.trim() !== '') {
        return payload.redirect;
    }

    return loginFallbackUrl;
}

function redirectToLogin(redirectUrl) {
    if (authRedirectInProgress) {
        return;
    }

    authRedirectInProgress = true;
    window.location.replace(redirectUrl || loginFallbackUrl);
}

async function handleFetchAuthFailure(response) {
    if (response.status !== 401 && response.status !== 419) {
        return;
    }

    let redirectUrl = loginFallbackUrl;
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        try {
            const payload = await response.clone().json();
            redirectUrl = resolveRedirectUrl(payload);
        } catch (_error) {
            redirectUrl = loginFallbackUrl;
        }
    }

    redirectToLogin(redirectUrl);
}

// Shared global entrypoint used by page-level scripts if needed.
window.__medcapsuleHandleAuthFailure = function handleAuthFailure(payload = null) {
    const redirectUrl = resolveRedirectUrl(payload);
    redirectToLogin(redirectUrl);
};

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;
        if (status === 401 || status === 419) {
            window.__medcapsuleHandleAuthFailure(error.response?.data ?? null);
        }

        return Promise.reject(error);
    }
);

const originalFetch = window.fetch.bind(window);
window.fetch = async (...args) => {
    const response = await originalFetch(...args);
    handleFetchAuthFailure(response);
    return response;
};

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

// import './echo';
