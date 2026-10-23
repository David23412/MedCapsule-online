(() => {
    'use strict';
    window.TopicSourceSelection = {
        createLoader({load, onLoading, onLoaded, onError}) {
            let generation = 0, controller;
            return {
                async request(topicId, mode) {
                    controller?.abort();
                    const current = ++generation;
                    const requestController = new AbortController();
                    controller = requestController;
                    const signal = requestController.signal;
                    const timeout = setTimeout(() => requestController.abort(), 15000);
                    onLoading();
                    try {
                        const data = await load(topicId, mode === 'read' ? 'quiz' : mode, signal);
                        if (current !== generation) return;
                        if (!Array.isArray(data.availableSources) || !data.progress || typeof data.progress !== 'object') {
                            throw new Error('Invalid source response');
                        }
                        onLoaded(data);
                    } catch (error) {
                        if (current === generation) onError(error);
                    } finally {
                        clearTimeout(timeout);
                    }
                },
                cancel() { generation++; controller?.abort(); }
            };
        },
        quizUrl({courseId, topicId, mode, sources, tutorScope}) {
            const suffix = mode === 'tutor' ? '/tutor' : mode === 'read' ? '/read' : '';
            const params = new URLSearchParams();
            sources.forEach(source => params.append('sources[]', source));
            if (mode === 'tutor') params.set('tutor_scope', tutorScope || 'full');
            return `/quiz/${encodeURIComponent(courseId)}/${encodeURIComponent(topicId)}${suffix}?${params}`;
        }
    };
})();
