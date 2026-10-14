const TUTOR_CONFIG = window.TUTOR_CONFIG || {};


// ============================================================
// BLOCK 1: Core tutor mode logic
// ============================================================

// 💀 LETHAL AUDIO POOL SYSTEM - 0MS INSTANT PLAYBACK 💀
let audioPool = {
    correct: [],
    wrong: [],
    perfect: [],
    partial: []
};

// Global variable declarations
let firstAttemptAnswers = {};
let questionStates = {};
// Track when the quiz session started (used for duration + autosubmit)
let quizStartTime = new Date();
// Ensure availability across any later scripts that might reference it
window.quizStartTime = quizStartTime;
let currentQuestionIndex = 1;
let totalQuestions = TUTOR_CONFIG.totalQuestions;
let correctAnswers = 0;
let totalAnswers = 0;
let correctAnswerStreak = 0;
let lastCelebratedStreakMilestone = 0;
let userAnswers = {};
let questionResults = {};
let timerInterval;
let answerCache = {};
let questionData = {};
let streakRewardAudio = null;
const tutorDraft = window.QuizSessionDraft?.create({
    key: TUTOR_CONFIG.draftKey,
    questionIds: Object.keys(TUTOR_CONFIG.questionData || {}),
    onExpire: () => window.location.reload()
}) || null;
let isRestoringTutorDraft = false;

// Highlighting system
let highlights = {};
let currentSelection = null;
let highlightToolbar = null;

function getQuestionIdFromCard(questionCard) {
    if (!questionCard) {
        return null;
    }

    const optionsContainer = questionCard.querySelector('.options-container[data-question-id]');
    if (optionsContainer?.dataset?.questionId) {
        return String(optionsContainer.dataset.questionId);
    }

    const checkAnswerButton = questionCard.querySelector('.check-answer');
    const onclickValue = checkAnswerButton?.getAttribute('onclick') || '';
    const match = onclickValue.match(/checkAnswer\(\s*\d+\s*,\s*(\d+)\s*\)/);
    return match ? String(match[1]) : null;
}

function getQuestionNumberFromCard(questionCard) {
    const id = questionCard?.id || '';
    const match = id.match(/^question-(\d+)$/);
    return match ? Number(match[1]) : null;
}

function collectTutorInputDraft() {
    const inputs = {};

    document.querySelectorAll('.question-card').forEach((questionCard) => {
        const questionId = getQuestionIdFromCard(questionCard);
        if (!questionId) {
            return;
        }

        const writtenAnswer = questionCard.querySelector('.written-answer');
        if (writtenAnswer) {
            inputs[questionId] = writtenAnswer.value || '';
            return;
        }

        const selectedOption = questionCard.querySelector('.option-card.selected');
        if (selectedOption) {
            inputs[questionId] = selectedOption.getAttribute('data-option') || '';
        }
    });

    return inputs;
}

function saveTutorDraft() {
    if (!tutorDraft || isRestoringTutorDraft) {
        return;
    }

    tutorDraft.save({
        startedAt: quizStartTime instanceof Date ? quizStartTime.getTime() : Number(quizStartTime || Date.now()),
        currentQuestionIndex,
        correctAnswers,
        totalAnswers,
        correctAnswerStreak,
        lastCelebratedStreakMilestone,
        userAnswers,
        questionStates,
        questionResults,
        pendingInputs: collectTutorInputDraft(),
        scrollY: window.scrollY || 0
    });
}

function clearTutorDraft() {
    tutorDraft?.clear();
}

function applyTutorPendingInputs(pendingInputs) {
    if (!pendingInputs || typeof pendingInputs !== 'object') {
        return;
    }

    Object.entries(pendingInputs).forEach(([questionId, answer]) => {
        const normalizedAnswer = typeof answer === 'string' ? answer : '';
        const questionCard = Array.from(document.querySelectorAll('.question-card')).find((card) => {
            return getQuestionIdFromCard(card) === String(questionId);
        });

        if (!questionCard) {
            return;
        }

        const writtenAnswer = questionCard.querySelector('.written-answer');
        if (writtenAnswer) {
            writtenAnswer.value = normalizedAnswer;
            return;
        }

        questionCard.querySelectorAll('.option-card').forEach((optionCard) => {
            optionCard.classList.toggle('selected', optionCard.getAttribute('data-option') === normalizedAnswer);
        });
    });
}

function renderDontKnowResult(questionNumber, questionId, questionResult) {
    const qData = questionData[questionId] || questionData[Number(questionId)];
    const questionCard = document.getElementById(`question-${questionNumber}`);
    if (!qData || !questionCard) {
        return;
    }

    const correctAnswerKey = String(qData.correct_answer || '');
    const correctAnswerText = getAnswerDisplayText(questionCard, correctAnswerKey);

    updateSidebarCircleState(questionNumber, false);

    const feedbackDiv = questionCard.querySelector('.answer-feedback');
    const alertDiv = feedbackDiv.querySelector('.alert');
    feedbackDiv.style.display = 'block';
    alertDiv.className = 'alert alert-danger';
    feedbackDiv.querySelector('.result-text').textContent = "Don't worry! Here's the answer:";
    feedbackDiv.querySelector('.explanation-content').innerHTML = `
                <div class="explanation-with-icon">
                    <div class="answer-header">
                        <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                        <strong>Correct Answer: ${escapeHtml(correctAnswerText)}</strong>
                    </div>
                    <div class="explanation-text">
                        ${qData.explanation || 'No explanation available.'}
                    </div>
                </div>
            `;

    questionCard.querySelectorAll('.option-card').forEach((opt) => {
        const optionLetter = opt.getAttribute('data-option');
        if (optionLetter === correctAnswerKey) {
            opt.classList.add('correct');
        }
        opt.style.pointerEvents = 'none';
        opt.style.opacity = '0.6';
    });

    const checkBtn = questionCard.querySelector('.check-answer');
    const dontKnowBtn = questionCard.querySelector('.btn-dont-know');
    const nextBtn = questionCard.querySelector('.next-question');
    const resultsBtn = questionCard.querySelector('.view-results');
    if (checkBtn) checkBtn.style.display = 'none';
    if (dontKnowBtn) dontKnowBtn.style.display = 'none';
    if (questionResult?.showViewResultsButton || totalAnswers === totalQuestions) {
        if (resultsBtn) resultsBtn.style.display = 'inline-block';
    } else if (nextBtn) {
        nextBtn.style.display = 'inline-block';
    }
}

function restoreTutorDraftState() {
    const savedDraft = tutorDraft?.load();
    if (!savedDraft) {
        return;
    }

    isRestoringTutorDraft = true;
    applyTutorPendingInputs(savedDraft.pendingInputs);

    if (Number.isFinite(Number(savedDraft.startedAt)) && Number(savedDraft.startedAt) > 0) {
        quizStartTime = new Date(Number(savedDraft.startedAt));
        window.quizStartTime = quizStartTime;
    }

    currentQuestionIndex = Math.max(1, Number(savedDraft.currentQuestionIndex || 1));
    correctAnswers = Math.max(0, Number(savedDraft.correctAnswers || 0));
    totalAnswers = Math.max(0, Number(savedDraft.totalAnswers || 0));
    correctAnswerStreak = Math.max(0, Number(savedDraft.correctAnswerStreak || 0));
    lastCelebratedStreakMilestone = Math.max(0, Number(savedDraft.lastCelebratedStreakMilestone || 0));
    userAnswers = savedDraft.userAnswers && typeof savedDraft.userAnswers === 'object' ? savedDraft.userAnswers : {};
    questionStates = savedDraft.questionStates && typeof savedDraft.questionStates === 'object' ? savedDraft.questionStates : {};
    questionResults = savedDraft.questionResults && typeof savedDraft.questionResults === 'object' ? savedDraft.questionResults : {};

    document.querySelectorAll('.question-card').forEach((questionCard) => {
        questionCard.style.display = 'none';
    });

    Object.entries(questionResults).forEach(([questionId, questionResult]) => {
        const questionCard = Array.from(document.querySelectorAll('.question-card')).find((card) => {
            return getQuestionIdFromCard(card) === String(questionId);
        });
        const questionNumber = getQuestionNumberFromCard(questionCard);

        if (!questionNumber) {
            return;
        }

        if (questionResult?.type === 'dont_know') {
            renderDontKnowResult(questionNumber, questionId, questionResult);
            return;
        }

        processAnswerResult[`result_processed_${questionNumber}`] = true;
        updateQuestionUI(questionNumber, {
            correct: Boolean(questionResult?.is_correct),
            explanation: questionResult?.explanation || '',
            correctAnswer: questionResult?.correct_answer || '',
            showNextButton: !(questionResult?.showViewResultsButton),
            showViewResultsButton: Boolean(questionResult?.showViewResultsButton)
        });
        updateSidebarCircleState(questionNumber, Boolean(questionResult?.is_correct));
    });

    const activeQuestion = document.getElementById(`question-${currentQuestionIndex}`) || document.getElementById('question-1');
    if (activeQuestion) {
        activeQuestion.style.display = 'block';
    }

    updateSidebarActiveState(currentQuestionIndex);
    updateProgress(totalAnswers, totalQuestions);

    if (savedDraft.scrollY !== undefined) {
        requestAnimationFrame(() => window.scrollTo({ top: Number(savedDraft.scrollY || 0), behavior: 'auto' }));
    }

    isRestoringTutorDraft = false;
}

function resetCorrectAnswerStreak() {
    correctAnswerStreak = 0;
}

function playStreakRewardSound() {
    try {
        if (!streakRewardAudio) {
            streakRewardAudio = new Audio('/sounds/encouragement.mp3');
            streakRewardAudio.preload = 'auto';
            streakRewardAudio.volume = 0.68;
        }

        streakRewardAudio.currentTime = 0;
        streakRewardAudio.play().catch(() => {});
    } catch (error) {
        console.log('ℹ️ Streak reward sound unavailable');
    }
}

function showStreakCelebration(streakCount) {
    const celebration = document.getElementById('streak-celebration');
    if (!celebration) {
        return;
    }

    const streakCountElement = celebration.querySelector('[data-streak-count]');
    const streakMessageElement = celebration.querySelector('[data-streak-message]');

    if (streakCountElement) {
        streakCountElement.textContent = `${streakCount} correct in a row`;
    }

    if (streakMessageElement) {
        streakMessageElement.textContent = streakCount === 5
            ? 'Clean run. Keep the pace steady.'
            : `${streakCount} straight answers. Locked in.`;
    }

    celebration.classList.remove('show');
    void celebration.offsetWidth;
    celebration.classList.add('show');

    playStreakRewardSound();

    if (typeof confetti !== 'undefined') {
        confetti({
            particleCount: 42,
            spread: 34,
            startVelocity: 24,
            ticks: 140,
            gravity: 0.9,
            drift: 0.15,
            origin: { x: 0.5, y: 0.18 },
            colors: ['#f59e0b', '#fbbf24', '#cbd5e1', '#94a3b8'],
            scalar: 0.82,
            zIndex: 11000
        });
    }

    window.clearTimeout(showStreakCelebration.hideTimer);
    showStreakCelebration.hideTimer = window.setTimeout(() => {
        celebration.classList.remove('show');
    }, 2350);
}

function updateCorrectAnswerStreak(isCorrect) {
    if (!isCorrect) {
        resetCorrectAnswerStreak();
        return;
    }

    correctAnswerStreak += 1;

    if (correctAnswerStreak % 5 !== 0 || correctAnswerStreak === lastCelebratedStreakMilestone) {
        return;
    }

    lastCelebratedStreakMilestone = correctAnswerStreak;
    showStreakCelebration(correctAnswerStreak);
}

