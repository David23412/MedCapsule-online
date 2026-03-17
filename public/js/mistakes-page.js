const mistakesPageConfig = window.MistakesPageConfig || {};
const reviewIndexUrl = String(mistakesPageConfig.reviewIndexUrl || '');

let isFlashcardMode = false;
let isFlipping = false;
let activeFilterController = null;

function getFlashcardModeToggle() {
    return document.getElementById('flashcardModeToggle');
}

function getMistakeCards(scope = document) {
    return Array.from(scope.querySelectorAll('.mistake-card'));
}

function getBackButtonHtml() {
    return '';
}

function setFlashcardButtonState() {
    const flashcardModeToggle = getFlashcardModeToggle();
    if (!flashcardModeToggle) return;

    flashcardModeToggle.classList.toggle('active', isFlashcardMode);
    flashcardModeToggle.innerHTML = `
        <i class="fas fa-sync-alt"></i>
        Flashcard Mode ${isFlashcardMode ? 'ON' : 'OFF'}
    `;
}

function hydrateFlashcardCard(card) {
    const questionText = card.querySelector('.normal-mode-content .question-text p')?.textContent ?? '';
    const questionImage = card.querySelector('.normal-mode-content .question-image');
    const writtenCorrect = card.querySelector('.normal-mode-content .written-correct-answer');
    const correctLabel = card.querySelector('.normal-mode-content .form-check-label.correct');
    const correctAnswer = writtenCorrect ? writtenCorrect.textContent : (correctLabel ? correctLabel.textContent : '');

    const cardFront = card.querySelector('.flashcard-content .card-front');
    const cardBack = card.querySelector('.flashcard-content .card-back');

    if (!cardFront || !cardBack) return;

    cardFront.innerHTML = `
        <div class="question-text">
            <p>${questionText}</p>
            ${questionImage ? `<img src="${questionImage.src}" alt="Question Image" class="question-image">` : ''}
        </div>
    `;

    cardBack.innerHTML = `
        <div class="correct-answer">
            ${correctAnswer}
        </div>
    `;
}

function resetFlashcardCard(card) {
    const cardFront = card.querySelector('.flashcard-content .card-front');
    const cardBack = card.querySelector('.flashcard-content .card-back');

    if (cardFront) cardFront.innerHTML = '';
    if (cardBack) cardBack.innerHTML = '';
    card.classList.remove('flipped');
}

function syncFlashcardModeOnCards(scope = document) {
    getMistakeCards(scope).forEach((card) => {
        if (isFlashcardMode) {
            hydrateFlashcardCard(card);
            card.classList.add('flashcard-mode');
            return;
        }

        resetFlashcardCard(card);
        card.classList.remove('flashcard-mode');
    });

    setFlashcardButtonState();
}

function bindMistakeCardInteractions(scope = document) {
    getMistakeCards(scope).forEach((card) => {
        if (card.dataset.mistakeBound === 'true') return;
        card.dataset.mistakeBound = 'true';

        card.addEventListener('click', (event) => {
            if (!isFlashcardMode || isFlipping) return;

            event.preventDefault();
            isFlipping = true;
            card.style.transform = 'scale(0.98)';

            setTimeout(() => {
                card.classList.toggle('flipped');
                card.style.transform = '';

                setTimeout(() => {
                    isFlipping = false;
                }, 600);
            }, 50);
        });

        card.addEventListener('touchstart', (event) => {
            if (!isFlashcardMode) return;

            event.preventDefault();
            card.style.transform = 'scale(0.96)';
        });

        card.addEventListener('touchend', (event) => {
            if (!isFlashcardMode) return;

            event.preventDefault();
            card.style.transform = '';

            if (isFlipping) return;

            isFlipping = true;
            setTimeout(() => {
                card.classList.toggle('flipped');
                setTimeout(() => {
                    isFlipping = false;
                }, 600);
            }, 50);
        });
    });

    syncFlashcardModeOnCards(scope);
}

function initializeFlashcardToggle() {
    const flashcardModeToggle = getFlashcardModeToggle();
    if (!flashcardModeToggle || flashcardModeToggle.dataset.bound === 'true') {
        setFlashcardButtonState();
        return;
    }

    flashcardModeToggle.dataset.bound = 'true';
    flashcardModeToggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        isFlashcardMode = !isFlashcardMode;
        syncFlashcardModeOnCards();
    });

    setFlashcardButtonState();
}

