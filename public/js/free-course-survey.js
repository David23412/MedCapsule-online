(function () {
    const pageConfig = window.CoursesPageConfig || {};
    const config = window.FreeCourseSurveyConfig || pageConfig.freeCourseSurvey;

    if (!config) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const backdrop = document.getElementById('freeCourseSurveyBackdrop');
    const modal = document.getElementById('freeCourseSurveyModal');
    const introView = document.getElementById('freeCourseSurveyIntro');
    const flowView = document.getElementById('freeCourseSurveyFlow');
    const closeButton = document.getElementById('freeCourseSurveyClose');
    const laterButton = document.getElementById('freeCourseSurveyLater');
    const startButton = document.getElementById('freeCourseSurveyStart');
    const questionCards = Array.from(document.querySelectorAll('.survey-question-card'));
    const form = document.getElementById('freeCourseSurveyForm');
    const prevButton = document.getElementById('surveyPrevBtn');
    const nextButton = document.getElementById('surveyNextBtn');
    const submitButton = document.getElementById('surveySubmitBtn');
    const progressFill = document.getElementById('surveyProgressFill');
    const progressValue = document.getElementById('surveyProgressValue');
    const saveStatus = document.getElementById('surveySaveStatus');
    const successOverlay = document.getElementById('surveySuccessOverlay');
    const triggers = document.querySelectorAll('.free-course-survey-trigger');

    if (!form || questionCards.length === 0) {
        return;
    }

    const totalSteps = questionCards.length;
    const initialDraft = config.draft || null;
    const escapeSelectorValue = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(/"/g, '\\"');
    };

    let answers = { ...(initialDraft?.answers || {}) };
    let currentStep = Math.max(1, Math.min(initialDraft?.current_step || 1, totalSteps));
    let redirectTo = config.redirectTo || initialDraft?.redirect_to || '/courses';
    let saveTimeout = null;

    hydrateAnswers();
    renderStep();
    showIntro();

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openModal({
                redirectTo: trigger.dataset.courseUrl || redirectTo,
                startSurvey: false,
            });
        });
    });

    if (config.openImmediately) {
        openModal({
            redirectTo: config.redirectTo || redirectTo,
            startSurvey: true,
        });
    }

    backdrop?.addEventListener('click', closeModal);
    closeButton?.addEventListener('click', closeModal);
    laterButton?.addEventListener('click', closeModal);
    startButton?.addEventListener('click', async () => {
        showFlow();
        await saveDraft();
    });

    questionCards.forEach((card) => {
        const questionId = card.dataset.questionId;
        const questionType = card.dataset.questionType;

        if (questionType === 'single_choice') {
            card.querySelectorAll('input[type="radio"]').forEach((input) => {
                input.addEventListener('change', () => {
                    answers[questionId] = input.value;
                    updateOptionStates(card);
                    clearError(questionId);
                    queueSave();
                });
            });
            updateOptionStates(card);
            return;
        }

        const textarea = card.querySelector('textarea');
        if (!textarea) {
            return;
        }

        textarea.addEventListener('input', () => {
            answers[questionId] = textarea.value.trim();
            clearError(questionId);
            queueSave();
        });
    });

    prevButton?.addEventListener('click', async () => {
        if (currentStep === 1) {
            return;
        }

        syncCurrentStepAnswer();
        currentStep -= 1;
        renderStep();
        await saveDraft();
    });

    nextButton?.addEventListener('click', async () => {
        syncCurrentStepAnswer();

        if (!validateCurrentStep()) {
            return;
        }

        currentStep += 1;
        renderStep();
        await saveDraft();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        syncAllAnswers();

        if (!validateCurrentStep() || !validateAllRequired()) {
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Unlocking...';
        saveStatus.textContent = 'Submitting survey';

        const response = await fetch(config.submitUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                answers,
                redirect_to: redirectTo,
            }),
        });

        const payload = await response.json();

        if (!response.ok) {
            submitButton.disabled = false;
            submitButton.textContent = 'Unlock Course';
            saveStatus.textContent = 'Unable to submit survey';
            return;
        }

        successOverlay.hidden = false;
        saveStatus.textContent = payload.message;

        window.setTimeout(() => {
            window.location.href = payload.redirect_to;
        }, 1200);
    });

    function openModal(options = {}) {
        redirectTo = options.redirectTo || redirectTo;

        if (backdrop) {
            backdrop.hidden = false;
        }

        if (modal) {
            modal.hidden = false;
        }

        document.body.style.overflow = 'hidden';

        if (options.startSurvey) {
            showFlow();
        } else {
            showIntro();
        }
    }

    function closeModal() {
        if (backdrop) {
            backdrop.hidden = true;
        }

        if (modal) {
            modal.hidden = true;
        }

        document.body.style.overflow = '';
    }

    function showIntro() {
        if (introView) {
            introView.hidden = false;
        }

        if (flowView) {
            flowView.hidden = true;
        }
    }

    function showFlow() {
        if (introView) {
            introView.hidden = true;
        }

        if (flowView) {
            flowView.hidden = false;
        }

        renderStep();
    }

    function hydrateAnswers() {
        Object.entries(answers).forEach(([questionId, value]) => {
            const card = questionCards.find((item) => item.dataset.questionId === questionId);

            if (!card) {
                return;
            }

            if (card.dataset.questionType === 'single_choice') {
                const input = card.querySelector(`input[value="${escapeSelectorValue(value)}"]`);
                if (input) {
                    input.checked = true;
                }
                updateOptionStates(card);
                return;
            }

            const textarea = card.querySelector('textarea');
            if (textarea) {
                textarea.value = value;
            }
        });
    }

    function renderStep() {
        questionCards.forEach((card, index) => {
            card.classList.toggle('is-active', index === currentStep - 1);
        });

        const progress = Math.round((currentStep / totalSteps) * 100);
        progressFill.style.width = `${progress}%`;
        progressValue.textContent = `${currentStep} / ${totalSteps}`;
        prevButton.hidden = currentStep === 1;
        nextButton.hidden = currentStep === totalSteps;
        submitButton.hidden = currentStep !== totalSteps;

        const activeCard = questionCards[currentStep - 1];
        activeCard?.scrollIntoView({ block: 'nearest' });
    }

    function validateCurrentStep() {
        const card = questionCards[currentStep - 1];
        const questionId = card.dataset.questionId;
        const required = card.dataset.required === 'true';
        const value = getQuestionValue(card);

        if (!required) {
            clearError(questionId);
            return true;
        }

        const isEmpty = typeof value === 'undefined' || value === null || String(value).trim() === '';

        if (isEmpty) {
            setError(questionId, 'هذا السؤال مطلوب');
            return false;
        }

        clearError(questionId);
        return true;
    }

    function validateAllRequired() {
        let valid = true;

        questionCards.forEach((card, index) => {
            const questionId = card.dataset.questionId;
            const required = card.dataset.required === 'true';
            const value = getQuestionValue(card);
            const isEmpty = typeof value === 'undefined' || value === null || String(value).trim() === '';

            if (required && isEmpty) {
                setError(questionId, 'هذا السؤال مطلوب');
                valid = false;
                currentStep = index + 1;
            }
        });

        if (!valid) {
            showFlow();
        }

        return valid;
    }

    function syncCurrentStepAnswer() {
        const card = questionCards[currentStep - 1];
        if (!card) {
            return;
        }

        const questionId = card.dataset.questionId;
        const value = getQuestionValue(card);

        if (value === undefined || value === null || String(value).trim() === '') {
            delete answers[questionId];
            return;
        }

        answers[questionId] = value;
    }

    function syncAllAnswers() {
        questionCards.forEach((card) => {
            const questionId = card.dataset.questionId;
            const value = getQuestionValue(card);

            if (value === undefined || value === null || String(value).trim() === '') {
                delete answers[questionId];
                return;
            }

            answers[questionId] = value;
        });
    }

    function getQuestionValue(card) {
        if (!card) {
            return undefined;
        }

        if (card.dataset.questionType === 'single_choice') {
            return card.querySelector('input[type="radio"]:checked')?.value;
        }

        return card.querySelector('textarea')?.value.trim();
    }

    function setError(questionId, message) {
        const errorNode = document.querySelector(`[data-error-for="${questionId}"]`);
        if (errorNode) {
            errorNode.textContent = message;
        }
    }

    function clearError(questionId) {
        const errorNode = document.querySelector(`[data-error-for="${questionId}"]`);
        if (errorNode) {
            errorNode.textContent = '';
        }
    }

    function updateOptionStates(card) {
        card.querySelectorAll('.survey-option-card').forEach((option) => {
            const input = option.querySelector('input');
            option.classList.toggle('is-selected', Boolean(input?.checked));
        });
    }

    function queueSave() {
        saveStatus.textContent = 'Saving progress...';

        window.clearTimeout(saveTimeout);
        saveTimeout = window.setTimeout(() => {
            saveDraft().catch(() => {
                saveStatus.textContent = 'Unable to save progress';
            });
        }, 350);
    }

    async function saveDraft() {
        syncCurrentStepAnswer();

        const response = await fetch(config.draftUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                answers,
                current_step: currentStep,
                redirect_to: redirectTo,
            }),
        });

        if (!response.ok) {
            throw new Error('Draft save failed');
        }

        saveStatus.textContent = 'Saved';
    }
})();