// Function to update sidebar active state
function updateSidebarActiveState(questionNumber) {
    document.querySelectorAll('.question-circle').forEach(circle => {
        circle.classList.remove('active');
        if (parseInt(circle.dataset.question) === questionNumber) {
            circle.classList.add('active');
        }
    });
}

// Update sidebar circle state (correct/incorrect)
function updateSidebarCircleState(questionNumber, isCorrect) {
    const circle = document.querySelector(`.question-circle[data-question="${questionNumber}"]`);
    if (circle) {
        circle.classList.remove('answered');
        if (isCorrect) {
            circle.classList.add('correct');
            circle.classList.remove('incorrect');
        } else {
            circle.classList.add('incorrect');
            circle.classList.remove('correct');
        }
    }
}

// 💀 LETHAL AUDIO POOL - INSTANT SOUND PLAYBACK 💀
function playInstantSound(isCorrect) {
    const startTime = performance.now();
    const soundType = isCorrect ? 'correct' : 'wrong';

    if (playInstantSound.isPlaying) {
        console.log(`🔇 LETHAL AUDIO POOL: Sound already playing, skipping duplicate ${soundType} sound`);
        return;
    }

    console.log(`🔥 LETHAL AUDIO POOL: Attempting 0ms playback for ${soundType} sound`);

    let audio = null;
    if (audioPool[soundType] && audioPool[soundType].length > 0) {
        audio = audioPool[soundType].shift();
        console.log(`🔥 LETHAL AUDIO POOL: Got ${soundType} audio from pool (${audioPool[soundType].length} remaining)`);
    } else {
        console.log(`🔥 LETHAL AUDIO POOL: Pool empty, creating fresh ${soundType} audio`);
        createAudioElement(soundType);
        audio = audioPool[soundType].shift();
    }

    if (!audio) {
        console.log(`ℹ️ LETHAL AUDIO POOL: No audio available for ${soundType}, continuing silently`);
        return;
    }

    try {
        audio.currentTime = 0;
        audio.volume = 0.7;

        const endedHandler = () => {
            audio.removeEventListener('ended', endedHandler);
            playInstantSound.isPlaying = false;
            setTimeout(() => {
                returnToPool(audio, soundType);
            }, 100);
        };
        audio.addEventListener('ended', endedHandler);

        playInstantSound.isPlaying = true;

        const playPromise = audio.play();
        if (playPromise !== undefined) {
            playPromise.then(() => {
                const playbackTime = performance.now() - startTime;
                console.log(`✅ LETHAL AUDIO POOL: ${soundType} sound started in ${playbackTime.toFixed(2)}ms - letting it play fully`);

            }).catch(error => {
                console.error(`❌ LETHAL AUDIO POOL: ${soundType} playback failed`, error);
                audio.removeEventListener('ended', endedHandler);
                returnToPool(audio, soundType);
                playInstantSound.isPlaying = false;
            });
        }
    } catch (error) {
        console.error(`❌ LETHAL AUDIO POOL: ${soundType} audio error`, error);
        returnToPool(audio, soundType);
        playInstantSound.isPlaying = false;
    }
}


// RECORD TUTOR QUIZ ATTEMPT - FIRE AND FORGET
function recordTutorQuizAttempt(results) {
    const funcStart = performance.now();
    console.log('📊 RECORDING TUTOR QUIZ ATTEMPT - FIRE AND FORGET');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const quizData = {
            results: {
                correct: results.correct,
                total: results.total,
                percentage: Math.round((results.correct / results.total) * 100)
            },
            user_answers: userAnswers || {},
            answered_questions: results.total,
            duration_seconds: Math.floor((new Date() - quizStartTime) / 1000),
            topic_id: TUTOR_CONFIG.topicId,
            course_id: TUTOR_CONFIG.courseId
        };

        // Fire and forget - don't await this
        fetch('/quiz/tutor/complete-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(quizData)
        }).then(response => {
            if (response.ok) {
                console.log(`✅ QUIZ ATTEMPT RECORDED in ${(performance.now() - funcStart).toFixed(2)}ms`);
            } else {
                console.warn(`⚠️ QUIZ ATTEMPT RECORDING FAILED: ${response.status}`);
            }
        }).catch(error => {
            console.warn(`⚠️ QUIZ ATTEMPT RECORDING ERROR:`, error);
        });

        console.log(`📊 RECORDING INITIATED in ${(performance.now() - funcStart).toFixed(2)}ms - running in background`);
    } catch (error) {
        console.warn('⚠️ ERROR INITIATING QUIZ ATTEMPT RECORDING:', error);
    }
}

// Timer functionality - DIFFERENT MODES FOR RANDOM VS NORMAL TUTOR
let isRandomQuiz = TUTOR_CONFIG.isRandomQuiz;
let initialDuration = TUTOR_CONFIG.initialDuration; // Only for random quiz
let timeLeft = isRandomQuiz ? initialDuration : 0; // Start at 0 for normal tutor, duration for random
let warningThreshold = Math.floor(initialDuration * 0.3); // Warning at 30% time remaining (only for random)
let dangerThreshold = Math.floor(initialDuration * 0.1); // Danger at 10% time remaining (only for random)

function updateTimer() {
    if (!quizStartTime) {
        console.warn('⚠️ Timer tick skipped - quizStartTime not initialized');
        return;
    }

    const currentTime = new Date();
    const elapsedSeconds = Math.floor((currentTime - quizStartTime) / 1000); // Ensure integer

    if (initialDuration > 0) {
        // Use countdown timer
        timeLeft = Math.max(0, initialDuration - elapsedSeconds);
    } else {
        // Use elapsed timer
        timeLeft = elapsedSeconds;
    }

    // Ensure timeLeft is always an integer
    timeLeft = Math.floor(timeLeft);

    const minutes = Math.floor(Math.abs(timeLeft) / 60);
    const seconds = Math.floor(Math.abs(timeLeft) % 60); // Ensure seconds is integer
    const timerElement = document.getElementById('timer');

    if (timerElement) {
        const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        timerElement.textContent = timeDisplay;

        // Apply color grading based on time remaining
        updateTimerStyling(timerElement, timeLeft);
    }

    // Auto-submit when time runs out (only for countdown mode)
    if (initialDuration > 0 && timeLeft <= 0) {
        console.log('⏰ TIMER EXPIRED - AUTO-SUBMITTING RANDOM TUTOR QUIZ...');
        if (timerInterval) clearTimeout(timerInterval);
        timerInterval = null;

        if (typeof submitQuizRandomTutor === 'function') {
            submitQuizRandomTutor();
        } else {
            console.error('❌ CRITICAL: submitQuizRandomTutor function missing!');
        }
        return;
    }

    // Continue the loop if not expired
    if (timerInterval) clearTimeout(timerInterval);
    timerInterval = setTimeout(updateTimer, 1000);
}

function updateTimerStyling(timerElement, remainingTime) {
    // Remove existing classes
    timerElement.classList.remove('warning', 'danger');

    if (initialDuration <= 0) return; // No styling for elapsed timer

    const percentageRemaining = (remainingTime / initialDuration) * 100;

    if (percentageRemaining <= 10) {
        // Danger: Red styling with animation
        timerElement.classList.add('danger');
    } else if (percentageRemaining <= 30) {
        // Warning: Yellow styling
        timerElement.classList.add('warning');
    }
}

// Helper functions to be fully available
function createAudioElement(type) {
    const audio = new Audio();
    let sourceUrl = '';

    switch (type) {
        case 'correct':
            sourceUrl = '/sounds/Single-correct.mp3';
            break;
        case 'wrong':
            sourceUrl = '/sounds/quiz-wrong.mp3';
            break;
        case 'perfect':
            sourceUrl = '/sounds/quiz-perfect.mp3';
            break;
        case 'partial':
            sourceUrl = '/sounds/quiz-partial.mp3';
            break;
    }

    audio.src = sourceUrl;
    audio.volume = 0.7;
    audio.preload = 'auto';

    audioPool[type].push(audio);

    audio.addEventListener('ended', function () {
        setTimeout(() => {
            returnToPool(audio, type);
        }, 200);
    });

    audio.addEventListener('error', function () {
        console.log(`ℹ️ Fresh audio element error for ${type}, removing from pool`);
        removeFromPool(audio, type);
    });

    console.log(`🔥 LETHAL AUDIO POOL: Created fresh ${type} audio element`);
}

function returnToPool(audio, type = null) {
    if (!type) {
        if (audio.classList.contains('audio-pool-correct') || audio.src.includes('Single-correct')) {
            type = 'correct';
        } else if (audio.classList.contains('audio-pool-wrong') || audio.src.includes('quiz-wrong')) {
            type = 'wrong';
        } else if (audio.classList.contains('audio-pool-perfect') || audio.src.includes('quiz-perfect')) {
            type = 'perfect';
        } else if (audio.classList.contains('audio-pool-partial') || audio.src.includes('quiz-partial')) {
            type = 'partial';
        }
    }

    if (type && audioPool[type]) {
        audio.currentTime = 0;
        audio.pause();

        try {
            audio.stop && audio.stop();
        } catch (e) {
            // Ignore errors if stop method doesn't exist
        }

        if (!audioPool[type].includes(audio)) {
            audioPool[type].push(audio);
            console.log(`🔥 LETHAL AUDIO POOL: Returned ${type} audio to pool (${audioPool[type].length} available)`);
        }
    }
}

function removeFromPool(audio, type) {
    if (audioPool[type]) {
        const index = audioPool[type].indexOf(audio);
        if (index > -1) {
            audioPool[type].splice(index, 1);
        }
    }
}

// Data and initialization
// PRELOAD ALL QUESTION DATA ON PAGE LOAD
document.addEventListener('DOMContentLoaded', function () {
    console.log('🔥 PRELOADING QUESTION DATA FOR INSTANT CHECKING...');

    // Extract question data from DOM
    Object.assign(questionData, TUTOR_CONFIG.questionData);

    console.log('✅ Question data preloaded for', Object.keys(questionData).length, 'questions');

    // PERFORMANCE MONITORING
    window.performanceStats = {
        instantChecks: 0,
        cacheHits: 0,
        networkRequests: 0,
        totalTime: 0
    };

    window.getPerformanceStats = function () {
        console.log('📊 PERFORMANCE STATS:', window.performanceStats);
        const avgTime = window.performanceStats.totalTime / (window.performanceStats.instantChecks + window.performanceStats.cacheHits + window.performanceStats.networkRequests);
        console.log(`📊 AVERAGE RESPONSE TIME: ${Math.round(avgTime)}ms`);
        return window.performanceStats;
    };

    // Initialize GradingAudioController FIRST (for individual question sounds)
    if (typeof GradingAudioController !== 'undefined' && GradingAudioController.init) {
        GradingAudioController.init();
        console.log('✅ GradingAudioController initialized for individual question sounds');
    } else {
        console.warn('⚠️ GradingAudioController not available, falling back to old system');
    }

    // Event delegation for option selection
    document.addEventListener('click', function (e) {
        if (e.target.closest('.option-card')) {
            selectOption(e.target.closest('.option-card'));
        }
    });

    // Initialize highlighting system
    initializeHighlighting();

    // Initialize timer after the tutor page is ready
    setTimeout(() => {
        if (typeof updateTimer === 'function') {
            updateTimer();
            console.log('✅ Timer started successfully');
        } else {
            console.error('❌ updateTimer function not found!');
        }
    }, 100);
});

