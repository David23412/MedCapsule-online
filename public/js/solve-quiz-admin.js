(function () {
    function setStatus(message, statusClass) {
        const element = document.getElementById('sq-status');
        if (!element) {
            return;
        }

        element.textContent = message;
        element.className = `sq-status${statusClass ? ` ${statusClass}` : ''}`;
    }

    function switchType(type, populate, data) {
        document.getElementById('sq-q-type').value = type;
        const mcqBadge = document.getElementById('sq-type-mcq');
        const writtenBadge = document.getElementById('sq-type-written');
        const mcqSection = document.getElementById('sq-mcq-section');
        const writtenSection = document.getElementById('sq-written-section');

        if (type === 'written') {
            mcqBadge.classList.remove('active');
            writtenBadge.classList.add('active');
            mcqSection.style.display = 'none';
            writtenSection.style.display = 'block';
            if (populate && data) {
                document.getElementById('sq-correct-text').value = data.correct_answer || '';
                document.getElementById('sq-alt-answers').value = data.alternative_answers || '';
            }
            return;
        }

        writtenBadge.classList.remove('active');
        mcqBadge.classList.add('active');
        writtenSection.style.display = 'none';
        mcqSection.style.display = 'block';
        if (populate && data) {
            document.getElementById('sq-oa').value = data.option_a || '';
            document.getElementById('sq-ob').value = data.option_b || '';
            document.getElementById('sq-oc').value = data.option_c || '';
            document.getElementById('sq-od').value = data.option_d || '';
            document.getElementById('sq-oe').value = data.option_e || '';
            document.querySelectorAll('input[name="sq-correct"]').forEach(radio => { radio.checked = false; });
            const radio = document.getElementById(`sq-c${(data.correct_answer || '').toLowerCase()}`);
            if (radio) {
                radio.checked = true;
            }
        }
    }

    function closeModal() {
        document.getElementById('sq-edit-overlay')?.classList.remove('show');
        document.body.style.overflow = '';
    }

    function updateLiveQuestion(questionId, payload) {
        document.querySelectorAll(`.question-box[data-question-id="${questionId}"], .written-question[data-question-id="${questionId}"]`).forEach((card) => {
            const text = card.querySelector('.question-text');
            if (text) {
                text.textContent = payload.question;
            }
        });
    }

    function initializeSolveQuizAdmin() {
        if (!window.QUIZ_QUESTION_DATA) {
            return;
        }

        const baseUrl = '/quiz/tutor/question/';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        window.openQuizEditModal = function (questionId) {
            const data = window.QUIZ_QUESTION_DATA?.[questionId];
            if (!data) {
                alert('Question data not available.');
                return;
            }

            document.getElementById('sq-q-id').value = questionId;
            document.getElementById('sq-q-type').value = data.question_type || 'multiple_choice';
            document.getElementById('sq-q-text').value = data.question_text || '';
            document.getElementById('sq-explanation').value = data.explanation || '';

            switchType(data.question_type === 'written' ? 'written' : 'multiple_choice', true, data);

            setStatus('', '');
            document.getElementById('sq-del-confirm').classList.remove('show');
            document.getElementById('sq-save-btn').disabled = false;
            document.getElementById('sq-del-btn').style.display = '';
            document.getElementById('sq-edit-overlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        };

        window.sqSwitchType = function (type, populate, data) {
            switchType(type, populate, data);
        };

        window.sqCloseModal = closeModal;

        window.sqSaveEdit = async function () {
            const questionId = document.getElementById('sq-q-id').value;
            const questionType = document.getElementById('sq-q-type').value;
            const saveButton = document.getElementById('sq-save-btn');
            const questionText = document.getElementById('sq-q-text').value.trim();

            if (!questionText) {
                setStatus('Question text is required.', 'error');
                return;
            }

            const payload = {
                _method: 'PUT',
                question: questionText,
                question_type: questionType,
                explanation: document.getElementById('sq-explanation').value.trim()
            };

            if (questionType === 'written') {
                const correctAnswer = document.getElementById('sq-correct-text').value.trim();
                if (!correctAnswer) {
                    setStatus('Correct answer is required.', 'error');
                    return;
                }
                payload.correct_answer = correctAnswer;
                payload.alternative_answers = document.getElementById('sq-alt-answers').value.trim();
            } else {
                const checked = document.querySelector('input[name="sq-correct"]:checked');
                if (!checked) {
                    setStatus('Please select the correct answer.', 'error');
                    return;
                }
                payload.correct_answer = checked.value;
                payload.option_a = document.getElementById('sq-oa').value.trim();
                payload.option_b = document.getElementById('sq-ob').value.trim();
                payload.option_c = document.getElementById('sq-oc').value.trim();
                payload.option_d = document.getElementById('sq-od').value.trim();
                payload.option_e = document.getElementById('sq-oe').value.trim();
            }

            saveButton.disabled = true;
            setStatus('Saving…', '');

            try {
                const response = await fetch(`${baseUrl}${questionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await response.json();
                if (!json.success) {
                    setStatus(json.message || 'Error saving.', 'error');
                    saveButton.disabled = false;
                    return;
                }

                setStatus('✓ Saved!', 'success');
                updateLiveQuestion(questionId, payload);
                if (window.QUIZ_QUESTION_DATA?.[questionId]) {
                    Object.assign(window.QUIZ_QUESTION_DATA[questionId], payload);
                }
                window.setTimeout(closeModal, 900);
            } catch (error) {
                setStatus(`Network error: ${error.message}`, 'error');
                saveButton.disabled = false;
            }
        };

        window.sqPromptDelete = function () {
            document.getElementById('sq-del-confirm').classList.add('show');
            document.getElementById('sq-save-btn').disabled = true;
            document.getElementById('sq-del-btn').style.display = 'none';
        };

        window.sqCancelDelete = function () {
            document.getElementById('sq-del-confirm').classList.remove('show');
            document.getElementById('sq-save-btn').disabled = false;
            document.getElementById('sq-del-btn').style.display = '';
        };

        window.sqConfirmDelete = async function () {
            const questionId = document.getElementById('sq-q-id').value;
            const confirmButton = document.getElementById('sq-confirm-del');
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
            setStatus('Deleting…', '');

            try {
                const response = await fetch(`${baseUrl}${questionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const json = await response.json();
                if (!json.success) {
                    setStatus(json.message || 'Error.', 'error');
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                    return;
                }

                setStatus('✓ Deleted.', 'success');
                document.querySelectorAll(`[data-question-id="${questionId}"]`).forEach((card) => card.remove());
                window.setTimeout(closeModal, 800);
            } catch (error) {
                setStatus(`Network error: ${error.message}`, 'error');
                confirmButton.disabled = false;
                confirmButton.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
            }
        };

        document.getElementById('sq-edit-overlay')?.addEventListener('click', function (event) {
            if (event.target.id === 'sq-edit-overlay') {
                closeModal();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initializeSolveQuizAdmin, { once: true });
})();
