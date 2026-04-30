(function () {
    function setEditStatus(message, statusClass) {
        const element = document.getElementById('edit-status');
        if (!element) {
            return;
        }

        element.textContent = message;
        element.className = `edit-status${statusClass ? ` ${statusClass}` : ''}`;
    }

    function switchEditType(type, populate, data) {
        document.getElementById('edit-question-type').value = type;

        const mcqBadge = document.getElementById('edit-type-mcq');
        const writtenBadge = document.getElementById('edit-type-written');
        const mcqSection = document.getElementById('edit-mcq-section');
        const writtenSection = document.getElementById('edit-written-section');

        if (type === 'written') {
            mcqBadge.classList.remove('active');
            writtenBadge.classList.add('active');
            mcqSection.style.display = 'none';
            writtenSection.style.display = 'block';
            if (populate && data) {
                document.getElementById('edit-correct-answer-text').value = data.correct_answer || '';
                document.getElementById('edit-alternative-answers').value = data.alternative_answers || '';
            }
            return;
        }

        writtenBadge.classList.remove('active');
        mcqBadge.classList.add('active');
        writtenSection.style.display = 'none';
        mcqSection.style.display = 'block';
        if (populate && data) {
            document.getElementById('edit-option-a').value = data.option_a || '';
            document.getElementById('edit-option-b').value = data.option_b || '';
            document.getElementById('edit-option-c').value = data.option_c || '';
            document.getElementById('edit-option-d').value = data.option_d || '';
            const radio = document.getElementById(`edit-correct-${(data.correct_answer || '').toUpperCase()}`);
            if (radio) {
                radio.checked = true;
            }
        }
    }

    function closeEditModal() {
        document.getElementById('edit-question-overlay')?.classList.remove('show');
        document.body.style.overflow = '';
    }

    function updateLiveQuestionCard(questionId, payload) {
        document.querySelectorAll('.question-card, .question-box, .written-question').forEach((card) => {
            const cardQuestionId = card.dataset.questionId || card.id?.replace('question-', '');
            if (String(cardQuestionId) !== String(questionId) && card.id !== `question-${questionId}`) {
                return;
            }

            const textElement = card.querySelector('.question-text');
            if (textElement) {
                textElement.textContent = payload.question;
            }
        });

        document.querySelectorAll('[id^="question-"]').forEach((card) => {
            const optionsContainer = card.querySelector('.options-container');
            if (!optionsContainer) {
                return;
            }

            if (String(optionsContainer.dataset.questionId) !== String(questionId)) {
                return;
            }

            const textElement = card.querySelector('.question-text');
            if (textElement) {
                textElement.textContent = payload.question;
            }
        });
    }

    function initializeTutorAdminEdit() {
        if (!window.TUTOR_CONFIG?.isAdmin) {
            return;
        }

        const updateUrl = '/quiz/tutor/question/';
        const deleteUrl = '/quiz/tutor/question/';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        window.openEditModal = function (questionId) {
            const data = window.TUTOR_CONFIG?.questionData?.[questionId];
            if (!data) {
                alert('Question data not found.');
                return;
            }

            document.getElementById('edit-question-id').value = questionId;
            document.getElementById('edit-question-type').value = data.question_type || 'multiple_choice';
            document.getElementById('edit-question-text').value = data.question_text || '';
            document.getElementById('edit-explanation').value = data.explanation || '';

            switchEditType(data.question_type === 'written' ? 'written' : 'multiple_choice', true, data);

            document.getElementById('edit-status').textContent = '';
            document.getElementById('edit-status').className = 'edit-status';
            document.getElementById('delete-confirm-box').classList.remove('show');
            document.getElementById('edit-save-btn').disabled = false;
            document.getElementById('edit-question-overlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        };

        window.switchEditType = switchEditType;
        window.closeEditModal = closeEditModal;

        window.saveQuestionEdit = async function () {
            const questionId = document.getElementById('edit-question-id').value;
            const questionType = document.getElementById('edit-question-type').value;
            const saveButton = document.getElementById('edit-save-btn');
            const questionText = document.getElementById('edit-question-text').value.trim();

            if (!questionText) {
                setEditStatus('Question text is required.', 'error');
                return;
            }

            const payload = {
                _method: 'PUT',
                question: questionText,
                question_type: questionType,
                explanation: document.getElementById('edit-explanation').value.trim(),
            };

            if (questionType === 'written') {
                const correctText = document.getElementById('edit-correct-answer-text').value.trim();
                if (!correctText) {
                    setEditStatus('Correct answer is required for written questions.', 'error');
                    return;
                }
                payload.correct_answer = correctText;
                payload.alternative_answers = document.getElementById('edit-alternative-answers').value.trim();
            } else {
                const checked = document.querySelector('input[name="edit-correct"]:checked');
                if (!checked) {
                    setEditStatus('Please select the correct answer.', 'error');
                    return;
                }
                payload.correct_answer = checked.value;
                payload.option_a = document.getElementById('edit-option-a').value.trim();
                payload.option_b = document.getElementById('edit-option-b').value.trim();
                payload.option_c = document.getElementById('edit-option-c').value.trim();
                payload.option_d = document.getElementById('edit-option-d').value.trim();
            }

            saveButton.disabled = true;
            setEditStatus('Saving…', '');

            try {
                const response = await fetch(`${updateUrl}${questionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const json = await response.json();
                if (!json.success) {
                    setEditStatus(json.message || 'Error saving.', 'error');
                    saveButton.disabled = false;
                    return;
                }

                setEditStatus('✓ Saved successfully!', 'success');
                updateLiveQuestionCard(questionId, payload);
                if (window.TUTOR_CONFIG?.questionData?.[questionId]) {
                    Object.assign(window.TUTOR_CONFIG.questionData[questionId], {
                        question_text: payload.question,
                        question_type: payload.question_type,
                        correct_answer: payload.correct_answer,
                        explanation: payload.explanation,
                        alternative_answers: payload.alternative_answers || '',
                        option_a: payload.option_a || '',
                        option_b: payload.option_b || '',
                        option_c: payload.option_c || '',
                        option_d: payload.option_d || '',
                    });
                }
                window.setTimeout(closeEditModal, 900);
            } catch (error) {
                setEditStatus(`Network error: ${error.message}`, 'error');
                saveButton.disabled = false;
            }
        };

        window.promptDeleteQuestion = function () {
            document.getElementById('delete-confirm-box').classList.add('show');
            document.getElementById('edit-save-btn').disabled = true;
            document.getElementById('edit-delete-btn').style.display = 'none';
        };

        window.cancelDeleteQuestion = function () {
            document.getElementById('delete-confirm-box').classList.remove('show');
            document.getElementById('edit-save-btn').disabled = false;
            document.getElementById('edit-delete-btn').style.display = '';
        };

        window.confirmDeleteQuestion = async function () {
            const questionId = document.getElementById('edit-question-id').value;
            const confirmButton = document.getElementById('confirm-delete-btn');
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
            setEditStatus('Deleting question…', '');

            try {
                const response = await fetch(`${deleteUrl}${questionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const json = await response.json();
                if (!json.success) {
                    setEditStatus(json.message || 'Error deleting.', 'error');
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                    return;
                }

                setEditStatus('✓ Question deleted.', 'success');
                document.querySelectorAll('.question-card, .question-box, .written-question').forEach((card) => {
                    const cardQuestionId = card.dataset.questionId;
                    if (String(cardQuestionId) === String(questionId)) {
                        card.remove();
                    }
                });
                document.querySelectorAll('[id^="question-"]').forEach((card) => {
                    const optionContainer = card.querySelector(`.options-container[data-question-id="${questionId}"]`);
                    if (optionContainer) {
                        card.remove();
                    }
                });
                window.setTimeout(closeEditModal, 800);
            } catch (error) {
                setEditStatus(`Network error: ${error.message}`, 'error');
                confirmButton.disabled = false;
                confirmButton.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
            }
        };

        document.getElementById('edit-question-overlay')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeEditModal();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initializeTutorAdminEdit, { once: true });
})();