// ===== TEXT HIGHLIGHTING SYSTEM =====
function initializeHighlighting() {
    highlightToolbar = document.getElementById('highlight-toolbar');
    console.log('🎨 Highlighting system initialized');

    normalizeQuestionTextNodes();

    // Load saved highlights from localStorage
    const savedHighlights = localStorage.getItem('tutorModeHighlights');
    if (savedHighlights) {
        try {
            highlights = JSON.parse(savedHighlights);
            restoreHighlights();
            console.log('📚 Loaded saved highlights:', highlights);
        } catch (e) {
            console.error('Failed to load highlights:', e);
        }
    }

    // Handle text selection - add to question cards specifically
    document.addEventListener('mouseup', function (e) {
        console.log('🖱️ Mouse up detected');
        scheduleTextSelectionHandling(e);
    });
    document.addEventListener('touchend', function (e) {
        console.log('👆 Touch end detected');
        scheduleTextSelectionHandling(e);
    });

    // Hide toolbar when clicking outside
    document.addEventListener('mousedown', function (e) {
        if (!highlightToolbar.contains(e.target) && !e.target.closest('.question-text')) {
            console.log('📋 Clicking outside toolbar - hiding');
            hideToolbar();
        }
    });

    window.addEventListener('resize', function () {
        if (highlightToolbar && highlightToolbar.classList.contains('show') && currentSelection?.questionElement && currentSelection?.range) {
            positionToolbar(currentSelection.questionElement, currentSelection.range);
        }
    });

    window.addEventListener('scroll', function () {
        if (highlightToolbar && highlightToolbar.classList.contains('show') && currentSelection?.questionElement && currentSelection?.range) {
            positionToolbar(currentSelection.questionElement, currentSelection.range);
        }
    }, true);
}

function scheduleTextSelectionHandling(e) {
    const target = e.target;

    window.requestAnimationFrame(() => {
        window.setTimeout(() => {
            handleTextSelection({ target });
        }, 0);
    });
}

function normalizeQuestionTextNodes() {
    document.querySelectorAll('.question-text').forEach(questionElement => {
        if (questionElement.querySelector('mark')) {
            return;
        }

        const normalizedText = questionElement.textContent.trim();
        if (questionElement.textContent !== normalizedText) {
            questionElement.textContent = normalizedText;
        }
    });
}

function handleTextSelection(e) {
    // Skip when the event target is a button or other interactive element —
    // clicking a button is NOT a text-selection gesture and should not be
    // intercepted by the highlight system.
    const INTERACTIVE_SELECTORS = ['button', 'a', 'input', 'select', 'textarea'];
    const target = e.target;
    if (target && INTERACTIVE_SELECTORS.some(sel => target.closest(sel))) {
        return;
    }

    console.log('📝 HANDLING TEXT SELECTION EVENT');

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
        hideToolbar();
        return;
    }

    const range = selection.getRangeAt(0).cloneRange();
    const selectedText = range.toString().trim();

    console.log('📝 Selected text length:', selectedText.length);

    if (selectedText.length === 0) {
        console.log('📝 No text selected, hiding toolbar');
        hideToolbar();
        return;
    }

    const startElement = range.startContainer.nodeType === Node.TEXT_NODE
        ? range.startContainer.parentElement
        : range.startContainer;
    const endElement = range.endContainer.nodeType === Node.TEXT_NODE
        ? range.endContainer.parentElement
        : range.endContainer;
    const questionElement = startElement?.closest?.('.question-text');

    console.log('📝 Found question element:', !!questionElement);

    if (!questionElement || !questionElement.contains(range.startContainer) || !questionElement.contains(range.endContainer) || !questionElement.contains(endElement)) {
        console.log('📝 Selection not within question text, hiding toolbar');
        hideToolbar();
        return;
    }

    const selectionOffsets = getTrimmedRangeOffsets(questionElement, range);
    const selectedFromOffsets = questionElement.textContent.slice(selectionOffsets.start, selectionOffsets.end).trim();
    if (!selectedFromOffsets || selectionOffsets.end <= selectionOffsets.start) {
        hideToolbar();
        return;
    }

    currentSelection = {
        text: selectedFromOffsets,
        range,
        questionElement: questionElement,
        offsets: selectionOffsets
    };

    console.log('📝 Stored selection details:', currentSelection);

    // Position and show toolbar
    showToolbar();
}

function positionToolbar(questionElement, range) {
    if (!highlightToolbar || !questionElement || !range) return;

    const bodyZoom = parseFloat(window.getComputedStyle(document.body).zoom) || 1;
    const questionRect = questionElement.getBoundingClientRect();
    const selectionRect = range.getBoundingClientRect();
    const selectionRects = Array.from(range.getClientRects()).filter(function (rect) {
        return rect.width > 0 || rect.height > 0;
    });
    const anchorRect = selectionRects.length > 0
        ? selectionRects[Math.floor(selectionRects.length / 2)]
        : selectionRect;
    const viewportPadding = 12;
    const questionPadding = 12;
    const toolbarGap = 4;

    highlightToolbar.classList.add('show');
    highlightToolbar.dataset.placement = 'top';

    const toolbarRect = highlightToolbar.getBoundingClientRect();
    const toolbarWidth = toolbarRect.width || (highlightToolbar.offsetWidth * bodyZoom) || 250;
    const toolbarHeight = toolbarRect.height || (highlightToolbar.offsetHeight * bodyZoom) || 52;

    let left = anchorRect.left + (anchorRect.width / 2) - (toolbarWidth / 2);
    let top = selectionRect.top - toolbarHeight - toolbarGap;
    let placement = 'top';

    if (top < viewportPadding) {
        top = selectionRect.bottom + toolbarGap;
        placement = 'bottom';
    }

    const minViewportLeft = viewportPadding;
    const maxViewportLeft = window.innerWidth - toolbarWidth - viewportPadding;
    const minQuestionLeft = questionRect.left + questionPadding;
    const maxQuestionLeft = questionRect.right - toolbarWidth - questionPadding;

    if (minQuestionLeft <= maxQuestionLeft) {
        left = Math.max(minQuestionLeft, Math.min(left, maxQuestionLeft));
    }

    left = Math.max(minViewportLeft, Math.min(left, maxViewportLeft));
    top = Math.max(viewportPadding, Math.min(top, window.innerHeight - toolbarHeight - viewportPadding));

    highlightToolbar.dataset.placement = placement;
    highlightToolbar.style.left = (left / bodyZoom) + 'px';
    highlightToolbar.style.top = (top / bodyZoom) + 'px';
}

function showToolbar() {
    const questionElement = currentSelection?.questionElement;
    const range = currentSelection?.range;
    if (!questionElement || !range) return;

    positionToolbar(questionElement, range);
    highlightToolbar.classList.add('show');
}

function hideToolbar() {
    if (highlightToolbar) {
        highlightToolbar.classList.remove('show');
        highlightToolbar.dataset.placement = 'top';
    }
    currentSelection = null;
}

window.applyHighlight = function (color) {
    if (!currentSelection || !currentSelection.range) {
        console.error('No stored selection available for highlighting');
        hideToolbar();
        return;
    }

    const questionElement = currentSelection.questionElement;
    const questionId = getQuestionId(questionElement);

    if (!questionElement) {
        console.error('No question element found');
        hideToolbar();
        return;
    }

    const selectionOffsets = currentSelection.offsets || getTrimmedRangeOffsets(questionElement, currentSelection.range);
    if (selectionOffsets.end <= selectionOffsets.start) {
        hideToolbar();
        return;
    }

    try {
        const existingIntervals = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
        const updatedIntervals = applyHighlightInterval(existingIntervals, selectionOffsets.start, selectionOffsets.end, color);

        renderQuestionHighlights(questionElement, updatedIntervals);
        persistQuestionHighlights(questionId, questionElement, updatedIntervals);
        console.log(`✅ SUCCESS: Applied ${color} highlight to "${currentSelection.text}"`);
        hideToolbar();
        window.getSelection().removeAllRanges();
    } catch (e) {
        console.error('Failed to apply highlight:', e);
        hideToolbar();
    }
};

window.removeHighlight = function () {
    if (!currentSelection || !currentSelection.range) return;

    const questionElement = currentSelection.questionElement;
    const questionId = getQuestionId(questionElement);
    const selectionOffsets = currentSelection.offsets || getTrimmedRangeOffsets(questionElement, currentSelection.range);
    if (selectionOffsets.end <= selectionOffsets.start) {
        hideToolbar();
        return;
    }

    const existingIntervals = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
    const updatedIntervals = removeHighlightInterval(existingIntervals, selectionOffsets.start, selectionOffsets.end);

    renderQuestionHighlights(questionElement, updatedIntervals);
    persistQuestionHighlights(questionId, questionElement, updatedIntervals);
    hideToolbar();
    window.getSelection().removeAllRanges();
};

function persistQuestionHighlights(questionId, questionElement, intervals) {
    const serializedHighlights = serializeHighlightIntervals(questionElement, intervals);

    if (serializedHighlights.length > 0) {
        highlights[questionId] = serializedHighlights;
    } else {
        delete highlights[questionId];
    }

    localStorage.setItem('tutorModeHighlights', JSON.stringify(highlights));
}

function restoreHighlights() {
    Object.keys(highlights).forEach(questionId => {
        const questionElement = document.querySelector(`#${escapeSelector(questionId)} .question-text`);
        if (!questionElement) return;

        const normalizedHighlights = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
        renderQuestionHighlights(questionElement, normalizedHighlights);
        persistQuestionHighlights(questionId, questionElement, normalizedHighlights);
    });
}

function escapeSelector(value) {
    return window.CSS && typeof window.CSS.escape === 'function'
        ? window.CSS.escape(String(value))
        : String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
}

function getQuestionId(questionElement) {
    const questionCard = questionElement.closest('.question-card');
    return questionCard ? questionCard.id : null;
}

function applyHighlightInterval(intervals, start, end, color) {
    if (end <= start) return mergeHighlightIntervals(intervals);

    const updatedIntervals = [];

    intervals.forEach(interval => {
        if (interval.end <= start || interval.start >= end) {
            updatedIntervals.push({ ...interval });
            return;
        }

        if (interval.start < start) {
            updatedIntervals.push({
                start: interval.start,
                end: start,
                color: interval.color
            });
        }

        if (interval.end > end) {
            updatedIntervals.push({
                start: end,
                end: interval.end,
                color: interval.color
            });
        }
    });

    updatedIntervals.push({ start, end, color });
    return mergeHighlightIntervals(updatedIntervals);
}

function removeHighlightInterval(intervals, start, end) {
    if (end <= start) return mergeHighlightIntervals(intervals);

    const updatedIntervals = [];

    intervals.forEach(interval => {
        if (interval.end <= start || interval.start >= end) {
            updatedIntervals.push({ ...interval });
            return;
        }

        if (interval.start < start) {
            updatedIntervals.push({
                start: interval.start,
                end: start,
                color: interval.color
            });
        }

        if (interval.end > end) {
            updatedIntervals.push({
                start: end,
                end: interval.end,
                color: interval.color
            });
        }
    });

    return mergeHighlightIntervals(updatedIntervals);
}

function normalizeHighlightEntries(questionElement, entries) {
    const textLength = questionElement.textContent.length;
    let normalizedIntervals = [];

    (entries || []).forEach(entry => {
        const color = entry?.color;
        const start = Number(entry?.offset?.start ?? 0);
        let end = Number(entry?.offset?.end ?? start);

        if ((!Number.isFinite(end) || end <= start) && typeof entry?.text === 'string') {
            end = start + entry.text.length;
        }

        if (!color || !Number.isFinite(start) || !Number.isFinite(end)) {
            return;
        }

        const clampedStart = Math.max(0, Math.min(start, textLength));
        const clampedEnd = Math.max(clampedStart, Math.min(end, textLength));
        normalizedIntervals = applyHighlightInterval(normalizedIntervals, clampedStart, clampedEnd, color);
    });

    return mergeHighlightIntervals(normalizedIntervals);
}

