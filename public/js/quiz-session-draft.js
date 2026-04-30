(function () {
    const STORAGE_PREFIX = 'medcapsule.quiz-draft.';
    const MAX_DRAFT_AGE_MS = 1000 * 60 * 30; // 30 minutes of inactivity
    const ACTIVITY_THROTTLE_MS = 1000 * 15;
    const PRUNE_THROTTLE_MS = 1000 * 60;

    function noop() {}

    function normalizeQuestionIds(questionIds) {
        return Array.isArray(questionIds)
            ? questionIds.map(id => String(id)).filter(Boolean)
            : [];
    }

    function isExpiredTimestamp(timestamp, maxAgeMs = MAX_DRAFT_AGE_MS) {
        const normalizedTimestamp = Number(timestamp || 0);
        return !normalizedTimestamp || (Date.now() - normalizedTimestamp) > maxAgeMs;
    }

    function readDraft(storageKey, version, maxAgeMs = MAX_DRAFT_AGE_MS) {
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }

            const parsed = JSON.parse(raw);
            if (!parsed || parsed.version !== version) {
                localStorage.removeItem(storageKey);
                return null;
            }

            const lastActivityAt = Number(parsed.lastActivityAt || parsed.savedAt || 0);
            if (isExpiredTimestamp(lastActivityAt, maxAgeMs)) {
                localStorage.removeItem(storageKey);
                return null;
            }

            return parsed;
        } catch (_error) {
            try {
                localStorage.removeItem(storageKey);
            } catch (_cleanupError) {
                // Ignore cleanup failures.
            }
            return null;
        }
    }

    function pruneExpiredDrafts() {
        try {
            for (let index = 0; index < localStorage.length; index += 1) {
                const storageKey = localStorage.key(index);
                if (!storageKey || !storageKey.startsWith(STORAGE_PREFIX)) {
                    continue;
                }

                const raw = localStorage.getItem(storageKey);
                if (!raw) {
                    continue;
                }

                const parsed = JSON.parse(raw);
                const lastActivityAt = Number(parsed?.lastActivityAt || parsed?.savedAt || 0);
                if (isExpiredTimestamp(lastActivityAt)) {
                    localStorage.removeItem(storageKey);
                }
            }
        } catch (_error) {
            // Ignore localStorage parsing failures.
        }
    }

    function create(options = {}) {
        const draftKey = String(options.key || `${window.location.pathname}${window.location.search}`);
        const storageKey = `${STORAGE_PREFIX}${draftKey}`;
        const version = String(options.version || '1');
        const questionIds = normalizeQuestionIds(options.questionIds);
        const maxAgeMs = Number(options.maxAgeMs || MAX_DRAFT_AGE_MS);
        const onExpire = typeof options.onExpire === 'function' ? options.onExpire : noop;
        const activityEvents = Array.isArray(options.activityEvents) && options.activityEvents.length > 0
            ? options.activityEvents
            : ['pointerdown', 'keydown', 'input', 'scroll', 'visibilitychange'];
        let lastPersistedAt = 0;
        let lastTouchedAt = 0;
        let lastPrunedAt = 0;
        let expireTimerId = null;
        let isExpired = false;

        function clear() {
            try {
                localStorage.removeItem(storageKey);
            } catch (_error) {
                // Ignore localStorage clear failures.
            }
        }

        function expireDraft() {
            if (isExpired) {
                return;
            }

            isExpired = true;
            clear();
            if (expireTimerId) {
                window.clearInterval(expireTimerId);
                expireTimerId = null;
            }
            onExpire();
        }

        function loadRecord() {
            return readDraft(storageKey, version, maxAgeMs);
        }

        function save(state) {
            try {
                const now = Date.now();
                if (now - lastPersistedAt < 250) {
                    return true;
                }

                if ((now - lastPrunedAt) >= PRUNE_THROTTLE_MS) {
                    pruneExpiredDrafts();
                    lastPrunedAt = now;
                }
                const existingDraft = loadRecord();
                const startedAt = Number(
                    (state && state.startedAt) ||
                    existingDraft?.state?.startedAt ||
                    now
                );

                localStorage.setItem(storageKey, JSON.stringify({
                    version,
                    savedAt: now,
                    lastActivityAt: now,
                    questionIds,
                    state: Object.assign({}, state || {}, {
                        startedAt
                    })
                }));
                lastPersistedAt = now;
                lastTouchedAt = now;
                return true;
            } catch (_error) {
                return false;
            }
        }

        function load() {
            return loadRecord()?.state || null;
        }

        function touch(forcePersist = false) {
            try {
                const now = Date.now();
                if (!forcePersist && now - lastTouchedAt < ACTIVITY_THROTTLE_MS) {
                    return true;
                }

                const existingDraft = loadRecord();
                if (!existingDraft) {
                    lastTouchedAt = now;
                    return true;
                }

                existingDraft.savedAt = now;
                existingDraft.lastActivityAt = now;
                localStorage.setItem(storageKey, JSON.stringify(existingDraft));
                lastTouchedAt = now;
                lastPersistedAt = now;
                return true;
            } catch (_error) {
                return false;
            }
        }

        function bindActivityTracking() {
            const handleActivity = (event) => {
                if (event?.type === 'visibilitychange' && document.visibilityState !== 'visible') {
                    return;
                }

                touch(event?.type === 'visibilitychange');
            };

            activityEvents.forEach((eventName) => {
                window.addEventListener(eventName, handleActivity, { passive: true });
            });

            window.addEventListener('pagehide', () => {
                touch(true);
            });
        }

        function startExpiryMonitor() {
            if (expireTimerId) {
                return;
            }

            expireTimerId = window.setInterval(() => {
                const draft = loadRecord();
                if (!draft) {
                    return;
                }

                const lastActivityAt = Number(draft.lastActivityAt || draft.savedAt || 0);
                if (isExpiredTimestamp(lastActivityAt, maxAgeMs)) {
                    expireDraft();
                }
            }, 1000 * 30);
        }

        function ensureSeedDraft() {
            if (loadRecord()) {
                return;
            }

            save({});
        }

        bindActivityTracking();
        ensureSeedDraft();
        startExpiryMonitor();

        return {
            key: draftKey,
            storageKey,
            load,
            save,
            clear,
            touch,
            expire: expireDraft
        };
    }

    window.QuizSessionDraft = {
        create
    };
})();