function initializeExplanationToggles(scope = document) {
    scope.querySelectorAll('.explanation-toggle').forEach((toggle) => {
        if (toggle.dataset.bound === 'true') return;

        const targetId = toggle.getAttribute('data-explanation-target');
        const panel = targetId ? document.getElementById(targetId) : null;
        if (!panel) return;

        toggle.dataset.bound = 'true';
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            const isOpen = panel.classList.toggle('is-open');
            toggle.classList.toggle('active', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
}

function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;

    toastContainer.appendChild(toast);
    toast.offsetHeight;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function setFilterLoadingState(isLoading) {
    const courseFilter = document.getElementById('courseFilter');
    const filterSection = document.querySelector('.filter-section');
    const mistakesContent = document.getElementById('mistakes-content');
    const headerSection = document.querySelector('.header-section');

    if (courseFilter) {
        courseFilter.disabled = isLoading;
        courseFilter.classList.toggle('loading', isLoading);
    }

    [filterSection, mistakesContent, headerSection].forEach((element) => {
        if (!element) return;
        element.style.opacity = isLoading ? '0.62' : '';
        element.style.pointerEvents = isLoading ? 'none' : '';
    });
}

function replaceSection(selector, nextDocument) {
    const current = document.querySelector(selector);
    const next = nextDocument.querySelector(selector);

    if (!current || !next) return;
    current.replaceWith(next);
}

async function loadFilteredMistakes(url, options = {}) {
    const { pushState = true } = options;

    if (!url) return;

    if (activeFilterController) {
        activeFilterController.abort();
    }

    activeFilterController = new AbortController();
    setFilterLoadingState(true);

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: activeFilterController.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');

        replaceSection('.header-section', nextDocument);
        replaceSection('.filter-section', nextDocument);
        replaceSection('#mistakes-content', nextDocument);

        if (pushState) {
            window.history.pushState({}, '', url);
        }

        initializeFlashcardToggle();
        initializeExplanationToggles();
        initializeFilterControls();
        bindMistakeCardInteractions();
    } catch (error) {
        if (error.name === 'AbortError') return;

        window.location.href = url;
    } finally {
        setFilterLoadingState(false);
        activeFilterController = null;
    }
}

function buildFilterUrl(form) {
    const url = new URL(form.action || reviewIndexUrl || window.location.href, window.location.origin);
    const formData = new FormData(form);
    const selectedCourse = String(formData.get('course') || '').trim();

    if (selectedCourse) {
        url.searchParams.set('course', selectedCourse);
    } else {
        url.searchParams.delete('course');
    }

    return url.toString();
}

function initializeFilterControls() {
    const filterForm = document.getElementById('robust-filter-form');
    const courseFilter = document.getElementById('courseFilter');
    const clearFilterButton = document.querySelector('.filter-section .btn-outline-secondary');

    if (filterForm && filterForm.dataset.bound !== 'true') {
        filterForm.dataset.bound = 'true';
        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loadFilteredMistakes(buildFilterUrl(filterForm));
        });
    }

    if (courseFilter && courseFilter.dataset.bound !== 'true') {
        courseFilter.dataset.bound = 'true';
        courseFilter.addEventListener('change', () => {
            if (filterForm) {
                filterForm.requestSubmit();
            }
        });
    }

    if (clearFilterButton && clearFilterButton.dataset.bound !== 'true') {
        clearFilterButton.dataset.bound = 'true';
        clearFilterButton.addEventListener('click', (event) => {
            event.preventDefault();
            loadFilteredMistakes(reviewIndexUrl);
        });
    }
}

function removeMistake(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    if (!card) {
        console.error('Card not found for question ID:', questionId);
        return;
    }

    const removeButton = card.querySelector('.remove-question');
    if (removeButton) {
        removeButton.disabled = true;
        removeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        removeButton.style.opacity = '0.6';
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`/review/remove/${questionId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        })
        .then((data) => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to remove question');
            }

            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                card.remove();

                if (document.querySelectorAll('.mistake-card').length === 0) {
                    const mistakesContent = document.querySelector('#mistakes-content');
                    if (mistakesContent) {
                        mistakesContent.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-check"></i>
                                <h3>No Mistakes!</h3>
                                <p>Fantastic work! You're crushing it with zero mistakes. Keep up the amazing progress!</p>
                            </div>
                        `;
                    }
                }

                showToast('Question removed successfully!', 'success');
            }, 300);
        })
        .catch((error) => {
            console.error('Error removing mistake:', error);

            if (removeButton) {
                removeButton.disabled = false;
                removeButton.innerHTML = '<i class="fas fa-times"></i>';
                removeButton.style.opacity = '1';
            }

            showToast('Error removing question. Please try again.', 'error');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Review mistakes page loaded successfully');

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarStorageKey = 'medcapsule-review-sidebar-collapsed';
    const getStoredSidebarState = () => {
        try {
            return localStorage.getItem(sidebarStorageKey);
        } catch (error) {
            return null;
        }
    };
    const setStoredSidebarState = (collapsed) => {
        try {
            localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false');
        } catch (error) {
            return;
        }
    };
    const applySidebarState = (collapsed) => {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    };

    const savedSidebarState = getStoredSidebarState();
    if (savedSidebarState !== null) {
        applySidebarState(savedSidebarState === 'true');
    } else if (window.innerWidth < 768) {
        applySidebarState(true);
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const collapsed = !document.body.classList.contains('sidebar-collapsed');
            applySidebarState(collapsed);
            setStoredSidebarState(collapsed);
        });
    }

    initializeFlashcardToggle();
    initializeExplanationToggles();
    initializeFilterControls();
    bindMistakeCardInteractions();

    window.addEventListener('popstate', () => {
        loadFilteredMistakes(window.location.href, { pushState: false });
    });
});

window.removeMistake = removeMistake;
window.getBackButtonHtml = getBackButtonHtml;