function mergeHighlightIntervals(intervals) {
    return intervals
        .filter(interval => interval && interval.color && interval.end > interval.start)
        .sort((a, b) => a.start - b.start || a.end - b.end)
        .reduce((mergedIntervals, interval) => {
            const previousInterval = mergedIntervals[mergedIntervals.length - 1];

            if (previousInterval && previousInterval.color === interval.color && interval.start <= previousInterval.end) {
                previousInterval.end = Math.max(previousInterval.end, interval.end);
            } else {
                mergedIntervals.push({ ...interval });
            }

            return mergedIntervals;
        }, []);
}

function renderQuestionHighlights(questionElement, intervals) {
    const fullText = questionElement.textContent;
    const normalizedIntervals = mergeHighlightIntervals(intervals);
    const fragment = document.createDocumentFragment();
    let cursor = 0;

    normalizedIntervals.forEach(interval => {
        if (interval.start > cursor) {
            fragment.appendChild(document.createTextNode(fullText.slice(cursor, interval.start)));
        }

        const mark = document.createElement('mark');
        mark.className = `highlight-${interval.color}`;
        mark.textContent = fullText.slice(interval.start, interval.end);
        fragment.appendChild(mark);

        cursor = interval.end;
    });

    if (cursor < fullText.length) {
        fragment.appendChild(document.createTextNode(fullText.slice(cursor)));
    }

    questionElement.textContent = '';
    questionElement.appendChild(fragment);
}

function serializeHighlightIntervals(questionElement, intervals) {
    const fullText = questionElement.textContent;

    return mergeHighlightIntervals(intervals).map(interval => ({
        text: fullText.slice(interval.start, interval.end),
        color: interval.color,
        offset: {
            start: interval.start,
            end: interval.end
        }
    }));
}

function getRangeOffsets(container, range) {
    const textLength = container.textContent.length;
    const rawStart = getRangeBoundaryOffset(container, range.startContainer, range.startOffset);
    const rawEnd = getRangeBoundaryOffset(container, range.endContainer, range.endOffset);
    const start = Math.max(0, Math.min(rawStart, rawEnd, textLength));
    const end = Math.max(start, Math.min(Math.max(rawStart, rawEnd), textLength));

    return {
        start,
        end
    };
}

function getTrimmedRangeOffsets(container, range) {
    const offsets = getRangeOffsets(container, range);
    const selectedText = container.textContent.slice(offsets.start, offsets.end);
    const leadingWhitespace = selectedText.match(/^\s*/)[0].length;
    const trailingWhitespace = selectedText.match(/\s*$/)[0].length;

    return {
        start: offsets.start + leadingWhitespace,
        end: Math.max(offsets.start + leadingWhitespace, offsets.end - trailingWhitespace)
    };
}

function getRangeBoundaryOffset(container, node, offset) {
    if (!container || !node || !container.contains(node)) {
        return 0;
    }

    const boundaryRange = document.createRange();
    boundaryRange.selectNodeContents(container);

    try {
        boundaryRange.setEnd(node, offset);
        return boundaryRange.toString().length;
    } catch (e) {
        return getAbsoluteTextOffset(container, node, offset);
    }
}

function getAbsoluteTextOffset(container, node, offset) {
    if (node === container && node.nodeType !== Node.TEXT_NODE) {
        return getTextLengthBeforeChildIndex(container, offset);
    }

    if (node.nodeType !== Node.TEXT_NODE) {
        const nodeStart = getElementTextStartOffset(container, node);
        return nodeStart + getTextLengthBeforeChildIndex(node, offset);
    }

    let absoluteOffset = 0;
    const walker = document.createTreeWalker(
        container,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );

    let currentNode;
    while (currentNode = walker.nextNode()) {
        if (currentNode === node) {
            return absoluteOffset + offset;
        }

        absoluteOffset += currentNode.textContent.length;
    }

    // Fallback: node not found in tree, return container length
    return container.textContent.length;
}

function getTextLengthBeforeChildIndex(element, childIndex) {
    let length = 0;
    const safeIndex = Math.max(0, Math.min(Number(childIndex) || 0, element.childNodes.length));

    for (let index = 0; index < safeIndex; index++) {
        length += element.childNodes[index].textContent.length;
    }

    return length;
}

function getElementTextStartOffset(container, element) {
    let length = 0;
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null, false);

    let currentNode;
    while (currentNode = walker.nextNode()) {
        if (element.contains(currentNode)) {
            return length;
        }

        length += currentNode.textContent.length;
    }

    return length;
}

// ============================================================
// BLOCK 2: Helper functions and grading
// ============================================================

// NOTE: Core tutor-mode globals like questionData, userAnswers, correctAnswers,
// totalAnswers, totalQuestions and currentQuestionIndex are declared in the
// earlier script block to avoid duplicate variable declarations.
// This block only defines helper functions that use those globals.

// LIGHTNING FAST FUNCTION DEFINITIONS 🚀
function selectOption(element) {
    try {
        const container = element.closest('.options-container');
        if (!container) return;

        // OPTIMIZED: Remove selected from siblings only
        const siblings = container.querySelectorAll('.option-card');
        siblings.forEach(opt => opt.classList.remove('selected'));

        // CLEAN SELECTION FEEDBACK
        element.classList.add('selected');

    } catch (error) {
        console.error('selectOption error:', error);
    }
}

// Track first attempt answers and question states

