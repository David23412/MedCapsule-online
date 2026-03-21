(function () {
    const pageConfig = window.CoursesPageConfig;
    const surveyConfig = pageConfig?.freeCourseSurvey;

    if (!surveyConfig?.enabled) {
        return;
    }

    const backdrop = document.getElementById('freeCourseSurveyBackdrop');
    const modal = document.getElementById('freeCourseSurveyModal');
    const closeButton = document.getElementById('freeCourseSurveyClose');
    const laterButton = document.getElementById('freeCourseSurveyLater');
    const startButton = document.getElementById('freeCourseSurveyStart');
    const triggers = document.querySelectorAll('.free-course-survey-trigger');

    if (!backdrop || !modal || !startButton || triggers.length === 0) {
        return;
    }

    const hideModal = () => {
        backdrop.hidden = true;
        modal.hidden = true;
        document.body.style.overflow = '';
    };

    const showModal = (courseUrl) => {
        const url = new URL(surveyConfig.surveyUrl, window.location.origin);
        if (courseUrl) {
            url.searchParams.set('redirect_to', courseUrl);
        }

        startButton.href = url.toString();
        backdrop.hidden = false;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            showModal(trigger.dataset.courseUrl || '');
        });
    });

    backdrop.addEventListener('click', hideModal);
    closeButton?.addEventListener('click', hideModal);
    laterButton?.addEventListener('click', hideModal);

    if (surveyConfig.successMessage) {
        window.setTimeout(() => {
            console.info(surveyConfig.successMessage);
        }, 0);
    }
})();
