(function () {
    const STORAGE_PREFIX = 'medcapsule.quiz-draft.';
    const MAX_DRAFT_AGE_MS = 1000 * 60 * 30; // 30 minutes of inactivity

    function normalizeQuestionIds(questionIds) {
        return Array.isArray(questionIds)
            ? questionIds.map(id => String(id)).filter(Boolean)
            : [];
    }

    function pruneExpiredDrafts() {
        try {
            const now = Date.now();
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
                const savedAt = Number(parsed?.savedAt || 0);
                if (!savedAt || (now - savedAt) > MAX_DRAFT_AGE_MS) {
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
        let lastPersistedAt = 0;

        function save(state) {
            try {
                const now = Date.now();
                if (now - lastPersistedAt < 250) {
                    return true;
                }

                pruneExpiredDrafts();
                localStorage.setItem(storageKey, JSON.stringify({
                    version,
                    savedAt: now,
                    questionIds,
                    state: state || {}
                }));
                lastPersistedAt = now;
                return true;
            } catch (_error) {
                return false;
            }
        }

        function load() {
            try {
                const raw = localStorage.getItem(storageKey);
                if (!raw) {
                    return null;
                }

                const parsed = JSON.parse(raw);
                if (!parsed || parsed.version !== version) {
                    return null;
                }

                return parsed.state || null;
            } catch (_error) {
                return null;
            }
        }

        function clear() {
            try {
                localStorage.removeItem(storageKey);
            } catch (_error) {
                // Ignore localStorage clear failures.
            }
        }

        return {
            key: draftKey,
            storageKey,
            load,
            save,
            clear
        };
    }

    window.QuizSessionDraft = {
        create
    };
})();