// Jump to specific question from sidebar
window.jumpToQuestion = function (questionNumber) {
    console.log(`🎯 Jumping to question ${questionNumber}`);

    document.querySelectorAll('.question-card').forEach(card => {
        card.style.display = 'none';
    });

    const targetQuestion = document.getElementById(`question-${questionNumber}`);
    if (targetQuestion) {
        targetQuestion.style.display = 'block';
        currentQuestionIndex = questionNumber;
        updateSidebarActiveState(questionNumber);

        const npCurrentQ = document.getElementById('np-current-q');
        if (npCurrentQ) npCurrentQ.textContent = questionNumber;
        
        const npMobileCurrent = document.getElementById('np-mobile-current');
        if (npMobileCurrent) npMobileCurrent.textContent = questionNumber;

        // Check if all questions are answered and show View Results button if needed
        if (totalAnswers === TUTOR_CONFIG.totalQuestions) {
            console.log('🔥 ALL QUESTIONS ANSWERED: Ensuring View Results button is visible');
            const viewResultsBtn = targetQuestion.querySelector('.view-results');
            if (viewResultsBtn) {
                viewResultsBtn.style.display = 'inline-block';
                viewResultsBtn.disabled = false;
            }
        }

        saveTutorDraft();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Navigate to previous question
window.navigatePrevious = function (currentNumber) {
    if (currentNumber > 1) {
        jumpToQuestion(currentNumber - 1);
    }
};

// Navigate to next question
window.navigateNext = function (currentNumber) {
    if (currentNumber < totalQuestions) {
        jumpToQuestion(currentNumber + 1);
    }
};

// Handle "I Don't Know" button
window.handleDontKnow = function (questionNumber, questionId) {
    console.log(`❓ I Don't Know clicked for Q${questionNumber}`);

    if (questionStates[questionNumber]) {
        console.log(`⚠️ Question ${questionNumber} already answered, ignoring`);
        return;
    }

    questionStates[questionNumber] = true;
    firstAttemptAnswers[questionId] = 'Dont know';

    const qData = questionData[questionId];
    if (!qData) {
        console.error(`❌ Question data not found for ID ${questionId}`);
        return;
    }

    totalAnswers++;
    resetCorrectAnswerStreak();
    userAnswers[questionId] = 'dont_know';
    questionResults[questionId] = {
        type: 'dont_know',
        is_correct: false,
        correct_answer: qData.correct_answer,
        explanation: qData.explanation || '',
        showViewResultsButton: totalAnswers === TUTOR_CONFIG.totalQuestions
    };

    updateSidebarCircleState(questionNumber, false);

    renderDontKnowResult(questionNumber, questionId, questionResults[questionId]);
    updateProgress(totalAnswers, TUTOR_CONFIG.totalQuestions);
    saveTutorDraft();
};

// [FIX] REMOVE INFINITE RELOAD LOOP
// if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
//     window.location.reload(true);
// }
// let currentQuestionIndex = 1; // REMOVED: Already declared above
// let correctAnswers = 0; // REMOVED: Already declared above
// let totalAnswers = 0; // REMOVED: Already declared above
let questionStartTime = new Date(); // Track time for each question
let totalStudyTime = 0; // Track total study time

// CRITICAL FIX: Track all user answers throughout the session
// let userAnswers = {}; // REMOVED: Already declared above

// AUTO-GRADE RANDOM QUIZ WHEN TIME RUNS OUT (DEFINED FIRST)
function autoGradeRandomQuiz() {
    console.log('⏰ AUTO-GRADING: Time ran out, grading random quiz with current answers...');

    // Check if we have any answers to grade
    const hasAnswers = Object.keys(userAnswers).length > 0;

    if (!hasAnswers) {
        console.log('ℹ️ No answers provided, showing empty results');
        // Show results with 0 correct answers
        showQuizResults({
            correct: 0,
            total: totalQuestions,
            percentage: 0
        });
        return;
    }

    console.log(`📊 Grading ${Object.keys(userAnswers).length} answers out of ${totalQuestions} questions`);

    // Calculate current score based on answered questions
    let currentCorrect = 0;
    let answeredCount = 0;

    // Count correct answers from answered questions
    for (const [questionId, answer] of Object.entries(userAnswers)) {
        answeredCount++;
        // FIXED: Use questionData instead of undefined 'questions' variable
        const questionDataItem = questionData[questionId];
        if (questionDataItem && answer.toLowerCase().trim() === questionDataItem.correct_answer.toLowerCase().trim()) {
            currentCorrect++;
        }
    }

    console.log(`✅ Auto-grade results: ${currentCorrect}/${answeredCount} answered questions correct`);

    // Calculate percentage based on answered questions only
    const percentage = answeredCount > 0 ? Math.round((currentCorrect / answeredCount) * 100) : 0;

    // Show results immediately
    showQuizResults({
        correct: currentCorrect,
        total: answeredCount,
        percentage: percentage
    });
}


// SUBMIT QUIZ WHEN TIME RUNS OUT - TZAR TECHNIQUE FROM WORKING RANDOM QUIZ
function submitQuizRandomTutor() {
    console.log('⏰ AUTO-SUBMIT: Time ran out, submitting tutor random quiz immediately');

    // 🚀 FORCE IMMEDIATE SUBMISSION - USE CORRECT ROUTE BASED ON QUIZ TYPE
    const isRandomQuiz = TUTOR_CONFIG.isRandomQuiz;
    const gradeUrl = TUTOR_CONFIG.gradeUrl;
    console.log('🎯 GRADE URL:', gradeUrl, '(Random Quiz:', isRandomQuiz + ')');
    console.log('🏘️ QUIZ MODE DATA:', {
        course: TUTOR_CONFIG.gradeMeta.course,
        topic: TUTOR_CONFIG.gradeMeta.topic,
        isRandomQuiz: TUTOR_CONFIG.gradeMeta.isRandomQuiz
    });

    // 🔥 DIRECT DATABASE CHECK BEFORE SUBMISSION - TELL US THE TRUTH!
    if (typeof window.checkDatabaseBeforeSubmit === 'undefined') {
        window.checkDatabaseBeforeSubmit = true;
        console.log('🔍 PRE-SUBMIT: Checking database state...');

        // Try to check if we can connect to the endpoint
        fetch(gradeUrl, {
            method: 'HEAD',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(response => {
            console.log('🌐 PRE-SUBMIT: Endpoint reachable, status:', response.status);
        }).catch(error => {
            console.error('❌ PRE-SUBMIT: Cannot reach grade endpoint!', error);
        });
    }

    const formDataObj = {};
    formDataObj.answers = {};
    formDataObj.time_taken = Math.floor((Date.now() - quizStartTime) / 1000); // Elapsed time
    formDataObj.quiz_mode = TUTOR_CONFIG.quizMode;
    if (TUTOR_CONFIG.randomSessionKey) {
        formDataObj.session_key = TUTOR_CONFIG.randomSessionKey;
    }

    console.log('⏱️ TIME TAKEN:', formDataObj.time_taken);
    console.log('📊 CURRENT ANSWERS BEFORE COLLECTION:', userAnswers);
    console.log('🔥 CRITICAL: About to submit tutor quiz - ensuring data is captured!');
    console.log('🎯 CSRF TOKEN present:', !!document.querySelector('meta[name="csrf-token"]').content);

    // COLLECT ALL ANSWERS FROM FORM ELEMENTS (exactly like working random quiz)
    document.querySelectorAll('[data-question-id]').forEach(questionBox => {
        const questionId = questionBox.getAttribute('data-question-id');
        // Initialize answer as empty string (unanswered)
        formDataObj.answers[questionId] = '';

        const writtenAnswer = questionBox.querySelector('.written-answer');
        const selectedOption = questionBox.querySelector('.option-card.selected');

        if (writtenAnswer) {
            formDataObj.answers[questionId] = writtenAnswer.value.trim();
        } else if (selectedOption) {
            formDataObj.answers[questionId] = selectedOption.getAttribute('data-option');
        }
    });

    console.log('🚀 COLLECTED ANSWERS:', formDataObj.answers);

    fetch(gradeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(formDataObj)
    })
        .then(response => {
            console.log('🚀 AUTO-GRADING RESPONSE STATUS:', response.status);

            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Auto-grading failed');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                clearTutorDraft();
                console.log('✅ AUTO-GRADING SUCCESSFUL:', data);

                // Store quiz results globally for potential review
                window.lastQuizResults = data;

                // Get DOM elements
                const popupScore = document.getElementById('popup-score');
                const popupMessage = document.getElementById('popup-message');
                const popupTitle = document.getElementById('popup-title');
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                const gradeIcon = document.getElementById('grade-icon');

                // Update score display and UI immediately
                const percentage = (data.score / data.total) * 100;

                // Set popup elements
                if (popupScore) popupScore.textContent = `${data.score}/${data.total}`;
                if (popupMessage) popupMessage.textContent = data.message || 'Quiz completed!';

                // Set appropriate grade icon and title
                if (percentage === 100) {
                    if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                    if (gradeIcon) gradeIcon.className = 'grade-icon perfect';
                    if (popupTitle) popupTitle.textContent = 'Perfect Score!';
                } else if (percentage >= 70) {
                    if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                    if (gradeIcon) gradeIcon.className = 'grade-icon good';
                    if (popupTitle) popupTitle.textContent = 'Well Done!';
                } else {
                    if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                    if (gradeIcon) gradeIcon.className = 'grade-icon needs-improvement';
                    if (popupTitle) popupTitle.textContent = 'Keep Learning!';
                }

                // Play grading sound IMMEDIATELY
                playFinalGradingSound(data.score, data.total);

                // Show popup with animation
                if (popupOverlay) popupOverlay.classList.add('show');
                if (popup) {
                    setTimeout(() => popup.classList.add('show'), 100);
                }
            } else {
                console.error('❌ AUTO-GRADING FAILED:', data.error);

                // Get DOM elements for error display
                const popupScore = document.getElementById('popup-score');
                const popupTitle = document.getElementById('popup-title');
                const popupMessage = document.getElementById('popup-message');
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');

                // Show error message
                if (popupTitle) popupTitle.textContent = 'Error';
                if (popupScore) popupScore.textContent = '';
                if (popupMessage) popupMessage.textContent = 'An error occurred while grading. Please try again.';
                if (popupOverlay) popupOverlay.classList.add('show');
                if (popup) {
                    setTimeout(() => popup.classList.add('show'), 100);
                }
            }
        })
        .catch(error => {
            console.error('🚨 AUTO-GRADING NETWORK ERROR:', error);

            // Get DOM elements for error display
            const popupScore = document.getElementById('popup-score');
            const popupTitle = document.getElementById('popup-title');
            const popupMessage = document.getElementById('popup-message');
            const popupOverlay = document.getElementById('popup-overlay');
            const popup = document.getElementById('popup');

            // Show error popup
            if (popupTitle) popupTitle.textContent = 'Error';
            if (popupScore) popupScore.textContent = '';
            if (popupMessage) popupMessage.textContent = 'Failed to submit quiz. Please check your connection and try again.';
            if (popupOverlay) popupOverlay.classList.add('show');
            if (popup) {
                setTimeout(() => popup.classList.add('show'), 100);
            }

            // Re-enable any disabled form elements
            document.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
        });
}

// Update progress bar with timer color integration
function updateProgress(completed, total) {
    const progressFill = document.getElementById('progress-fill');
    const navbarProgressFill = document.getElementById('navbar-progress-fill');
    const progressText = document.getElementById('progress-text');
    const timerElement = document.getElementById('timer');
    const percentage = (completed / total) * 100;

    if (progressFill) {
        progressFill.style.width = `${percentage}%`;

        // Sync progress bar color with timer state
        if (timerElement) {
            if (timerElement.classList.contains('danger')) {
                progressFill.classList.add('danger');
                progressFill.classList.remove('warning');
            } else if (timerElement.classList.contains('warning')) {
                progressFill.classList.add('warning');
                progressFill.classList.remove('danger');
            } else {
                progressFill.classList.remove('warning', 'danger');
            }
        }
    }

    if (navbarProgressFill) {
        navbarProgressFill.style.width = `${percentage}%`;
        
        if (timerElement) {
            if (timerElement.classList.contains('danger')) {
                navbarProgressFill.classList.add('danger');
                navbarProgressFill.classList.remove('warning');
            } else if (timerElement.classList.contains('warning')) {
                navbarProgressFill.classList.add('warning');
                navbarProgressFill.classList.remove('danger');
            } else {
                navbarProgressFill.classList.remove('warning', 'danger');
            }
        }
    }

    if (progressText) {
        progressText.textContent = `${completed} of ${total} questions completed`;
    }
}



// 💀 LETHAL CLIENT-SIDE ANSWER CHECKING 💀

// PRELOAD ALL QUESTION DATA ON PAGE LOAD
// Log mistake to backend
function logMistake(questionId, userAnswer, correctAnswer) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch('/mistakes/log', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            question_id: questionId,
            user_answer: userAnswer,
            correct_answer: correctAnswer
        })
    })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Mistake logged:', data);
        })
        .catch(error => {
            console.log('ℹ️ Mistake logging issue (non-critical):', error.message);
        });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function getAnswerDisplayText(questionCard, answerKey) {
    const normalizedKey = String(answerKey || '').trim();
    if (!questionCard || !normalizedKey) {
        return normalizedKey;
    }

    const escapedKey = window.CSS && typeof window.CSS.escape === 'function'
        ? window.CSS.escape(normalizedKey)
        : normalizedKey.replace(/"/g, '\\"');
    const optionContent = questionCard.querySelector(`.option-card[data-option="${escapedKey}"] .option-content`);
    const optionText = optionContent?.textContent?.trim();
    return optionText || normalizedKey;
}

function getSelectedAnswerDisplayText(questionCard) {
    const selectedOption = questionCard?.querySelector('.option-card.selected .option-content');
    return selectedOption?.textContent?.trim() || 'Unknown';
}

// EXTRACTED ANSWER PROCESSING FUNCTION FOR SPEED
function processAnswerResult(questionNumber, questionId, data, checkBtn) {
    // 💀 BULLETPROOF DUPLICATE PREVENTION - NO MULTIPLE PROCESSING 💀
    const resultKey = `result_processed_${questionNumber}`;
    if (processAnswerResult[resultKey]) {
        console.log(`🔇 BULLETPROOF: Result already processed for Q${questionNumber}, skipping duplicate`);
        return;
    }
    processAnswerResult[resultKey] = true;
    const questionCard = document.getElementById(`question-${questionNumber}`);
    const correctAnswerKey = String(data.correct_answer || '');
    const correctAnswerText = getAnswerDisplayText(questionCard, correctAnswerKey);

    // Update sidebar circle state
    updateSidebarCircleState(questionNumber, data.is_correct);
    questionResults[questionId] = {
        type: 'answered',
        is_correct: Boolean(data.is_correct),
        correct_answer: correctAnswerKey,
        correct_answer_text: correctAnswerText,
        explanation: data.explanation || '',
        showViewResultsButton: totalAnswers === totalQuestions
    };
    updateCorrectAnswerStreak(Boolean(data.is_correct));

    // 🔥 UPDATE SOURCE PROGRESS IN MODAL IF AVAILABLE
    if (data.source_progress) {
        console.log('🎯 TUTOR MODE: Updating source progress from answer check:', data.source_progress);
        updateSourceProgressDisplay(data.source_progress);
    }

    // 🎯 FINAL RESULTS CHECK - IF THIS IS THE LAST QUESTION, SHOW VIEW RESULTS BUTTON
    if (totalAnswers === totalQuestions) {
        console.log('🔥 LAST QUESTION ANSWERED: Showing View Results button instead of Next Question');

        // 🎯 SHOW EXPLANATION FOR LAST QUESTION, THEN VIEW RESULTS BUTTON
        updateQuestionUI(questionNumber, {
            correct: data.is_correct,
            explanation: data.explanation,
            correctAnswer: correctAnswerKey,
            correctAnswerText,
            showNextButton: false, // Don't show next button
            showViewResultsButton: true // Show "View Results" button instead
        });

        return; // DON'T show grading popup yet, let user read explanation and click View Results
    }

    // 🎯 FOR NON-LAST QUESTIONS - SHOW EXPLANATION THEN NEXT BUTTON AS NORMAL
    console.log('🎓 TUTOR MODE: Showing explanation for Q${questionNumber}, total answered: ${totalAnswers}/${totalQuestions}');

    // FOR NON-LAST QUESTIONS - SHOW NEXT BUTTON AS NORMAL
    updateQuestionUI(questionNumber, {
        correct: data.is_correct,
        explanation: data.explanation,
        correctAnswer: correctAnswerKey,
        correctAnswerText,
        showNextButton: true // Flag to show next button for non-last questions
    });
    saveTutorDraft();
}

// 🔥 UPDATE SOURCE PROGRESS DISPLAY IN MODAL
function updateSourceProgressDisplay(sourceProgress) {
    console.log('📊 Updating source progress display:', sourceProgress);

    // Find the source progress element in the modal
    const sourceElement = document.querySelector(`[data-source="${sourceProgress.source}"]`);
    if (sourceElement) {
        const percentage = Math.round(sourceProgress.percentage || 0);
        const solvedCorrectly = Number(sourceProgress.correct || 0);
        const totalQuestions = Number(sourceProgress.total || 0);
        const progressText = `${solvedCorrectly}/${totalQuestions} (${percentage}%)`;

        // Update the progress text
        const progressSpan = sourceElement.querySelector('.source-progress');
        if (progressSpan) {
            progressSpan.textContent = progressText;
        }

        // Update progress bar if exists
        const progressBar = sourceElement.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }

        console.log(`✅ Updated ${sourceProgress.source} progress to ${progressText}`);
    } else {
        console.warn(`⚠️ Could not find source element for: ${sourceProgress.source}`);
    }
}

