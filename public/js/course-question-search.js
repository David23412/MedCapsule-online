(function() {
    const searchInput = document.getElementById('courseQuestionSearchInput');
    const resultsContainer = document.getElementById('searchQuestionsResults');
    const spinner = document.getElementById('searchSpinner');
    const courseId = window.TopicsPageConfig.courseId;
    const canEditQuestions = window.TopicsPageConfig.canEditQuestions;
    const searchQuestionData = new Map();
    let searchTimeout;
    let searchVersion = 0;
    let searchController;
    let editorBusy = false;
    let closeTimer;
    const pendingBookmarks = new Set();
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));

    async function requestJson(url, options = {}, controller = new AbortController()) {
        const timeout = setTimeout(() => controller.abort(), 12000);
        try {
            const response = await fetch(url, {...options, signal: controller.signal,
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...options.headers}});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Request failed. Please try again.');
            return data;
        } finally {
            clearTimeout(timeout);
        }
    }

    function setEditorBusy(busy) {
        editorBusy = busy;
        document.querySelectorAll('#sq-edit-overlay input, #sq-edit-overlay textarea, #sq-edit-overlay button').forEach(control => control.disabled = busy);
    }
    function scheduleClose(delay) {
        closeTimer = setTimeout(() => { setEditorBusy(false); sqCloseModal(); }, delay);
    }

    if (!searchInput || !resultsContainer) return;
    searchInput.maxLength = 200;

    if (!canEditQuestions) {
        resultsContainer.classList.add('search-results-protected');
    }

    const clearSearchSelection = () => {
        const selection = window.getSelection ? window.getSelection() : null;
        if (!selection || selection.isCollapsed) {
            return;
        }

        selection.removeAllRanges();
    };

    const isProtectedSearchTarget = (target) => {
        return target instanceof HTMLElement
            && Boolean(target.closest('#searchQuestionsResults'))
            && !target.closest('button, input, textarea, select, option, [contenteditable=""], [contenteditable="true"]');
    };

    if (!canEditQuestions) {
        ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach((eventName) => {
            resultsContainer.addEventListener(eventName, (event) => {
                if (!isProtectedSearchTarget(event.target)) {
                    return;
                }

                if (eventName === 'copy' || eventName === 'cut') {
                    try {
                        event.clipboardData?.setData('text/plain', 'Copying course search content is disabled.');
                    } catch (error) {
                        // Ignore clipboard access failures.
                    }
                }

                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation?.();
                clearSearchSelection();
            }, true);
        });

        resultsContainer.addEventListener('keydown', (event) => {
            const key = String(event.key || '').toLowerCase();
            const withModifier = event.ctrlKey || event.metaKey;

            if (withModifier && ['a', 'c', 'x'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation?.();
                clearSearchSelection();
            }
        }, true);

        resultsContainer.addEventListener('mouseup', clearSearchSelection, true);
        resultsContainer.addEventListener('touchend', clearSearchSelection, true);
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);
        const version = ++searchVersion;
        searchController?.abort();
        if (query.length < 2) {
            spinner.style.display = 'none';
            searchQuestionData.clear();
            resultsContainer.innerHTML = `
                <div class="search-results-placeholder">
                    <i class="fas fa-keyboard mb-2"></i>
                    <p>Start typing to search keywords, concepts, or question text</p>
                </div>
            `;
            return;
        }

        spinner.style.display = 'block';
        searchTimeout = setTimeout(() => performSearch(query, version), 350);
    });

    async function performSearch(query, version) {
        const controller = new AbortController();
        searchController = controller;
        try {
            const data = await requestJson(`/courses/${courseId}/search-questions?query=${encodeURIComponent(query)}`, {}, controller);
            if (version !== searchVersion) return;
            
            spinner.style.display = 'none';

            if (!data.success || !data.questions || data.questions.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-results-placeholder">
                        <i class="fas fa-search-minus mb-2"></i>
                        <p>No questions found matching "${escapeHtml(query)}"</p>
                    </div>
                `;
                return;
            }

            searchQuestionData.clear();
            data.questions.forEach((question) => {
                searchQuestionData.set(String(question.id), question);
            });

            resultsContainer.innerHTML = data.questions.map(q => `
                <div class="search-result-card" data-question-id="${escapeHtml(q.id)}">
                    <div class="search-result-content">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="search-result-question flex-grow-1 me-3">${escapeHtml(q.question)}</div>
                            <div class="search-result-status-action d-flex align-items-start gap-2">
                                ${canEditQuestions
                                    ? `<button class="btn btn-outline-primary btn-sm search-edit-question-btn" data-id="${escapeHtml(q.id)}" title="Edit Question"><i class="fas fa-pen"></i></button>`
                                    : ''
                                }
                                ${q.is_already_mistake
                                    ? '<div class="mistake-status-pill"><i class="fas fa-check-circle"></i></div>'
                                    : `<button class="btn add-mistake-minimal-btn" data-id="${escapeHtml(q.id)}" title="Add to Mistakes"><i class="fas fa-plus"></i></button>`
                                }
                            </div>
                        </div>
                        <div class="search-result-answer mb-3">
                            <span class="badge bg-success-subtle text-success me-3" style="align-self: flex-start; margin-top: 5px;">Correct Answer</span>
                            <div class="fw-semibold text-dark search-result-answer-text">${escapeHtml(q.correct_answer)}</div>
                        </div>
                        <div class="search-result-explanation">
                            ${escapeHtml(q.explanation)}
                        </div>
                        ${q.topic_titles && q.topic_titles.length > 0 ? `
                            <div class="search-result-footer d-flex justify-content-end mt-3">
                                <div class="search-result-meta d-flex gap-2 flex-wrap justify-content-end">
                                    ${q.topic_titles.map(title => `<span class="source-tag">${escapeHtml(title)}</span>`).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');

            resultsContainer.querySelectorAll('.add-mistake-minimal-btn').forEach(btn => {
                btn.disabled = pendingBookmarks.has(btn.dataset.id);
                btn.addEventListener('click', function() {
                    const qId = this.dataset.id;
                    addQuestionToMistakes(qId, this);
                });
            });

            if (canEditQuestions) {
                resultsContainer.querySelectorAll('.search-edit-question-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        openSearchQuestionEditModal(this.dataset.id);
                    });
                });
            }

        } catch (error) {
            if (version !== searchVersion) return;
            console.error('Search error:', error);
            spinner.style.display = 'none';
            resultsContainer.innerHTML = '<div class="alert alert-danger">Search failed. Please try again.</div>';
        }
    }

    async function addQuestionToMistakes(qId, button) {
        if (pendingBookmarks.has(qId)) return;
        pendingBookmarks.add(qId);
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        try {
            const data = await requestJson(`/questions/${qId}/add-mistake`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const question = searchQuestionData.get(String(qId));
            if (question) question.is_already_mistake = true;
            resultsContainer.querySelectorAll(`.add-mistake-minimal-btn[data-id="${qId}"]`).forEach(current => {
                current.outerHTML = '<div class="mistake-status-pill"><i class="fas fa-check-circle"></i></div>';
            });
            showToast('Mistake Added', data.message, 'success');
        } catch (error) {
            console.error('Add mistake error:', error);
            button.disabled = false;
            button.innerHTML = originalContent;
            showToast('Error', error.message || 'An error occurred. Please try again.', 'error');
        } finally {
            pendingBookmarks.delete(qId);
            resultsContainer.querySelectorAll(`.add-mistake-minimal-btn[data-id="${qId}"]`).forEach(current => {
                current.disabled = false;
            });
        }
    }

    function showToast(title, message, type) {
        const toastStack = document.getElementById('taskToastStack');
        if (!toastStack) {
            alert(message);
            return;
        }

        const toast = document.createElement('div');
        toast.className = `task-toast ${type === 'error' ? 'toast-error' : ''}`;
        toast.innerHTML = `
            <div class="task-toast-icon">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check'}"></i>
            </div>
            <div class="task-toast-copy">
                <span class="task-toast-kicker">${escapeHtml(title)}</span>
                <strong class="task-toast-title">${escapeHtml(message)}</strong>
            </div>
        `;

        toastStack.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        setTimeout(() => {
            toast.classList.remove('is-visible');
            setTimeout(() => toast.remove(), 260);
        }, 3000);
    }

    if (!canEditQuestions) {
        return;
    }

    const editBaseUrl = '/quiz/tutor/question/';
    const editCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    window.openSearchQuestionEditModal = function(qId) {
        if (editorBusy) return;
        clearTimeout(closeTimer);
        setEditorBusy(false);
        document.getElementById('sq-confirm-del').innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
        document.querySelectorAll('input[name="sq-correct"]').forEach(radio => radio.checked = false);
        const data = searchQuestionData.get(String(qId));
        if (!data) {
            showToast('Error', 'Question data not available.', 'error');
            return;
        }

        ['a', 'b', 'c', 'd', 'e'].forEach(letter => {
            document.getElementById('sq-o' + letter).value = data['option_' + letter] || '';
        });
        document.getElementById('sq-correct-text').value = data.question_type === 'written' ? data.raw_correct_answer || '' : '';
        document.getElementById('sq-alt-answers').value = data.alternative_answers || '';
        document.getElementById('sq-q-id').value = qId;
        document.getElementById('sq-q-text').value = data.question || '';
        document.getElementById('sq-explanation').value = data.explanation === 'No explanation available.' ? '' : (data.explanation || '');
        document.getElementById('sq-status').textContent = '';
        document.getElementById('sq-status').className = 'sq-status';
        document.getElementById('sq-del-confirm').classList.remove('show');
        document.getElementById('sq-save-btn').disabled = false;
        document.getElementById('sq-del-btn').style.display = '';

        sqSwitchType(data.question_type === 'written' ? 'written' : 'multiple_choice', true, data);

        document.getElementById('sq-edit-overlay').classList.add('show');
        document.getElementById('sq-edit-overlay').setAttribute('aria-hidden', 'false');
        document.getElementById('sq-q-text').focus();
        document.body.style.overflow = 'hidden';
    };

    window.sqSwitchType = function(type, populate, data) {
        if (editorBusy) return;
        document.getElementById('sq-q-type').value = type;
        const mcqButton = document.getElementById('sq-type-mcq');
        const writtenButton = document.getElementById('sq-type-written');
        const mcqSection = document.getElementById('sq-mcq-section');
        const writtenSection = document.getElementById('sq-written-section');

        if (type === 'written') {
            mcqButton.classList.remove('active');
            writtenButton.classList.add('active');
            mcqSection.style.display = 'none';
            writtenSection.style.display = 'block';

            if (populate && data) {
                document.getElementById('sq-correct-text').value = data.raw_correct_answer || '';
                document.getElementById('sq-alt-answers').value = data.alternative_answers || '';
            }
            return;
        }

        writtenButton.classList.remove('active');
        mcqButton.classList.add('active');
        writtenSection.style.display = 'none';
        mcqSection.style.display = 'block';

        if (populate && data) {
            document.getElementById('sq-oa').value = data.option_a || '';
            document.getElementById('sq-ob').value = data.option_b || '';
            document.getElementById('sq-oc').value = data.option_c || '';
            document.getElementById('sq-od').value = data.option_d || '';
            document.getElementById('sq-oe').value = data.option_e || '';
            const radio = document.getElementById('sq-c' + String(data.raw_correct_answer || '').toLowerCase());
            if (radio) {
                radio.checked = true;
            }
        }
    };

    window.sqCloseModal = function() {
        if (editorBusy) return;
        document.getElementById('sq-edit-overlay').classList.remove('show');
        document.getElementById('sq-edit-overlay').setAttribute('aria-hidden', 'true');
        searchInput.focus();
        clearTimeout(closeTimer);
        document.body.style.overflow = document.querySelector('.modal.show') ? 'hidden' : '';
    };

    window.sqPromptDelete = function() {
        if (editorBusy) return;
        document.getElementById('sq-del-confirm').classList.add('show');
        document.getElementById('sq-save-btn').disabled = true;
        document.getElementById('sq-del-btn').style.display = 'none';
    };

    window.sqCancelDelete = function() {
        if (editorBusy) return;
        document.getElementById('sq-del-confirm').classList.remove('show');
        document.getElementById('sq-save-btn').disabled = false;
        document.getElementById('sq-del-btn').style.display = '';
    };

    window.sqSaveEdit = async function() {
        if (editorBusy) return;
        const qId = document.getElementById('sq-q-id').value;
        const qType = document.getElementById('sq-q-type').value;
        const saveBtn = document.getElementById('sq-save-btn');
        const questionText = document.getElementById('sq-q-text').value.trim();

        if (!questionText) {
            sqSetStatus('Question text is required.', 'error');
            return;
        }

        let payload = {
            _method: 'PUT',
            question: questionText,
            question_type: qType,
            explanation: document.getElementById('sq-explanation').value.trim()
        };

        if (qType === 'written') {
            const correctAnswer = document.getElementById('sq-correct-text').value.trim();
            if (!correctAnswer) {
                sqSetStatus('Correct answer is required.', 'error');
                return;
            }

            payload.correct_answer = correctAnswer;
            payload.alternative_answers = document.getElementById('sq-alt-answers').value.trim();
        } else {
            const checked = document.querySelector('input[name="sq-correct"]:checked');
            if (!checked) {
                sqSetStatus('Please select the correct answer.', 'error');
                return;
            }

            payload.correct_answer = checked.value;
            payload.option_a = document.getElementById('sq-oa').value.trim();
            payload.option_b = document.getElementById('sq-ob').value.trim();
            payload.option_c = document.getElementById('sq-oc').value.trim();
            payload.option_d = document.getElementById('sq-od').value.trim();
            payload.option_e = document.getElementById('sq-oe').value.trim();
            if (!payload['option_' + checked.value.toLowerCase()] || payload['option_' + checked.value.toLowerCase()] === '-') {
                sqSetStatus('The selected correct answer must have option text.', 'error');
                return;
            }
        }

        setEditorBusy(true);
        sqSetStatus('Saving…', '');

        try {
            const data = await requestJson(editBaseUrl + qId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': editCsrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            // Prevent a search started before this write from restoring obsolete card data.
            ++searchVersion;
            clearTimeout(searchTimeout);
            searchController?.abort();
            spinner.style.display = 'none';
            const saved = data.data;
            const updatedQuestion = {...searchQuestionData.get(String(qId)), ...saved,
                raw_correct_answer: saved.correct_answer,
                correct_answer: saved.question_type === 'written' ? saved.correct_answer
                    : `${saved.correct_answer}: ${saved['option_' + saved.correct_answer.toLowerCase()] || ''}`,
                explanation: saved.explanation || 'No explanation available.'};

            searchQuestionData.set(String(qId), updatedQuestion);
            updateSearchQuestionCard(qId, updatedQuestion);
            sqSetStatus('✓ Saved!', 'success');
            scheduleClose(900);
        } catch (error) {
            setEditorBusy(false);
            sqSetStatus(error.name === 'AbortError' ? 'Request timed out. Please try again.' : error.message, 'error');
            saveBtn.disabled = false;
        }
    };

    window.sqConfirmDelete = async function() {
        if (editorBusy) return;
        const qId = document.getElementById('sq-q-id').value;
        const button = document.getElementById('sq-confirm-del');
        setEditorBusy(true);
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
        sqSetStatus('Deleting…', '');

        try {
            const data = await requestJson(editBaseUrl + qId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': editCsrf,
                    'Accept': 'application/json'
                }
            });

            ++searchVersion;
            clearTimeout(searchTimeout);
            searchController?.abort();
            spinner.style.display = 'none';
            searchQuestionData.delete(String(qId));
            document.querySelector(`.search-result-card[data-question-id="${qId}"]`)?.remove();
            sqSetStatus('✓ Deleted.', 'success');
            scheduleClose(800);
        } catch (error) {
            setEditorBusy(false);
            sqSetStatus(error.name === 'AbortError' ? 'Request timed out. Please try again.' : error.message, 'error');
            document.getElementById('sq-save-btn').disabled = true;
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
        }
    };

    function updateSearchQuestionCard(qId, question) {
        const card = document.querySelector(`.search-result-card[data-question-id="${qId}"]`);
        if (!card) {
            return;
        }

        const questionText = card.querySelector('.search-result-question');
        const answerText = card.querySelector('.search-result-answer-text');
        const explanationText = card.querySelector('.search-result-explanation');

        if (questionText) {
            questionText.textContent = question.question || '';
        }

        if (answerText) {
            answerText.textContent = question.correct_answer || '';
        }

        if (explanationText) {
            explanationText.textContent = question.explanation || 'No explanation available.';
        }
    }

    function sqSetStatus(message, className) {
        const status = document.getElementById('sq-status');
        status.textContent = message;
        status.className = 'sq-status' + (className ? ' ' + className : '');
    }

    document.getElementById('sq-edit-overlay')?.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.stopPropagation();
            sqCloseModal();
        }
    });
    document.getElementById('sq-edit-overlay')?.addEventListener('click', (event) => {
        if (event.target.id === 'sq-edit-overlay') {
            sqCloseModal();
        }
    });
})();