window.checkAnswer = function checkAnswer(questionNumber, questionId) {
    const startTime = performance.now();
    console.log(`🚀 CHECKING ANSWER: Question ${questionNumber}, ID ${questionId}`);

    // 💀 BULLETPROOF DUPLICATE PREVENTION - NO MULTIPLE PROCESSING 💀
    const processingKey = `processing_${questionNumber}`;
    if (checkAnswer[processingKey]) {
        console.log(`🔇 BULLETPROOF: Question ${questionNumber} already being processed, skipping duplicate`);
        return;
    }
    checkAnswer[processingKey] = true;

    // FIX: Set question state immediately to prevent "I Don't Know" button from being usable
    questionStates[questionNumber] = true;

    // INSTANT DOM ACCESS
    const questionCard = document.getElementById(`question-${questionNumber}`);
    if (!questionCard) {
        console.error(`❌ Question card not found: question-${questionNumber}`);
        return;
    }

    const writtenAnswer = questionCard.querySelector('.written-answer');
    const selectedOption = questionCard.querySelector('.option-card.selected');
    const checkBtn = questionCard.querySelector('.check-answer');

    // INSTANT ANSWER EXTRACTION
    let answer = '';
    if (writtenAnswer) {
        answer = writtenAnswer.value.trim();
    } else if (selectedOption) {
        answer = selectedOption.getAttribute('data-option');
    }

    if (!answer) {
        console.warn('⚠️ No answer selected');

        // Show user-friendly warning message
        const questionCard = document.getElementById(`question-${questionNumber}`);
        if (questionCard) {
            const feedbackDiv = questionCard.querySelector('.answer-feedback');

            // Hide any existing feedback first
            feedbackDiv.style.display = 'none';

            // Clear processing flag to allow retry
            delete checkAnswer[processingKey];

            // Show warning after a brief delay
            setTimeout(() => {
                feedbackDiv.style.display = 'block';
                const alertDiv = feedbackDiv.querySelector('.alert');
                alertDiv.className = 'alert alert-warning';
                feedbackDiv.querySelector('.result-text').textContent = 'Please select an answer first!';
                feedbackDiv.querySelector('.explanation-content').innerHTML = `
                            <div class="explanation-with-icon warning-inline">
                                <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                                <div class="explanation-text">You need to choose an answer before checking. Select one of the options above and try again.</div>
                            </div>
                        `;
            }, 300);
        }

        return;
    }

    console.log(`📝 Answer selected: "${answer}"`);

    // 💀 INSTANT CLIENT-SIDE CHECKING - NO NETWORK DELAY 💀
    // Written questions MUST go to the server for intelligent fuzzy grading
    if (questionData[questionId] && questionData[questionId].question_type !== 'written') {
        console.log(`⚡ INSTANT CLIENT-SIDE CHECK for Q${questionId}`);

        const qData = questionData[questionId];
        const isCorrect = answer.toLowerCase().trim() === qData.correct_answer.toLowerCase().trim();

        const instantData = {
            success: true,
            is_correct: isCorrect,
            correct_answer: qData.correct_answer,
            explanation: qData.explanation
        };

        console.log(`✅ INSTANT RESULT: ${isCorrect ? 'CORRECT' : 'WRONG'} (0ms)`);

        // TRACK PERFORMANCE
        window.performanceStats.instantChecks++;
        window.performanceStats.totalTime += (performance.now() - startTime);

        // INSTANT PROCESSING
        userAnswers[questionId] = answer;
        totalAnswers++;
        if (isCorrect) correctAnswers++;

        // INSTANT UI UPDATE
        processAnswerResult(questionNumber, questionId, instantData, checkBtn);
        // Clear processing flag
        delete checkAnswer[processingKey];
        return;
    }

    // FALLBACK: CHECK CACHE
    const cacheKey = `${questionId}_${answer}`;
    if (answerCache[cacheKey]) {
        console.log(`⚡ CACHE HIT! Instant response for Q${questionId}`);
        const cachedData = answerCache[cacheKey];

        // TRACK PERFORMANCE
        window.performanceStats.cacheHits++;
        window.performanceStats.totalTime += (performance.now() - startTime);

        // INSTANT PROCESSING FROM CACHE
        userAnswers[questionId] = answer;
        totalAnswers++;
        if (cachedData.is_correct) correctAnswers++;

        // INSTANT UI UPDATE FROM CACHE
        processAnswerResult(questionNumber, questionId, cachedData, checkBtn);
        // Clear processing flag
        delete checkAnswer[processingKey];
        return;
    }

    // LAST RESORT: NETWORK REQUEST
    console.warn(`⚠️ FALLBACK TO NETWORK for Q${questionId}`);

    // INSTANT UI FEEDBACK
    if (checkBtn) {
        checkBtn.disabled = true;
        checkBtn.textContent = 'Checking...';
        checkBtn.style.background = '#6c757d';
    }

    // 💀 F16 RAPTOR SPEED FETCH 💀
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000); // 2 second timeout

    // CACHE CSRF TOKEN FOR SPEED
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/quiz/tutor/check/${questionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive'
        },
        body: JSON.stringify({ answer }),
        signal: controller.signal,
        keepalive: true
    })
        .then(response => {
            clearTimeout(timeoutId);
            const responseTime = performance.now() - startTime;
            console.log(`⚡ Server response in ${Math.round(responseTime)}ms (Status: ${response.status})`);

            if (!response.ok) {
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            }

            return response.json();
        })
        .then(data => {
            const totalTime = performance.now() - startTime;
            console.log(`✅ Answer processed in ${Math.round(totalTime)}ms`);
            console.log('📊 Server performance:', data.performance_metrics);

            if (!data.success) {
                throw new Error(data.message || 'Answer check failed');
            }

            // CACHE THE RESULT FOR FUTURE INSTANT ACCESS
            answerCache[cacheKey] = data;
            console.log(`💾 Cached result for Q${questionId}_${answer}`);

            // INSTANT ANSWER STORAGE
            userAnswers[questionId] = answer;
            console.log(`💾 Stored answer: Q${questionId} = "${answer}"`);

            // INSTANT COUNTERS
            totalAnswers++;
            if (data.is_correct) correctAnswers++;

            // PROCESS RESULT
            processAnswerResult(questionNumber, questionId, data, checkBtn);
            // Clear processing flag
            delete checkAnswer[processingKey];
        })
        .catch(error => {
            // LETHAL FIX: Silent error handling - don't spam console or alert user
            console.log('ℹ️ Answer check encountered an issue, providing fallback response');

            // Provide fallback response instead of failing
            const fallbackData = {
                success: false,
                is_correct: false,
                error: 'Connection issue',
                correct_answer: 'Unable to determine',
                explanation: 'Please check your connection and try again'
            };

            // Process the fallback data as if it was a normal response
            totalAnswers++;
            resetCorrectAnswerStreak();
            // Don't increment correctAnswers since we don't know if it's correct

            // Update UI with fallback data
            updateQuestionUI(questionNumber, {
                correct: fallbackData.is_correct,
                explanation: fallbackData.explanation,
                correctAnswer: fallbackData.correct_answer
            });

            // Re-enable inputs
            questionCard.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);

            // Continue with quiz if all questions are answered
            if (totalAnswers === totalQuestions) {
                const percentage = (correctAnswers / totalAnswers) * 100;

                // Show results immediately (sound will be played in showQuizResults)
                showQuizResults({
                    correct: correctAnswers,
                    total: totalAnswers,
                    percentage: percentage
                });
            }

            // Clear processing flag
            delete checkAnswer[processingKey];
        });
}

// Function to show next question
function showNextQuestion(currentNumber) {
    const currentQuestion = document.getElementById(`question-${currentNumber}`);
    const nextQuestion = document.getElementById(`question-${currentNumber + 1}`);

    if (currentQuestion) {
        currentQuestion.style.display = 'none';
    }

    if (nextQuestion) {
        nextQuestion.style.display = 'block';
        currentQuestionIndex = currentNumber + 1;
        updateProgress(currentNumber + 1, totalQuestions);
        saveTutorDraft();
    } else {
        // No more questions, show final results
        const percentage = (correctAnswers / totalAnswers) * 100;

        // Show results without playing sound here (sound will be played in showQuizResults)
        showQuizResults({
            correct: correctAnswers,
            total: totalAnswers,
            percentage: percentage
        });
    }
}

// Function to show final results when "View Results" button is clicked
function showFinalResults() {
    console.log('🎯 VIEW RESULTS: User clicked "View Results" button for final question');
    const percentage = (correctAnswers / totalAnswers) * 100;

    showQuizResults({
        correct: correctAnswers,
        total: totalAnswers,
        percentage: percentage
    });
}

// Function to update question UI after checking answer
function updateQuestionUI(questionNumber, data) {
    const questionCard = document.getElementById(`question-${questionNumber}`);
    const feedbackDiv = questionCard.querySelector('.answer-feedback');
    const optionsContainer = questionCard.querySelector('.options-container');

    // 💀 BULLETPROOF SOUND PREVENTION - NO DOUBLE PLAYBACK 💀
    const soundKey = `sound_played_${questionNumber}`;
    const hasPlayedSound = questionCard.dataset[soundKey];

    // Show feedback
    feedbackDiv.style.display = 'block';
    const alertDiv = feedbackDiv.querySelector('.alert');
    alertDiv.className = `alert ${data.correct ? 'alert-success' : 'alert-danger'}`;

    // Update feedback text
    if (data.correct) {
        feedbackDiv.querySelector('.result-text').textContent = 'Correct! 🎉';
    } else {
        const userAnswer = getSelectedAnswerDisplayText(questionCard);
        const correctAnswerDisplay = data.correctAnswerText || getAnswerDisplayText(questionCard, data.correctAnswer);
        feedbackDiv.querySelector('.result-text').textContent = `You selected: ${userAnswer} | Correct: ${correctAnswerDisplay}`;
    }

    // 🎯 PLAY IMMEDIATE SOUND FOR ALL INDIVIDUAL ANSWERS USING UNIFIED CONTROLLER
    if (!hasPlayedSound) {
        console.log(`🔥 PLAYING IMMEDIATE SOUND: (${data.correct ? 'CORRECT' : 'WRONG'}) - FIRST TIME ONLY`);
        if (data.correct) {
            GradingAudioController.playCorrectSound();
        } else {
            GradingAudioController.playWrongSound();
        }
        questionCard.dataset[soundKey] = 'true';
    } else {
        console.log(`🔇 SOUND ALREADY PLAYED FOR Q${questionNumber}, skipping duplicate`);
    }

    // Show explanation and correct answer
    let explanationHtml = data.explanation || '';
    let correctAnswerHeader = '';
    const correctAnswerDisplay = data.correctAnswerText || getAnswerDisplayText(questionCard, data.correctAnswer);
    if (correctAnswerDisplay) {
        // Always show correct answer in green, regardless of whether user got it right or wrong
        correctAnswerHeader = `
                    <div class="answer-header">
                        <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                        <strong>Correct Answer: ${escapeHtml(correctAnswerDisplay)}</strong>
                    </div>`;
    }

    // Create aligned result text with doctor icon
    const explanationWithIcon = `
                <div class="explanation-with-icon">
                    ${correctAnswerHeader}
                    <div class="explanation-text">
                        ${explanationHtml}
                    </div>
                </div>
            `;
    feedbackDiv.querySelector('.explanation-content').innerHTML = explanationWithIcon;

    // Hide the separate result text element since we now have it inline
    feedbackDiv.querySelector('.result-text').style.display = 'none';

    // Update option styling
    const selectedOption = questionCard.querySelector('.option-card.selected');
    const writtenAnswer = questionCard.querySelector('.written-answer');

    if (writtenAnswer) {
        writtenAnswer.disabled = true;
        writtenAnswer.classList.add(data.correct ? 'correct' : 'incorrect');
    } else if (selectedOption) {
        // Update multiple choice styling
        const options = questionCard.querySelectorAll('.option-card');
        options.forEach(option => {
            option.classList.remove('selected', 'correct', 'incorrect');
            if (option === selectedOption) {
                option.classList.add(data.correct ? 'correct' : 'incorrect');
            } else if (!data.correct && option.getAttribute('data-option') === data.correctAnswer) {
                option.classList.add('correct');
            }
        });
    }

    // Show next question button and hide check answer button
    const checkAnswerBtn = questionCard.querySelector('.check-answer');
    const nextQuestionBtn = questionCard.querySelector('.next-question');
    const viewResultsBtn = questionCard.querySelector('.view-results'); // Add View Results button
    const dontKnowBtn = questionCard.querySelector('.btn-dont-know'); // FIX: Also hide "I Don't Know" button

    if (checkAnswerBtn) checkAnswerBtn.style.display = 'none';
    if (dontKnowBtn) dontKnowBtn.style.display = 'none'; // FIX: Disable "I Don't Know" button after answer is checked

    // Handle final question differently - show "View Results" button
    if (data.showViewResultsButton) {
        if (viewResultsBtn) {
            viewResultsBtn.style.display = 'block';
            viewResultsBtn.disabled = false;
            // Ensure it hasn't been created yet
            if (!viewResultsBtn) {
                const actionsDiv = questionCard.querySelector('.actions');
                const newViewResultsBtn = document.createElement('button');
                newViewResultsBtn.className = 'btn btn-success view-results';
                newViewResultsBtn.onclick = () => {
                    const percentage = (correctAnswers / totalAnswers) * 100;
                    showQuizResults({
                        correct: correctAnswers,
                        total: totalAnswers,
                        percentage: percentage
                    });
                };
                newViewResultsBtn.textContent = 'View Results';
                actionsDiv.appendChild(newViewResultsBtn);
            }
        }
    } else if (nextQuestionBtn) {
        nextQuestionBtn.style.display = 'block';
        nextQuestionBtn.disabled = false; // FIX: Ensure Next Question is enabled
    }

    // FIX: Update progress bar immediately after checking answer
    updateProgress(totalAnswers, totalQuestions);
}

// 🎯 INSTANT RESULTS: Show popup immediately, background operations are completely fire-and-forget
function showQuizResults(results) {
    console.log('🚀 INSTANT RESULTS: Starting immediate quiz completion process', results);
    const completionStart = performance.now();
    clearTutorDraft();

    // ⚡ INSTANT UI RENDERING - SHOW POPUP IMMEDIATELY
    const popupOverlay = document.getElementById('popup-overlay');
    const popup = document.getElementById('popup');
    const gradeIcon = document.getElementById('grade-icon');
    const popupTitle = document.getElementById('popup-title');
    const popupScore = document.getElementById('popup-score');
    const popupMessage = document.getElementById('popup-message');

    // Show score as X/Y format
    popupScore.textContent = `${results.correct}/${results.total}`;

    // Set appropriate UI based on score
    const percentage = (results.correct / results.total) * 100;
    if (percentage === 100) {
        gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
        gradeIcon.className = 'grade-icon perfect';
        popupTitle.textContent = 'Perfect Score!';
        popupMessage.textContent = 'Outstanding! You\'ve mastered this topic!';

        // FIREWORKS FOR PERFECT SCORE
        if (typeof confetti !== 'undefined') {
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            const originX = isSafari ? 0.48 : 0.62;

            setTimeout(() => {
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { x: originX, y: 0.45 },
                    colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                    zIndex: 10000
                });
            }, 300); // Small delay for effect
        }
    } else if (percentage >= 70) {
        gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
        gradeIcon.className = 'grade-icon good';
        popupTitle.textContent = 'Well Done!';
        popupMessage.textContent = 'Good progress! Keep up the great work!';
    } else {
        gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
        gradeIcon.className = 'grade-icon needs-improvement';
        popupTitle.textContent = 'Keep Learning!';
        popupMessage.textContent = 'Don\'t give up! Practice makes perfect!';
    }

    // Show success popup with animation IMMEDIATELY
    popupOverlay.classList.add('show');
    setTimeout(() => popup.classList.add('show'), 100);

    // 🎉 FUNCTION COMPLETES INSTANTLY HERE - NO AWAITS OR BLOCKING OPERATIONS
    const uiTime = performance.now() - completionStart;
    console.log(`⚡ INSTANT UI: Popup shown in ${(uiTime).toFixed(2)}ms - COMPLETING FUNCTION NOW`);

    // 🔥 FIRE-AND-FORGET BACKGROUND OPERATIONS - NO AWAITING
    const isRandomQuiz = TUTOR_CONFIG.isRandomQuiz;

    // Background database recording (completely fire-and-forget)
    if (!isRandomQuiz) {
        recordTutorQuizAttempt(results); // 🔥 NOT AWAITING THIS
    } else {
        console.log('🎓 TUTOR MODE: Skipping quiz attempt recording for learning mode');
    }

    // Background sound playback (completely fire-and-forget)
    playFinalGradingSound(results.correct, results.total); // 🔥 NOT AWAITING THIS

    console.log(`🎉 INSTANT RESULTS: Function completed in ${(performance.now() - completionStart).toFixed(2)}ms - background operations running independently`);

    // 🎯 RETURN IMMEDIATELY - NO CONTEXTWAITING
    return;
}



// 🎯 UNIFIED GRADING SOUND USING GradingAudioController
function playFinalGradingSound(score, total) {
    const funcStart = performance.now();
    console.log('🔥 PLAY FINAL GRADING SOUND - UNIFIED CONTROLLER');

    const percentage = (score / total) * 100;
    console.log(`📊 FINAL SCORE: ${score}/${total} = ${percentage.toFixed(1)}%`);

    // 🎯 USE UNIFIED CONTROLLER FOR CONSISTENT SOUND SELECTION
    GradingAudioController.playGradingSound(percentage, total);

    console.log(`🔥 UNIFIED GRADING SOUND COMPLETED in ${(performance.now() - funcStart).toFixed(2)}ms`);
}

// 💀 LETHAL AUDIO POOL DEBUG FUNCTIONS 💀
window.debugAudioPool = function () {
    console.log('🔥 LETHAL AUDIO POOL DEBUG:');
    console.log('Correct pool size:', audioPool.correct.length);
    console.log('Wrong pool size:', audioPool.wrong.length);
    console.log('Perfect pool size:', audioPool.perfect.length);
    console.log('Partial pool size:', audioPool.partial.length);
    console.log('Total pool elements:', audioPool.correct.length + audioPool.wrong.length + audioPool.perfect.length + audioPool.partial.length);
};

window.testAudioPool = function () {
    console.log('🔥 LETHAL AUDIO POOL: Testing instant playback...');
    setTimeout(() => playInstantSound(true), 100);   // Test correct
    setTimeout(() => playInstantSound(false), 500);  // Test wrong
    setTimeout(() => playInstantSound(true), 1000);  // Test correct again
};



// ============================================================
// BLOCK 3: Source progress handling
// ============================================================

// Handle source progress updates from tutor quiz completion
document.addEventListener('sourceProgressUpdated', function (e) {
    console.log('🔥 TUTOR MODE: Source progress update received:', e.detail);
    const { topicId, progress, availableSources, allSourcesMastered } = e.detail;

    // Update the source information display if it exists
    const sourcesInfo = document.querySelector('.sources-info');
    if (sourcesInfo && progress) {
        console.log('✅ TUTOR MODE: Updating source progress display');

        // Update the source display text with progress information
        const selectedSources = TUTOR_CONFIG.selectedSources;
        if (selectedSources && selectedSources.length > 0 && !selectedSources.includes('all')) {
            let progressText = '';
            selectedSources.forEach(source => {
                const sourceProgress = progress[source];
                if (sourceProgress) {
                    const percentage = Math.round(sourceProgress.percentage);
                    const status = percentage >= 90 ? '✅' : percentage >= 50 ? '🔄' : percentage > 0 ? '❌' : '⭕';
                    const solvedCorrectly = Number(sourceProgress.correct ?? sourceProgress.completed ?? 0);
                    progressText += `${source}: ${status} ${percentage}% (${solvedCorrectly}/${sourceProgress.total}) `;
                }
            });

            if (progressText) {
                sourcesInfo.innerHTML = `
                            <span class="text-muted">
                                <strong>Progress:</strong> ${progressText}
                            </span>
                        `;
            }
        }
    }

    console.log('✅ TUTOR MODE: Source progress update completed');
});

// ============================================================
// BLOCK 4: Review mode initialization
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const reviewAnswersButton = document.getElementById('review-answers-btn');

    if (!reviewAnswersButton) {
        return;
    }

    window.enterTutorReviewMode = function enterTutorReviewMode() {
        if (document.body.classList.contains('tutor-review-mode')) {
            return;
        }

        document.body.classList.add('tutor-review-mode');

        const popupOverlay = document.getElementById('popup-overlay');
        const popup = document.getElementById('popup');
        const timerElement = document.getElementById('timer');
        popup?.classList.remove('show');
        popupOverlay?.classList.remove('show');

        if (timerInterval) {
            clearTimeout(timerInterval);
            timerInterval = null;
        }

        if (timerElement) {
            timerElement.classList.remove('warning', 'danger');
        }

        document.querySelectorAll('.check-answer, .next-question, .view-results, .btn-dont-know').forEach((element) => {
            element.style.display = 'none';
        });

        document.querySelectorAll('.option-card').forEach((optionCard) => {
            optionCard.style.pointerEvents = 'none';
            optionCard.style.cursor = 'default';
            optionCard.setAttribute('tabindex', '-1');
        });

        document.querySelectorAll('.written-answer').forEach((writtenAnswer) => {
            writtenAnswer.readOnly = true;
            writtenAnswer.disabled = true;
            writtenAnswer.classList.add('review-locked');
        });

        document.querySelectorAll('.options-container').forEach((container) => {
            const questionId = container.dataset.questionId;
            const normalizedQuestionId = String(questionId || '');

            if (!normalizedQuestionId) {
                return;
            }

            const questionCard = container.closest('.question-card');
            const feedbackDiv = questionCard?.querySelector('.answer-feedback');
            const alertDiv = feedbackDiv?.querySelector('.alert');
            const resultText = feedbackDiv?.querySelector('.result-text');
            const explanationContent = feedbackDiv?.querySelector('.explanation-content');
            const qData = questionData[normalizedQuestionId] || questionData[Number(normalizedQuestionId)];
            const submittedAnswer = userAnswers[normalizedQuestionId] ?? userAnswers[Number(normalizedQuestionId)] ?? null;

            if (!feedbackDiv || !alertDiv || !resultText || !explanationContent || !qData) {
                return;
            }

            feedbackDiv.style.display = 'block';

            const writtenAnswer = container.querySelector('.written-answer');
            const isCorrect = submittedAnswer !== null
                && String(submittedAnswer).trim().toLowerCase() === String(qData.correct_answer || '').trim().toLowerCase();

            if (writtenAnswer) {
                alertDiv.className = `alert ${isCorrect ? 'alert-success' : 'alert-danger'}`;
                resultText.textContent = isCorrect ? 'Correct Answer' : 'Review Answer';



                return;
            }

            const optionCards = container.querySelectorAll('.option-card');
            optionCards.forEach((optionCard) => {
                const optionLetter = optionCard.getAttribute('data-option');
                optionCard.classList.remove('selected', 'correct', 'incorrect');

                if (optionLetter === qData.correct_answer) {
                    optionCard.classList.add('correct');
                } else if (submittedAnswer !== null && optionLetter === String(submittedAnswer) && optionLetter !== qData.correct_answer) {
                    optionCard.classList.add('incorrect');
                }
            });

            alertDiv.className = `alert ${isCorrect ? 'alert-success' : 'alert-danger'}`;
            resultText.textContent = isCorrect ? 'Correct Answer' : 'Review Answer';


        });

        if (typeof window.jumpToQuestion === 'function') {
            window.jumpToQuestion(1);
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    reviewAnswersButton.addEventListener('click', function () {
        window.enterTutorReviewMode();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.written-answer').forEach((textarea) => {
        textarea.addEventListener('input', saveTutorDraft);
    });

    document.querySelectorAll('.option-card').forEach((optionCard) => {
        optionCard.addEventListener('click', function () {
            requestAnimationFrame(saveTutorDraft);
        });
    });

    restoreTutorDraftState();
    saveTutorDraft();
});

window.addEventListener('beforeunload', saveTutorDraft);
document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
        saveTutorDraft();
    }
});

// ============================================================
// KEYBOARD ARROW NAVIGATION (Left / Right to switch questions)
// ============================================================
let editModalOpen = false;
document.addEventListener('keydown', function (e) {
    // Don't intercept when typing in inputs/selects or when edit modal is open
    const tag = (e.target.tagName || '').toLowerCase();
    if (editModalOpen) return;
    const activeQuestionCard = document.getElementById(`question-${currentQuestionIndex}`);

    if (e.key === 'Enter') {
        const isTypingInTextarea = tag === 'textarea';
        const isTypingInInput = tag === 'input' || tag === 'select';

        if (isTypingInInput) {
            return;
        }

        if (activeQuestionCard) {
            const checkButton = activeQuestionCard.querySelector('.check-answer');
            const nextButton = activeQuestionCard.querySelector('.next-question');
            const viewResultsButton = activeQuestionCard.querySelector('.view-results');
            const isCheckVisible = checkButton
                && checkButton.style.display !== 'none'
                && !checkButton.disabled;
            const isNextVisible = nextButton
                && nextButton.style.display !== 'none'
                && !nextButton.disabled;
            const isViewResultsVisible = viewResultsButton
                && viewResultsButton.style.display !== 'none'
                && !viewResultsButton.disabled;

            if (isCheckVisible) {
                e.preventDefault();

                if (isTypingInTextarea && e.shiftKey) {
                    return;
                }

                const questionId = getQuestionIdFromCard(activeQuestionCard);
                const questionNumber = getQuestionNumberFromCard(activeQuestionCard);

                if (questionId && questionNumber && typeof window.checkAnswer === 'function') {
                    window.checkAnswer(questionNumber, Number(questionId));
                }
                return;
            }

            if (isNextVisible) {
                e.preventDefault();

                const questionNumber = getQuestionNumberFromCard(activeQuestionCard);
                if (questionNumber && typeof window.showNextQuestion === 'function') {
                    window.showNextQuestion(questionNumber);
                }
                return;
            }

            if (isViewResultsVisible) {
                e.preventDefault();

                if (typeof window.showFinalResults === 'function') {
                    window.showFinalResults();
                }
                return;
            }
        }

        if (isTypingInTextarea) {
            return;
        }
    }

    if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && activeQuestionCard) {
        const isTypingInTextField = tag === 'input' || tag === 'textarea' || tag === 'select';
        if (isTypingInTextField) {
            return;
        }

        const optionCards = Array.from(activeQuestionCard.querySelectorAll('.option-card'));
        if (optionCards.length > 0) {
            e.preventDefault();

            const currentIndex = optionCards.findIndex((optionCard) => optionCard.classList.contains('selected'));
            const direction = e.key === 'ArrowDown' ? 1 : -1;
            const nextIndex = currentIndex === -1
                ? (direction === 1 ? 0 : optionCards.length - 1)
                : (currentIndex + direction + optionCards.length) % optionCards.length;

            selectOption(optionCards[nextIndex]);
            requestAnimationFrame(saveTutorDraft);
            return;
        }
    }

    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        if (typeof window.navigatePrevious === 'function' && currentQuestionIndex > 1) {
            window.navigatePrevious(currentQuestionIndex);
        }
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        if (typeof window.navigateNext === 'function' && currentQuestionIndex < totalQuestions) {
            window.navigateNext(currentQuestionIndex);
        }
    }
});

// ============================================================
// ADMIN EDIT QUESTION MODAL (only initialized for admins)
// ============================================================
if (TUTOR_CONFIG.isAdmin) {
    (function () {
        // Track original values to detect unsaved changes
        let originalValues = {};

        function hasUnsavedChanges() {
            const fields = ['edit-question-text', 'edit-option-a', 'edit-option-b', 'edit-option-c', 'edit-option-d', 'edit-explanation'];
            for (const id of fields) {
                const el = document.getElementById(id);
                if (el && el.value !== (originalValues[id] || '')) return true;
            }
            const checkedRadio = document.querySelector('input[name="edit-correct"]:checked');
            if ((checkedRadio ? checkedRadio.value : '') !== (originalValues['correct_answer'] || '')) return true;
            return false;
        }

        function confirmClose() {
            if (hasUnsavedChanges()) {
                return confirm('You have unsaved changes. Discard them?');
            }
            return true;
        }

        window.openEditModal = function (questionId) {
            // Sanitize questionId to integer
            questionId = parseInt(questionId, 10);
            if (!questionId || questionId <= 0) return;

            const qData = TUTOR_CONFIG.questionData[questionId];
            if (!qData) return;

            document.getElementById('edit-question-id').value = questionId;
            document.getElementById('edit-question-text').value = qData.question_text || '';
            document.getElementById('edit-option-a').value = qData.option_a || '';
            document.getElementById('edit-option-b').value = qData.option_b || '';
            document.getElementById('edit-option-c').value = qData.option_c || '';
            document.getElementById('edit-option-d').value = qData.option_d || '';
            document.getElementById('edit-explanation').value = qData.explanation || '';

            // Set correct answer radio
            const correctRadio = document.getElementById('edit-correct-' + (qData.correct_answer || 'A'));
            if (correctRadio) correctRadio.checked = true;

            // Snapshot original values for unsaved-changes detection
            originalValues = {
                'edit-question-text': qData.question_text || '',
                'edit-option-a': qData.option_a || '',
                'edit-option-b': qData.option_b || '',
                'edit-option-c': qData.option_c || '',
                'edit-option-d': qData.option_d || '',
                'edit-explanation': qData.explanation || '',
                'correct_answer': qData.correct_answer || 'A'
            };

            // Clear status
            const status = document.getElementById('edit-status');
            status.textContent = '';
            status.className = 'edit-status';

            document.getElementById('edit-save-btn').disabled = false;
            document.getElementById('edit-question-overlay').classList.add('active');
            editModalOpen = true;

            // Small delay to let the modal animation finish before focusing
            setTimeout(() => document.getElementById('edit-question-text').focus(), 100);
        };

        window.closeEditModal = function () {
            if (!confirmClose()) return;
            document.getElementById('edit-question-overlay').classList.remove('active');
            editModalOpen = false;
            originalValues = {};
        };

        // Internal close that skips confirmation (used after successful save)
        function forceCloseModal() {
            document.getElementById('edit-question-overlay').classList.remove('active');
            editModalOpen = false;
            originalValues = {};
        }

        window.saveQuestionEdit = function () {
            const questionId = parseInt(document.getElementById('edit-question-id').value, 10);
            if (!questionId || questionId <= 0) return;

            const saveBtn = document.getElementById('edit-save-btn');
            const status = document.getElementById('edit-status');

            const correctRadio = document.querySelector('input[name="edit-correct"]:checked');
            if (!correctRadio) {
                status.textContent = 'Please select the correct answer.';
                status.className = 'edit-status error';
                return;
            }

            const payload = {
                question: document.getElementById('edit-question-text').value.trim(),
                option_a: document.getElementById('edit-option-a').value.trim(),
                option_b: document.getElementById('edit-option-b').value.trim(),
                option_c: document.getElementById('edit-option-c').value.trim(),
                option_d: document.getElementById('edit-option-d').value.trim(),
                correct_answer: correctRadio.value,
                explanation: document.getElementById('edit-explanation').value.trim()
            };

            if (!payload.question) {
                status.textContent = 'Question text cannot be empty.';
                status.className = 'edit-status error';
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            status.textContent = '';
            status.className = 'edit-status';

            fetch('/quiz/tutor/question/' + questionId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(r => {
                    if (!r.ok) return r.json().catch(() => ({ success: false, message: 'Server error (' + r.status + ')' }));
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update local question data cache
                        const qData = TUTOR_CONFIG.questionData[questionId];
                        if (qData) {
                            qData.question_text = payload.question;
                            qData.option_a = payload.option_a;
                            qData.option_b = payload.option_b;
                            qData.option_c = payload.option_c;
                            qData.option_d = payload.option_d;
                            qData.correct_answer = payload.correct_answer;
                            qData.explanation = payload.explanation;
                        }

                        // Update the visible question card in-place
                        const optionsContainer = document.querySelector('.options-container[data-question-id="' + questionId + '"]');
                        if (optionsContainer) {
                            const questionCard = optionsContainer.closest('.question-card');

                            // Update question text
                            const qText = questionCard.querySelector('.question-text');
                            if (qText) qText.textContent = payload.question;

                            // Update option texts
                            const optionCards = optionsContainer.querySelectorAll('.option-card');
                            optionCards.forEach(card => {
                                const letter = card.getAttribute('data-option');
                                const key = 'option_' + letter.toLowerCase();
                                const contentEl = card.querySelector('.option-content');
                                if (contentEl && payload[key] !== undefined) {
                                    contentEl.textContent = payload[key];
                                }
                            });
                        }

                        // Snapshot saved values so the modal counts as "clean"
                        originalValues = {
                            'edit-question-text': payload.question,
                            'edit-option-a': payload.option_a,
                            'edit-option-b': payload.option_b,
                            'edit-option-c': payload.option_c,
                            'edit-option-d': payload.option_d,
                            'edit-explanation': payload.explanation,
                            'correct_answer': payload.correct_answer
                        };

                        status.textContent = '✓ Saved successfully!';
                        status.className = 'edit-status success';

                        setTimeout(() => forceCloseModal(), 900);
                    } else {
                        status.textContent = data.message || 'Save failed.';
                        status.className = 'edit-status error';
                    }
                })
                .catch(err => {
                    status.textContent = 'Network error: ' + err.message;
                    status.className = 'edit-status error';
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                });
        };

        // Close edit modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && editModalOpen) {
                closeEditModal();
            }
        });

        // Close edit modal by clicking overlay background
        const overlay = document.getElementById('edit-question-overlay');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeEditModal();
            });
        }
    })();
} // end if (TUTOR_CONFIG.isAdmin)
