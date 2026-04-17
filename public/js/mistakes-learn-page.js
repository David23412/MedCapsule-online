const mistakesLearnPageConfig = window.MistakesLearnPageConfig || {};
let activeLearnFilterController = null;

function replaceLearnSection(selector, nextDocument) {
    const current = document.querySelector(selector);
    const next = nextDocument.querySelector(selector);

    if (!current || !next) return;
    current.replaceWith(next);
}

function hydrateLearnPageConfig(nextDocument) {
    const configScript = Array.from(nextDocument.querySelectorAll('script')).find((script) =>
        script.textContent.includes('window.MistakesLearnPageConfig')
    );

    if (!configScript) return;
    Function(configScript.textContent)();
}

async function loadLearnFilterInPlace(url, options = {}) {
    const { pushState = true } = options;

    if (!url) return;

    if (activeLearnFilterController) {
        activeLearnFilterController.abort();
    }

    activeLearnFilterController = new AbortController();

    const courseFilter = document.getElementById('course-filter');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (courseFilter) {
        courseFilter.disabled = true;
        courseFilter.style.opacity = '0.7';
        courseFilter.style.cursor = 'wait';
    }

    [sidebar, mainContent].forEach((element) => {
        if (!element) return;
        element.style.opacity = '0.65';
        element.style.pointerEvents = 'none';
    });

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: activeLearnFilterController.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');

        hydrateLearnPageConfig(nextDocument);
        replaceLearnSection('.sidebar', nextDocument);
        replaceLearnSection('.main-content', nextDocument);

        if (pushState) {
            window.history.pushState({}, '', url);
        }

        if (typeof window.initializeMistakesLearnPage === 'function') {
            window.initializeMistakesLearnPage();
        }
    } catch (error) {
        if (error.name === 'AbortError') return;
        window.location.href = url;
    } finally {
        activeLearnFilterController = null;
    }
}

function initializeMistakesLearnPage() {
            const difficultyOptions = document.querySelectorAll('.difficulty-option');
            const startButton = document.querySelector('.start-button');
            const difficultySelector = document.querySelector('.difficulty-selector');
            const difficultyOverlay = document.querySelector('.difficulty-overlay');
            const progressContainer = document.querySelector('.progress-container');
            const correctSound = document.getElementById('correctSound');
            const wrongSound = document.getElementById('wrongSound');
            const encouragementSound = document.getElementById('encouragementSound');
            const courseFilter = document.getElementById('course-filter');
            const topicFilter = document.getElementById('topic-filter');
            const questionTypeFilter = document.getElementById('question-type-filter');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarProgressSummary = document.getElementById('sidebarProgressSummary');
            const sidebarProgressPercent = document.getElementById('sidebarProgressPercent');
            const sidebarProgressFill = document.getElementById('sidebarProgressFill');
            const sidebarCompletedCount = document.getElementById('sidebarCompletedCount');
            const sidebarRemainingCount = document.getElementById('sidebarRemainingCount');
            const sidebarModeValue = document.getElementById('sidebarModeValue');
            const sidebarDueNowCount = document.getElementById('sidebarDueNowCount');
            const sidebarHighRiskCount = document.getElementById('sidebarHighRiskCount');
            const sidebarStabilizingCount = document.getElementById('sidebarStabilizingCount');
            const sidebarSkippedCount = document.getElementById('sidebarSkippedCount');
            const queueDueNowCount = document.getElementById('queueDueNowCount');
            const queueHighRiskCount = document.getElementById('queueHighRiskCount');
            const queueAverageInterval = document.getElementById('queueAverageInterval');
            const queueRecallHealth = document.getElementById('queueRecallHealth');
            const memoryQueueLead = document.getElementById('memoryQueueLead');
            const selectedTopicIds = Array.isArray(mistakesLearnPageConfig.selectedTopicIds)
                ? mistakesLearnPageConfig.selectedTopicIds
                : [];
            const currentMode = typeof mistakesLearnPageConfig.currentMode === 'string'
                ? mistakesLearnPageConfig.currentMode
                : 'quiz';
            const currentDeckMode = mistakesLearnPageConfig.currentDeckMode === 'spaced'
                ? 'spaced'
                : 'classic';
            const autoStartMode = typeof mistakesLearnPageConfig.autoStartMode === 'string'
                ? mistakesLearnPageConfig.autoStartMode
                : null;
            const selectedQuestionType = typeof mistakesLearnPageConfig.selectedQuestionType === 'string'
                ? mistakesLearnPageConfig.selectedQuestionType
                : 'all';
            const sidebarStorageKey = 'medcapsule-learn-sidebar-collapsed';

            const appendCurrentLearnFilters = (url) => {
                if (courseFilter && courseFilter.value) {
                    url.searchParams.set('course', courseFilter.value);
                }

                const currentTopicValue = topicFilter ? String(topicFilter.value || '') : '';
                if (currentTopicValue) {
                    url.searchParams.append('topic_ids[]', currentTopicValue);
                } else {
                    selectedTopicIds.forEach((topicId) => {
                        url.searchParams.append('topic_ids[]', topicId);
                    });
                }

                const currentQuestionTypeValue = questionTypeFilter
                    ? String(questionTypeFilter.value || 'all')
                    : selectedQuestionType;
                if (currentQuestionTypeValue && currentQuestionTypeValue !== 'all') {
                    url.searchParams.set('question_type', currentQuestionTypeValue);
                }

                return url;
            };

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

            // Mobile sidebar overlay behavior
            const isMobile = () => window.innerWidth <= 768;
            const sidebar = document.querySelector('.sidebar');
            let sidebarBackdrop = null;

            function createSidebarBackdrop() {
                if (sidebarBackdrop) return;
                sidebarBackdrop = document.createElement('div');
                sidebarBackdrop.className = 'sidebar-backdrop';
                document.body.appendChild(sidebarBackdrop);
                sidebarBackdrop.addEventListener('click', closeMobileSidebar);
            }

            function openMobileSidebar() {
                if (!sidebar) return;
                createSidebarBackdrop();
                sidebar.classList.add('mobile-open');
                if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileSidebar() {
                if (!sidebar) return;
                sidebar.classList.remove('mobile-open');
                if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // 💀 LETHAL AUDIO POOL SYSTEM - MULTIPLE READY AUDIO ELEMENTS 💀
            const audioPool = {
                correct: [],
                wrong: [],
                encouragement: []
            };
            
            // Create multiple audio elements for instant playback
            function createAudioPool() {
                // Create 10 ready-to-play audio elements for each sound (DOUBLED FOR LETHAL SPEED)
                for (let i = 0; i < 10; i++) {
                    // Correct sound pool
                    const correctAudio = new Audio('/sounds/Single-correct.mp3');
                    correctAudio.volume = 0.7;
                    correctAudio.preload = 'auto';
                    audioPool.correct.push(correctAudio);
                    
                    // Wrong sound pool
                    const wrongAudio = new Audio('/sounds/quiz-wrong.mp3');
                    wrongAudio.volume = 0.6;
                    wrongAudio.preload = 'auto';
                    audioPool.wrong.push(wrongAudio);
                    
                    // Encouragement sound pool
                    const encouragementAudio = new Audio('/sounds/encouragement.mp3');
                    encouragementAudio.volume = 0.7;
                    encouragementAudio.preload = 'auto';
                    audioPool.encouragement.push(encouragementAudio);
                }
            }
            
            // Initialize the audio pool
            createAudioPool();
            
            // Simple skip tracking
            let skippedQuestions = new Set();

            // 💀 LETHAL F16 RAPTOR SOUND SYSTEM - INSTANT FEEDBACK 💀
            const soundSystem = {
                playSound: function(audioElement) {
                    if (!audioElement) return;
                    
                    // Check if audio is ready or has errors
                    if (audioElement.readyState < 2 || audioElement.error) {
                        return;
                    }
                    
                    try {
                        // NUCLEAR SPEED: Clone and play for instant response
                        const clone = audioElement.cloneNode();
                        clone.volume = audioElement.volume;
                        
                        // Play the clone immediately
                        const playPromise = clone.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(() => {
                                // Fallback: try original element
                                audioElement.currentTime = 0;
                                audioElement.play().catch(() => {
                                    // Final fallback: try again after micro delay
                                    setTimeout(() => {
                                        audioElement.play().catch(() => {});
                                    }, 5);
                                });
                            });
                        }
                        
                        // Clean up clone after playback
                        clone.addEventListener('ended', () => {
                            clone.remove();
                        });
                    } catch (e) {
                        // Silent fail for maximum speed
                    }
                },
                
                playCorrect: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        return;
                    }
                    this.playSound(correctSound);
                },
                
                playWrong: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        return;
                    }
                    this.playSound(wrongSound);
                },
                
                playEncouragement: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        return;
                    }
                    this.playSound(encouragementSound);
                }
            };

            const playInstantOptionFeedback = (isCorrect) => {
                if (!quizStarted) {
                    return;
                }

                const soundPath = isCorrect ? '/sounds/Single-correct.mp3' : '/sounds/quiz-wrong.mp3';
                const fallbackPool = isCorrect ? audioPool.correct : audioPool.wrong;
                const fallbackElementId = isCorrect ? 'correctSound' : 'wrongSound';

                try {
                    const freshAudio = new Audio(soundPath);
                    freshAudio.volume = isCorrect ? 0.7 : 0.6;
                    freshAudio.preload = 'auto';

                    const playPromise = freshAudio.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(() => {
                            if (fallbackPool.length > 0) {
                                const readyAudio = fallbackPool.shift();
                                readyAudio.currentTime = 0;
                                readyAudio.play().catch(() => {
                                    const fallbackElement = document.getElementById(fallbackElementId);
                                    if (fallbackElement) {
                                        fallbackElement.currentTime = 0;
                                        fallbackElement.play().catch(() => {});
                                    }
                                });
                                readyAudio.addEventListener('ended', () => {
                                    fallbackPool.push(readyAudio);
                                }, { once: true });
                            }
                        });
                    }
                } catch (error) {
                    // Silent fail to preserve answer flow
                }
            };

            // 💀 NUCLEAR AUDIO PRELOADING SYSTEM - F16 RAPTOR SPEED 💀
            const audioElements = [correctSound, wrongSound, encouragementSound];
            
            // Force preload all audio files immediately
            audioElements.forEach(sound => {
                if (sound) {
                    // Set volume and preload
                    sound.volume = 0.7;
                    sound.preload = 'auto';
                    
                    // Add error handling for 404s
                    sound.addEventListener('error', function(e) {

                    });
                    
                    sound.load();
                }
            });
            
            // 💀 NO AUDIO PRIMING - COMPLETELY SILENT 💀
            // Audio will be primed only when first needed during actual gameplay
            
            // 💀 SILENT FALLBACK FOR MISSING AUDIO FILES 💀
            // Create silent audio elements if the original ones fail to load
            setTimeout(() => {
                audioElements.forEach((sound, index) => {
                    if (sound && sound.error) {

                        // Create a silent audio element as fallback
                        const silentAudio = new Audio();
                        silentAudio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT';
                        silentAudio.volume = 0;
                        silentAudio.id = sound.id;
                        sound.parentNode.replaceChild(silentAudio, sound);
                    }
                });
            }, 2000);

            // Initialize questions data and mode-specific settings
            const MODE_SETTINGS = {
                practice: {
                    requiredCorrect: 2,
                    totalStages: 2
                },
                quiz: {
                    requiredCorrect: 3,
                    totalStages: 3
                },
                spaced: {
                    requiredCorrect: 1,
                    totalStages: 0
                }
            };

            // PATCH CYCLING SYSTEM - Group questions into patches of 5
            const PATCH_SIZE = 5;
            let selectedMode = null;
            let questionsData = [];
            let currentQuestionIndex = 0;
            let activeQuestions = new Set();
            let initialSpacedDeckCount = 0;
            let totalAttempts = 0;
            let correctFirstAttempts = 0;
            let startTime = 0;
            let currentQuestionShownAt = 0;
            let quizStarted = false; // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
            let currentPatchIndex = 0; // Track current patch
            let currentPositionInPatch = -1; // Track position in current patch
            let patches = []; // Array of question patches
            let encouragementHideTimeout = null;

            const parseNumber = (value, fallback = 0) => {
                const parsed = Number(value);
                return Number.isFinite(parsed) ? parsed : fallback;
            };

            const parseDateValue = (value) => {
                if (!value) {
                    return null;
                }

                const parsed = new Date(value);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            };

            const isDueNow = (question) => !question.nextReviewAt || question.nextReviewAt.getTime() <= Date.now();

            const getStabilityLabel = (stability) => {
                if (stability < 0.5) return 'Fragile';
                if (stability < 2) return 'Building';
                if (stability < 7) return 'Settling';
                return 'Stable';
            };

            const getDifficultyLabel = (difficulty) => {
                if (difficulty >= 0.75) return 'Stubborn';
                if (difficulty >= 0.55) return 'Sticky';
                if (difficulty >= 0.35) return 'Improving';
                return 'Fluent';
            };

            const formatIntervalLabel = (intervalDays) => {
                return intervalDays > 0 ? `${Math.round(intervalDays)}d` : 'Same day';
            };

            const formatRelativeReviewTime = (date) => {
                if (!date) {
                    return 'Now';
                }

                const diffMs = date.getTime() - Date.now();
                const absMinutes = Math.round(Math.abs(diffMs) / (1000 * 60));

                if (diffMs <= 0) {
                    if (absMinutes < 60) {
                        return 'Due now';
                    }

                    const overdueHours = Math.round(absMinutes / 60);
                    return `${overdueHours}h overdue`;
                }

                if (absMinutes < 60) {
                    return `In ${absMinutes}m`;
                }

                const absHours = Math.round(absMinutes / 60);
                if (absHours < 24) {
                    return `In ${absHours}h`;
                }

                const absDays = Math.round(absHours / 24);
                return `In ${absDays}d`;
            };

            const getMemoryStatus = (question) => {
                if (question.skipped) {
                    return { label: 'Skipped', className: 'is-scheduled' };
                }

                if (question.learningState === 'relearn' && question.sameDayRelearnStep > 0) {
                    return {
                        label: `Relearn ${question.sameDayRelearnStep}/${question.maxSameDayRelearnStep}`,
                        className: 'is-risk'
                    };
                }

                if (isDueNow(question)) {
                    return { label: 'Due now', className: 'is-due' };
                }

                if (question.retrievability <= 0.55) {
                    return { label: 'High risk', className: 'is-risk' };
                }

                if (question.nextReviewAt && question.nextReviewAt.getTime() - Date.now() <= 1000 * 60 * 60 * 18) {
                    return { label: 'Soon', className: 'is-soon' };
                }

                return { label: 'Scheduled', className: 'is-scheduled' };
            };

            const renderMemoryState = (question) => {
                if (!question || !question.element) {
                    return;
                }

                const status = getMemoryStatus(question);
                const statusBadge = question.element.querySelector('[data-role="memory-status-badge"]');
                const nextReviewValue = question.element.querySelector('[data-role="memory-next-review"]');
                const intervalValue = question.element.querySelector('[data-role="memory-interval"]');
                const stabilityValue = question.element.querySelector('[data-role="memory-stability"]');
                const difficultyValue = question.element.querySelector('[data-role="memory-difficulty"]');
                const retrievabilityValue = question.element.querySelector('[data-role="memory-retrievability"]');
                const reviewStreakValue = question.element.querySelector('[data-role="memory-review-streak"]');

                if (statusBadge) {
                    statusBadge.textContent = status.label;
                    statusBadge.className = `memory-status-badge ${status.className}`;
                }

                if (nextReviewValue) {
                    nextReviewValue.textContent = formatRelativeReviewTime(question.nextReviewAt);
                }

                if (intervalValue) {
                    intervalValue.textContent = formatIntervalLabel(question.intervalDays);
                }

                if (stabilityValue) {
                    stabilityValue.textContent = getStabilityLabel(question.stability);
                }

                if (difficultyValue) {
                    difficultyValue.textContent = getDifficultyLabel(question.difficulty);
                }

                if (retrievabilityValue) {
                    retrievabilityValue.textContent = `${Math.round(question.retrievability * 100)}%`;
                }

                if (reviewStreakValue) {
                    reviewStreakValue.textContent = String(question.reviewStreak);
                }

            };

            const showReviewSyncError = (question, message) => {
                if (!question || !question.element) {
                    return;
                }

                const feedback = question.element.querySelector('[data-role="review-sync-feedback"]');
                if (!feedback) {
                    return;
                }

                feedback.textContent = message;
                feedback.className = 'review-sync-feedback is-visible is-error';
            };

            const clearReviewSyncError = (question) => {
                if (!question || !question.element) {
                    return;
                }

                const feedback = question.element.querySelector('[data-role="review-sync-feedback"]');
                if (!feedback) {
                    return;
                }

                feedback.textContent = '';
                feedback.className = 'review-sync-feedback';
            };

            const renderConfidenceState = (question) => {
                if (!question || !question.element) {
                    return;
                }

                const panel = question.element.querySelector('[data-role="spaced-confidence-panel"]');
                if (!panel) {
                    return;
                }

                const isVisible = selectedMode === 'spaced' && question.element.classList.contains('active');
                panel.classList.toggle('is-visible', isVisible);
                question.element.classList.toggle('spaced-mode-active', isVisible);
                question.element.classList.toggle('spaced-awaiting-confidence', isVisible && !question.confidenceLevel);
                const hasSelection = Boolean(question.confidenceLevel);
                const optionsRow = panel.querySelector('.spaced-confidence-options');

                if (optionsRow) {
                    optionsRow.classList.toggle('has-selection', hasSelection);
                }

                panel.querySelectorAll('.spaced-confidence-option').forEach((button) => {
                    button.classList.toggle('is-selected', question.confidenceLevel === button.dataset.confidence);
                });
            };

            const renderSpacedSelectionState = (question) => {
                if (!question || !question.element) {
                    return;
                }

                question.element.querySelectorAll('.option').forEach((option) => {
                    option.classList.remove('selected');
                });
            };

            const updateConfidencePanels = () => {
                questionsData.forEach((question) => {
                    renderConfidenceState(question);
                    renderSpacedSelectionState(question);
                });
            };

            const requireSpacedConfidence = (question) => {
                if (selectedMode !== 'spaced') {
                    return true;
                }

                if (question?.confidenceLevel) {
                    return true;
                }

                showReviewSyncError(question, 'Choose guessed, unsure, or knew it before answering in spaced mode.');
                return false;
            };

            const updateQueueSummary = () => {
                const liveQuestions = questionsData.filter((question) => activeQuestions.has(question.id) && !question.skipped);
                const dueCount = liveQuestions.filter((question) => isDueNow(question)).length;
                const highRiskCount = liveQuestions.filter((question) => question.retrievability <= 0.55).length;
                const stabilizingCount = liveQuestions.filter((question) => question.stability >= 1.5 && question.retrievability > 0.55).length;
                const skippedCount = questionsData.filter((question) => question.skipped).length;
                const averageInterval = liveQuestions.length
                    ? liveQuestions.reduce((sum, question) => sum + question.intervalDays, 0) / liveQuestions.length
                    : 0;
                const averageRecall = liveQuestions.length
                    ? liveQuestions.reduce((sum, question) => sum + question.retrievability, 0) / liveQuestions.length
                    : 0;

                const nextQuestion = liveQuestions
                    .filter((question) => question.nextReviewAt)
                    .sort((left, right) => left.nextReviewAt.getTime() - right.nextReviewAt.getTime())[0];

                if (sidebarDueNowCount) sidebarDueNowCount.textContent = String(dueCount);
                if (sidebarHighRiskCount) sidebarHighRiskCount.textContent = String(highRiskCount);
                if (sidebarStabilizingCount) sidebarStabilizingCount.textContent = String(stabilizingCount);
                if (sidebarSkippedCount) sidebarSkippedCount.textContent = String(skippedCount);
                if (queueDueNowCount) queueDueNowCount.textContent = String(dueCount);
                if (queueHighRiskCount) queueHighRiskCount.textContent = String(highRiskCount);
                if (queueAverageInterval) queueAverageInterval.textContent = averageInterval > 0 ? `${averageInterval.toFixed(1)}d` : 'Same day';
                if (queueRecallHealth) queueRecallHealth.textContent = `${Math.round(averageRecall * 100)}%`;

                if (memoryQueueLead) {
                    if (dueCount > 0) {
                        memoryQueueLead.textContent = `${dueCount} due now`;
                    } else if (nextQuestion) {
                        memoryQueueLead.textContent = `Next ${formatRelativeReviewTime(nextQuestion.nextReviewAt)}`;
                    } else {
                        memoryQueueLead.textContent = 'Queue is clear';
                    }
                }
            };

            const syncAdaptiveState = (question, payload) => {
                if (!question || !payload) {
                    return;
                }

                question.nextReviewAt = parseDateValue(payload.next_review_at) ?? question.nextReviewAt;
                question.intervalDays = parseNumber(payload.interval_days, question.intervalDays);
                question.stability = parseNumber(payload.stability, question.stability);
                question.difficulty = parseNumber(payload.difficulty, question.difficulty);
                question.retrievability = parseNumber(payload.retrievability, question.retrievability);
                question.reviewCount = parseNumber(payload.review_count, question.reviewCount);
                question.reviewStreak = parseNumber(payload.new_streak, question.reviewStreak);
                question.lastReviewRating = parseNumber(payload.rating, question.lastReviewRating);
                question.lastReviewConfidence = typeof payload.confidence === 'string' ? payload.confidence : question.lastReviewConfidence;
                question.learningState = typeof payload.learning_state === 'string' && payload.learning_state !== ''
                    ? payload.learning_state
                    : null;
                question.sameDayRelearnStep = parseNumber(payload.same_day_relearn_step, question.sameDayRelearnStep);
                question.needsSameDayRelearn = payload.needs_same_day_relearn === true;
                question.isMastered = payload.is_mastered === true;
                question.lastReviewedAt = Date.now();
                question.lastAttemptAt = new Date();

                renderMemoryState(question);
                updateQueueSummary();
            };

            const currentQuestionElapsedSeconds = () => {
                if (!currentQuestionShownAt) {
                    return 0;
                }

                return Math.max(1, Math.round((Date.now() - currentQuestionShownAt) / 1000));
            };

            const submitReviewAnswer = async (questionId, answer, timeSpentSeconds = 0, confidence = null) => {
                const currentQuestion = questionsData.find((question) => question.id === questionId);
                const interactionPayload = selectedMode === 'spaced' && currentQuestion
                    ? {
                        first_selected_answer: currentQuestion.firstSelectedAnswer,
                        answer_change_count: currentQuestion.answerChangeCount || 0,
                        time_to_first_selection: currentQuestion.timeToFirstSelection,
                        first_confidence: currentQuestion.firstConfidenceLevel,
                        confidence_change_count: currentQuestion.confidenceChangeCount || 0,
                    }
                    : {};

                const response = await fetch(`/learn/review/${questionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        selected_answer: answer,
                        mode: selectedMode,
                        time_spent: timeSpentSeconds,
                        confidence: selectedMode === 'spaced' ? confidence : null,
                        ...interactionPayload
                    })
                });

                const payload = await response.json();

                if (!response.ok || payload.success !== true) {
                    throw new Error(payload.message || 'Failed to record review');
                }

                return payload;
            };

            const modeNeedsReload = (targetMode) => {
                if (targetMode === 'spaced') {
                    return currentDeckMode !== 'spaced';
                }

                return currentDeckMode === 'spaced';
            };

            const buildLearnUrlForMode = (targetMode, autoStart = false) => {
                const baseUrl = String(mistakesLearnPageConfig.reviewLearnUrl || '');
                const url = new URL(baseUrl, window.location.origin);

                appendCurrentLearnFilters(url);

                url.searchParams.set('mode', targetMode);

                if (autoStart) {
                    url.searchParams.set('autostart', '1');
                }

                return url;
            };

            const clearAutoStartFlagFromUrl = () => {
                const url = new URL(window.location.href);

                if (!url.searchParams.has('autostart')) {
                    return;
                }

                url.searchParams.delete('autostart');
                window.history.replaceState({}, document.title, url.toString());
            };

            const applySessionOutcome = (question, isCorrect) => {
                if (!question) {
                    return;
                }

                if (selectedMode === 'spaced') {
                    applySpacedOutcome(question, isCorrect);
                    return;
                }

                totalAttempts++;

                if (isCorrect) {
                    if (question.correctStreak === 0) {
                        correctFirstAttempts++;
                    }

                    question.correctStreak++;

                    if (question.correctStreak >= MODE_SETTINGS[selectedMode].requiredCorrect) {
                        activeQuestions.delete(question.id);

                        const encouragementMessage = document.querySelector('.encouragement-message');
                        if (encouragementMessage) {
                            const title = encouragementMessage.querySelector('h2');
                            const message = encouragementMessage.querySelector('p');
                            if (title) title.textContent = 'Great job! Keep going! 🎉';
                            if (message) message.textContent = "You're making great progress!";

                            encouragementMessage.classList.add('show');
                            if (encouragementHideTimeout) clearTimeout(encouragementHideTimeout);
                            encouragementHideTimeout = setTimeout(() => {
                                encouragementMessage.classList.remove('show');
                            }, 1500);
                        }
                    }
                } else {
                    question.correctStreak = 0;
                }

                updateStageIndicator();
                updateProgress();
            };

            const applySpacedOutcome = (question, isCorrect, payload = null) => {
                totalAttempts++;

                if (isCorrect) {
                    question.correctStreak = 0;
                    question.sessionResolved = question.needsSameDayRelearn !== true && (question.isMastered || !isDueNow(question));
                } else {
                    question.correctStreak = 0;
                    question.sessionResolved = false;
                }

                question.confidenceLevel = null;
                question.firstConfidenceLevel = null;
                question.confidenceChangeCount = 0;
                question.selectedAnswer = null;
                question.firstSelectedAnswer = null;
                question.answerChangeCount = 0;
                question.timeToFirstSelection = null;
                question.frozenResponseSeconds = null;
                renderConfidenceState(question);
                renderSpacedSelectionState(question);

                updateStageIndicator();
                updateProgress();
            };

            const getSpacedEligibleQuestions = (includeScheduled = false) => {
                const now = Date.now();

                return questionsData
                    .map((question, index) => ({ question, index }))
                    .filter(({ question }) => {
                        if (question.skipped || question.sessionResolved) {
                            return false;
                        }

                        if (!question.nextReviewAt) {
                            return true;
                        }

                        return includeScheduled || question.nextReviewAt.getTime() <= now;
                    });
            };

            const getSpacedSessionDeck = () => questionsData.filter((question) => !question.skipped);

            const getSpacedReviewedCount = () => getSpacedSessionDeck().filter((question) => question.sessionResolved).length;

            const getNextSpacedQuestionIndex = (excludeQuestionId = null) => {
                const sortSpacedQuestions = (questions) => questions.sort((left, right) => {
                    const leftDue = left.question.nextReviewAt ? left.question.nextReviewAt.getTime() : 0;
                    const rightDue = right.question.nextReviewAt ? right.question.nextReviewAt.getTime() : 0;

                    if (leftDue !== rightDue) {
                        return leftDue - rightDue;
                    }

                    if (left.question.learningState !== right.question.learningState) {
                        return left.question.learningState === 'relearn' ? -1 : 1;
                    }

                    if (left.question.sameDayRelearnStep !== right.question.sameDayRelearnStep) {
                        return left.question.sameDayRelearnStep - right.question.sameDayRelearnStep;
                    }

                    if (left.question.conceptRiskScore !== right.question.conceptRiskScore) {
                        return right.question.conceptRiskScore - left.question.conceptRiskScore;
                    }

                    if (left.question.retrievability !== right.question.retrievability) {
                        return left.question.retrievability - right.question.retrievability;
                    }

                    if (left.question.difficulty !== right.question.difficulty) {
                        return right.question.difficulty - left.question.difficulty;
                    }

                    const leftAttempt = left.question.lastAttemptAt ? left.question.lastAttemptAt.getTime() : 0;
                    const rightAttempt = right.question.lastAttemptAt ? right.question.lastAttemptAt.getTime() : 0;

                    return leftAttempt - rightAttempt;
                });

                let eligibleQuestions = sortSpacedQuestions(getSpacedEligibleQuestions());

                if (eligibleQuestions.length === 0) {
                    eligibleQuestions = sortSpacedQuestions(getSpacedEligibleQuestions(true));
                }

                if (excludeQuestionId !== null) {
                    const alternativeQuestion = eligibleQuestions.find(({ question }) => question.id !== excludeQuestionId);
                    if (alternativeQuestion) {
                        return alternativeQuestion.index;
                    }
                }

                return eligibleQuestions[0]?.index ?? null;
            };

            const updateSidebarProgress = () => {
                if (selectedMode === 'spaced') {
                    const total = initialSpacedDeckCount;
                    const reviewed = getSpacedReviewedCount();
                    const remaining = Math.max(total - reviewed, 0);
                    const dueNow = getSpacedEligibleQuestions().length;
                    const percent = total > 0 ? Math.round((reviewed / total) * 100) : 0;

                    if (sidebarProgressSummary) {
                        sidebarProgressSummary.textContent = dueNow > 0
                            ? `${reviewed} reviewed, ${dueNow} due now`
                            : `${reviewed} reviewed this run`;
                    }

                    if (sidebarProgressPercent) {
                        sidebarProgressPercent.textContent = `${percent}%`;
                    }

                    if (sidebarProgressFill) {
                        sidebarProgressFill.style.width = `${percent}%`;
                    }

                    if (sidebarCompletedCount) {
                        sidebarCompletedCount.textContent = reviewed;
                    }

                    if (sidebarRemainingCount) {
                        sidebarRemainingCount.textContent = remaining;
                    }

                    return;
                }

                const total = questionsData.length;
                const completed = total - activeQuestions.size;
                const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

                if (sidebarProgressSummary) {
                    sidebarProgressSummary.textContent = `${completed} of ${total} cleared this run`;
                }

                if (sidebarProgressPercent) {
                    sidebarProgressPercent.textContent = `${percent}%`;
                }

                if (sidebarProgressFill) {
                    sidebarProgressFill.style.width = `${percent}%`;
                }

                if (sidebarCompletedCount) {
                    sidebarCompletedCount.textContent = completed;
                }

                if (sidebarRemainingCount) {
                    sidebarRemainingCount.textContent = Math.max(total - completed, 0);
                }
            };

            const updateSidebarMode = () => {
                if (!sidebarModeValue) return;

                if (!selectedMode) {
                    sidebarModeValue.textContent = 'Pick mode';
                    sidebarModeValue.dataset.mode = 'pending';
                    return;
                }

                if (selectedMode === 'practice') {
                    sidebarModeValue.textContent = 'Classic easy';
                    sidebarModeValue.dataset.mode = 'practice';
                } else if (selectedMode === 'quiz') {
                    sidebarModeValue.textContent = 'Classic hard';
                    sidebarModeValue.dataset.mode = 'quiz';
                } else {
                    sidebarModeValue.textContent = 'Spaced';
                    sidebarModeValue.dataset.mode = 'spaced';
                }
            };

            // Initialize questions data and create patches
            document.querySelectorAll('.card').forEach(card => {
                const questionData = {
                    element: card,
                    id: card.dataset.questionId,
                    correctStreak: 0,
                    reviewStreak: parseNumber(card.dataset.reviewStreak),
                    nextReviewAt: parseDateValue(card.dataset.nextReviewAt),
                    intervalDays: parseNumber(card.dataset.intervalDays),
                    stability: parseNumber(card.dataset.stability),
                    difficulty: parseNumber(card.dataset.difficulty),
                    retrievability: parseNumber(card.dataset.retrievability, 0.9),
                    reviewCount: parseNumber(card.dataset.reviewCount),
                    lastReviewRating: parseNumber(card.dataset.lastReviewRating),
                    lastReviewConfidence: null,
                    conceptRiskScore: parseNumber(card.dataset.conceptRiskScore),
                    focusConceptName: card.dataset.focusConceptName || null,
                    learningState: card.dataset.learningState || null,
                    sameDayRelearnStep: parseNumber(card.dataset.sameDayRelearnStep),
                    maxSameDayRelearnStep: 2,
                    needsSameDayRelearn: (card.dataset.learningState || '') === 'relearn' && parseNumber(card.dataset.sameDayRelearnStep) > 0,
                    lastAttemptAt: parseDateValue(card.dataset.lastAttemptDate),
                    confidenceLevel: null,
                    firstConfidenceLevel: null,
                    confidenceChangeCount: 0,
                    selectedAnswer: null,
                    firstSelectedAnswer: null,
                    answerChangeCount: 0,
                    timeToFirstSelection: null,
                    frozenResponseSeconds: null,
                    isMastered: false,
                    skipped: card.dataset.skipped === 'true',
                    sessionResolved: false,
                    lastReviewedAt: 0,
                    courseId: card.dataset.courseId || 'all',
                    isActive: true,
                    patchIndex: 0 // Will be set when creating patches
                };
                questionsData.push(questionData);
                activeQuestions.add(questionData.id);
                // Hide all cards initially
                card.classList.remove('active');
                renderMemoryState(questionData);
                renderConfidenceState(questionData);
                renderSpacedSelectionState(questionData);
            });

            const savedSidebarState = getStoredSidebarState();
            if (savedSidebarState !== null && !isMobile()) {
                applySidebarState(savedSidebarState === 'true');
            } else if (isMobile()) {
                // On mobile, always start with sidebar hidden (collapsed)
                applySidebarState(true);
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (isMobile()) {
                        // Mobile: toggle overlay sidebar
                        if (sidebar && sidebar.classList.contains('mobile-open')) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }
                    } else {
                        // Desktop: toggle collapse
                        const collapsed = !document.body.classList.contains('sidebar-collapsed');
                        applySidebarState(collapsed);
                        setStoredSidebarState(collapsed);
                    }
                });
            }

            // Close mobile sidebar on resize to desktop
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    closeMobileSidebar();
                }
            });

            updateSidebarProgress();
            updateSidebarMode();
            updateQueueSummary();

            // Create patches from questions
            function createPatches() {
                patches = [];
                for (let i = 0; i < questionsData.length; i += PATCH_SIZE) {
                    const patch = questionsData.slice(i, i + PATCH_SIZE);
                    // Assign patch index to each question
                    patch.forEach(q => q.patchIndex = patches.length);
                    patches.push(patch);
                }

            }

            // PATCH CYCLING: Check if current patch is completed
            function isCurrentPatchCompleted() {
                if (currentPatchIndex >= patches.length) return true;

                const currentPatch = patches[currentPatchIndex];
                return currentPatch.every(question =>
                    question.correctStreak >= MODE_SETTINGS[selectedMode].requiredCorrect
                );
            }

            // PATCH CYCLING: Move to next patch
            function moveToNextPatch() {
                if (currentPatchIndex < patches.length - 1) {
                    currentPatchIndex++;


                    // Reset question index to start of new patch
                    currentQuestionIndex = patches[currentPatchIndex][0] ?
                        questionsData.indexOf(patches[currentPatchIndex][0]) : 0;

                    // Reset position for new patch
                    currentPositionInPatch = -1;

                    // Show encouragement when moving to next patch
                    const encouragementMessage = document.querySelector('.encouragement-message');
                    const completedSetNumber = currentPatchIndex;
                    const nextSetNumber = currentPatchIndex + 1;

                    if (encouragementMessage) {
                        const title = encouragementMessage.querySelector('h2');
                        const message = encouragementMessage.querySelector('p');

                        if (title) {
                            title.textContent = 'Good job!';
                        }

                        if (message) {
                            message.textContent = `Set ${completedSetNumber} complete. Next up: Set ${nextSetNumber}.`;
                        }

                        encouragementMessage.classList.remove('show');
                        void encouragementMessage.offsetWidth;
                        encouragementMessage.classList.add('show');

                        if (encouragementHideTimeout) {
                            window.clearTimeout(encouragementHideTimeout);
                        }

                        encouragementHideTimeout = window.setTimeout(() => {
                            encouragementMessage.classList.remove('show');
                        }, 2200);
                    }

                    return true;
                }
                return false; // No more patches
            }

            // PATCH CYCLING: Get next question from current patch
            function getNextQuestionFromCurrentPatch() {
                if (currentPatchIndex >= patches.length) return null;

                const currentPatch = patches[currentPatchIndex];

                if (currentPatch.length === 0) return null;

                let start = (currentPositionInPatch + 1) % currentPatch.length;

                for (let i = 0; i < currentPatch.length; i++) {
                    const position = (start + i) % currentPatch.length;
                    const question = currentPatch[position];

                    if (activeQuestions.has(question.id)) {
                        currentPositionInPatch = position;
                        return questionsData.findIndex(q => q.id === question.id);
                    }
                }

                return null;
            }



            // Setup continue learning button
            document.getElementById('continue-learning').addEventListener('click', function() {
                // Hide popup
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);
                
                // Reset tracking variables
                totalAttempts = 0;
                correctFirstAttempts = 0;
                startTime = null;
                quizStarted = false;

                // Reset all questions data for a new session
                questionsData.forEach(q => {
                    q.correctStreak = 0;
                    q.sessionResolved = false;
                    q.isActive = true;
                    q.confidenceLevel = null;
                    q.firstConfidenceLevel = null;
                    q.confidenceChangeCount = 0;
                    q.selectedAnswer = null;
                    q.firstSelectedAnswer = null;
                    q.answerChangeCount = 0;
                    q.timeToFirstSelection = null;
                    q.frozenResponseSeconds = null;
                });

                currentQuestionIndex = -1;
                currentPatchIndex = 0; // Reset patch index
                currentPositionInPatch = -1; // Reset position
                initialSpacedDeckCount = 0;
                activeQuestions.clear();
                questionsData.forEach(q => activeQuestions.add(q.id));

                // Make sure all cards are hidden
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('active');
                });

                difficultyOptions.forEach(option => option.classList.remove('selected'));
                selectedMode = null;
                startButton.disabled = true;
                difficultySelector.style.display = 'block';
                difficultyOverlay.style.display = 'block';
                progressContainer.style.display = 'none';

                updateSidebarMode();
                updateConfidencePanels();
                updateStageIndicator();
                updateProgress();
                updateQueueSummary();
            });

            // Setup submit button for written answers
            document.querySelectorAll('.submit-written-answer').forEach(button => {
                button.addEventListener('click', function() {
                    const container = this.closest('.written-answer-container');
                    const textarea = container.querySelector('.written-answer');
                    const feedbackElement = container.querySelector('.written-answer-feedback');
                    const questionId = this.dataset.questionId;
                    const submittedAnswer = textarea.value.trim();
                    const currentQuestion = questionsData[currentQuestionIndex];
                    
                    if (submittedAnswer === '') {
                        feedbackElement.textContent = 'Please enter an answer.';
                        feedbackElement.className = 'written-answer-feedback incorrect';
                        return;
                    }

                    if (!requireSpacedConfidence(currentQuestion)) {
                        return;
                    }

                    clearReviewSyncError(currentQuestion);
                    
                    // Disable input while processing
                    textarea.disabled = true;
                    this.disabled = true;
                    
                    // Check the answer on the server
                    const timeSpentSeconds = currentQuestionElapsedSeconds();

                    submitReviewAnswer(questionId, submittedAnswer, timeSpentSeconds, currentQuestion.confidenceLevel)
                    .then(data => {
                        if (data.success) {
                            // Use the is_correct flag from the server response instead of parsing the message
                            const isCorrect = data.is_correct === true;
                            
                            // Debug logging to help track any future issues

                            
                            syncAdaptiveState(currentQuestion, data);
                            
                            if (isCorrect) {
                                // Play correct sound
                                soundSystem.playCorrect();
                                
                                // Show correct feedback
                                feedbackElement.textContent = 'Correct! Well done.';
                                feedbackElement.className = 'written-answer-feedback correct';
                            } else {
                                // Play wrong sound
                                soundSystem.playWrong();
                                
                                // Show incorrect feedback with the specific message from backend
                                feedbackElement.textContent = data.message || 'Incorrect. Try again!';
                                feedbackElement.className = 'written-answer-feedback incorrect';
                                
                                // Show the correct answer if provided in the response
                                if (data.correct_answer) {
                                    // Create an element to show the correct answer
                                    const correctAnswerDiv = document.createElement('div');
                                    correctAnswerDiv.className = 'written-answer-correct';
                                    correctAnswerDiv.innerHTML = `<strong>Correct Answer:</strong> ${data.correct_answer}`;
                                    correctAnswerDiv.style.marginTop = '10px';
                                    correctAnswerDiv.style.padding = '8px';
                                    correctAnswerDiv.style.backgroundColor = 'rgba(227, 252, 239, 0.3)';
                                    correctAnswerDiv.style.border = '1px solid rgba(0, 168, 84, 0.5)';
                                    correctAnswerDiv.style.borderRadius = '6px';
                                    correctAnswerDiv.style.color = '#28a745';
                                    
                                    // Add the correct answer below the feedback
                                    feedbackElement.appendChild(correctAnswerDiv);
                                }
                            }

                            applySessionOutcome(currentQuestion, isCorrect);
                            
                            // Correct: cycle at the same speed as normal MC questions (600ms)
                            // Incorrect: 2500ms so the user can read the correct answer
                            const cycleDelay = isCorrect ? 600 : 2500;
                            setTimeout(() => {
                                // Reset the form
                                textarea.value = '';
                                textarea.disabled = false;
                                this.disabled = false;
                                
                                // Clear all feedback elements
                                feedbackElement.textContent = '';
                                feedbackElement.className = 'written-answer-feedback';
                                
                                // Remove any correct answer divs
                                const correctAnswerDiv = feedbackElement.querySelector('.written-answer-correct');
                                if (correctAnswerDiv) {
                                    feedbackElement.removeChild(correctAnswerDiv);
                                }
                                
                                // Move to next question using patch cycling
                                showNextQuestion();
                                updateStageIndicator();
                            }, cycleDelay);
                        } else {
                            console.error('Error checking answer:', data.message);
                            feedbackElement.textContent = 'Error checking answer. Please try again.';
                            feedbackElement.className = 'written-answer-feedback incorrect';
                            
                            // Re-enable the form
                            textarea.disabled = false;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        feedbackElement.textContent = error.message || 'Error checking answer. Please try again.';
                        feedbackElement.className = 'written-answer-feedback incorrect';
                        showReviewSyncError(currentQuestion, 'Review was not saved. This mistake stayed in the same state.');
                        
                        // Re-enable the form
                        textarea.disabled = false;
                        this.disabled = false;
                    });
                });
            });

            function updateStageIndicator() {
                const stageIndicator = document.querySelector('.card.active .stage-indicator');
                if (!stageIndicator || !selectedMode) return;

                if (selectedMode === 'spaced') {
                    stageIndicator.innerHTML = '';
                    stageIndicator.style.display = 'none';
                    return;
                }

                stageIndicator.style.display = 'flex';

                const currentQuestion = questionsData[currentQuestionIndex];
                const modeConfig = MODE_SETTINGS[selectedMode];
                
                // Clear existing dots
                stageIndicator.innerHTML = '';
                
                // Add dots based on the mode's total stages
                for (let i = 0; i < modeConfig.totalStages; i++) {
                    const dot = document.createElement('div');
                    dot.className = 'stage-dot';
                    if (i < currentQuestion.correctStreak) {
                        dot.classList.add('completed');
                    }
                    stageIndicator.appendChild(dot);
                }
            }

            function selectDifficulty(mode) {
                const selectedOption = Array.from(difficultyOptions).find(option => option.dataset.mode === mode);

                if (!selectedOption || selectedOption.classList.contains('difficulty-option-spaced')) {
                    return;
                }

                difficultyOptions.forEach(option => option.classList.remove('selected'));
                selectedOption.classList.add('selected');
                selectedMode = selectedOption.dataset.mode;
                startButton.disabled = false;
                updateSidebarMode();
                updateConfidencePanels();
            }

            // Handle difficulty selection
            difficultyOptions.forEach(option => {
                option.addEventListener('click', function() {
                    if (this.classList.contains('difficulty-option-spaced')) return;
                    selectDifficulty(this.dataset.mode);
                });
            });

            if (autoStartMode) {
                selectDifficulty(autoStartMode);
            } else if (currentMode) {
                selectDifficulty(currentMode);
            } else if (mistakesLearnPageConfig.autoSelectEasy) {
                selectDifficulty('practice');
            }

            // Handle start button click
            startButton.addEventListener('click', function() {
                if (!selectedMode) return;

                if (modeNeedsReload(selectedMode)) {
                    window.location.href = buildLearnUrlForMode(selectedMode, true).toString();
                    return;
                }

                // 💀 BULLETPROOF AUDIO CONTROL - ENABLE SOUNDS ONLY AFTER QUIZ STARTS 💀
                quizStarted = true;

                // Initialize start time when learning begins
                startTime = Date.now();

                // Create patches for patch cycling system
                if (selectedMode !== 'spaced') {
                    createPatches();
                } else {
                    initialSpacedDeckCount = getSpacedSessionDeck().length;
                }

                // Hide difficulty selector and overlay
                difficultySelector.style.display = 'none';
                difficultyOverlay.style.display = 'none';

                // Show progress container
                progressContainer.style.display = 'block';

                // Initialize the first question
                currentQuestionIndex = -1; // Set to -1 so showNextQuestion will start at 0
                currentPatchIndex = 0; // Start with first patch
                currentPositionInPatch = -1; // Start before first question
                showNextQuestion();
            });

            if (autoStartMode && autoStartMode === selectedMode && !modeNeedsReload(autoStartMode)) {
                clearAutoStartFlagFromUrl();
                startButton.click();
            }

            // 💀 LETHAL INSTANT SOUND SYSTEM - F16 RAPTOR SPEED 💀
            document.querySelectorAll('.option').forEach(option => {
                option.addEventListener('click', function(e) {
                    if (!this.parentElement.classList.contains('answered')) {
                        const currentQuestion = questionsData[currentQuestionIndex];

                        if (!requireSpacedConfidence(currentQuestion)) {
                            return;
                        }

                        // 💀 LETHAL INSTANT SOUND PLAYBACK - NUCLEAR SPEED 💀
                        const isCorrect = this.dataset.isCorrect === "true";
                        const selectedOption = this.dataset.option;
                        
                        // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                        if (!quizStarted) {
                            return;
                        }
                        
                        playInstantOptionFeedback(isCorrect);
                        clearReviewSyncError(currentQuestion);

                        // Mark options container as answered
                        this.parentElement.classList.add('answered');
                        this.classList.add(isCorrect ? 'correct' : 'incorrect');

                        const timeSpentSeconds = currentQuestionElapsedSeconds();

                        if (selectedMode === 'spaced') {
                            if (!currentQuestion.firstSelectedAnswer) {
                                currentQuestion.firstSelectedAnswer = selectedOption;
                                currentQuestion.timeToFirstSelection = timeSpentSeconds;
                                currentQuestion.frozenResponseSeconds = timeSpentSeconds;
                            } else if (currentQuestion.selectedAnswer && currentQuestion.selectedAnswer !== selectedOption) {
                                currentQuestion.answerChangeCount = (currentQuestion.answerChangeCount || 0) + 1;
                            }
                            currentQuestion.selectedAnswer = selectedOption;
                        }

                        // ⚡️ OPTIMISTIC CYCLING - Fire and move on
                        const processOutcome = () => {
                            if (selectedMode === 'spaced') {
                                applySpacedOutcome(currentQuestion, isCorrect);
                            } else {
                                applySessionOutcome(currentQuestion, isCorrect);
                            }
                        };

                        processOutcome();

                        // 🔥 NUCLEAR SPEED: Reduced timeout to 600ms for faster cycling feel
                        setTimeout(() => {
                            this.parentElement.classList.remove('answered');
                            this.classList.remove('correct', 'incorrect');
                            showNextQuestion();
                        }, 600);

                        // BACKGROUND SYNC - Do not block the experience
                        submitReviewAnswer(currentQuestion.id, selectedOption, timeSpentSeconds, currentQuestion.confidenceLevel)
                            .then((data) => {
                                syncAdaptiveState(currentQuestion, data);
                            })
                            .catch((error) => {
                                console.error('Error background syncing review:', error);
                                // We don't show sync error here unless it's critical, 
                                // because the user has already moved on.
                            });
                        return;


                    }
                });
            });

            // Simple skip button handler
            document.querySelectorAll('.skip-question-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const questionId = this.dataset.questionId;
                    const currentQuestion = questionsData[currentQuestionIndex];
                    
                    // Simple API call to mark as skipped
                    fetch(`/review/skip/${questionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            mode: selectedMode === 'practice' ? 'quiz' : selectedMode
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {

                        } else {
                            console.warn('⚠️ Skip failed:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error skipping question:', error);
                    });
                    
                    // Always skip locally for this session
                    skippedQuestions.add(questionId);
                    currentQuestion.skipped = true;
                    currentQuestion.sessionResolved = true;
                    currentQuestion.confidenceLevel = null;
                    currentQuestion.firstConfidenceLevel = null;
                    currentQuestion.confidenceChangeCount = 0;
                    currentQuestion.selectedAnswer = null;
                    currentQuestion.firstSelectedAnswer = null;
                    currentQuestion.answerChangeCount = 0;
                    currentQuestion.timeToFirstSelection = null;
                    currentQuestion.frozenResponseSeconds = null;
                    activeQuestions.delete(currentQuestion.id);
                    renderMemoryState(currentQuestion);
                    renderConfidenceState(currentQuestion);
                    renderSpacedSelectionState(currentQuestion);
                    updateQueueSummary();
                    updateProgress();
                    showNextQuestion();
                });
            });

            document.querySelectorAll('.spaced-confidence-option').forEach(button => {
                button.addEventListener('click', function() {
                    const card = this.closest('.card');
                    const question = questionsData.find(item => item.id === card?.dataset.questionId);

                    if (!question) {
                        return;
                    }

                    if (!question.firstConfidenceLevel) {
                        question.firstConfidenceLevel = this.dataset.confidence;
                    } else if (question.confidenceLevel && question.confidenceLevel !== this.dataset.confidence) {
                        question.confidenceChangeCount = (question.confidenceChangeCount || 0) + 1;
                    }

                    question.confidenceLevel = this.dataset.confidence;
                    clearReviewSyncError(question);
                    renderConfidenceState(question);
                    renderSpacedSelectionState(question);
                });
            });

            function showNextQuestion() {
                if (questionsData.length === 0) return;

                if (selectedMode === 'spaced') {
                    const previousQuestionId = currentQuestionIndex >= 0
                        ? questionsData[currentQuestionIndex]?.id ?? null
                        : null;
                    const nextQuestionIndex = getNextSpacedQuestionIndex(previousQuestionId);

                    if (nextQuestionIndex === null) {
                        showCompletion();
                        return;
                    }

                    currentQuestionIndex = nextQuestionIndex;

                    document.querySelectorAll('.card').forEach(card => {
                        card.classList.remove('active');
                    });

                    questionsData[currentQuestionIndex].element.classList.add('active');
                    clearReviewSyncError(questionsData[currentQuestionIndex]);
                    currentQuestionShownAt = Date.now();
                    renderMemoryState(questionsData[currentQuestionIndex]);
                    renderSpacedSelectionState(questionsData[currentQuestionIndex]);
                    updateConfidencePanels();
                    updateProgress();
                    updateStageIndicator();
                    return;
                }

                // PATCH CYCLING: Check if all questions have been mastered
                if (activeQuestions.size === 0) {
                    showCompletion();
                    return;
                }

                // PATCH CYCLING: Check if current patch is completed
                if (isCurrentPatchCompleted()) {

                    if (!moveToNextPatch()) {
                        // No more patches available
                        showCompletion();
                        return;
                    }
                }

                // PATCH CYCLING: Get next question from current patch
                const nextQuestionIndex = getNextQuestionFromCurrentPatch();

                if (nextQuestionIndex === null) {
                    // All questions in current patch are mastered, try to move to next patch
                    if (!moveToNextPatch()) {
                        showCompletion();
                        return;
                    }
                    // Try again with new patch
                    return showNextQuestion();
                }

                // Update current question index
                currentQuestionIndex = nextQuestionIndex;

                // Hide ALL cards in the DOM
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('active');
                });

                // Show only the current question
                questionsData[currentQuestionIndex].element.classList.add('active');
                clearReviewSyncError(questionsData[currentQuestionIndex]);
                currentQuestionShownAt = Date.now();
                renderMemoryState(questionsData[currentQuestionIndex]);
                updateConfidencePanels();

                // Update progress
                updateProgress();
                updateStageIndicator();
            }

            function updateProgress() {
                const progressText = document.querySelector('.progress-text');
                const progressFill = document.querySelector('.progress-fill');

                if (selectedMode === 'spaced') {
                    const total = initialSpacedDeckCount;
                    const reviewed = getSpacedReviewedCount();
                    const dueNow = getSpacedEligibleQuestions().length;
                    const percent = total > 0 ? (reviewed / total) * 100 : 0;

                    progressText.textContent = dueNow > 0
                        ? `${reviewed} of ${total} reviewed this run, ${dueNow} due now`
                        : `${reviewed} of ${total} reviewed this run`;
                    progressFill.style.width = `${percent}%`;
                    updateSidebarProgress();
                    return;
                }

                const total = questionsData.length;
                const completed = questionsData.length - activeQuestions.size;

                progressText.textContent = `${completed} of ${total} completed this session`;
                progressFill.style.width = `${(completed / total) * 100}%`;
                updateSidebarProgress();
            }

            function showCompletion() {
                // 💀 NUCLEAR INSTANT GRADING SOUND - PLAYS IMMEDIATELY BEFORE ANYTHING ELSE 💀
                const soundStart = performance.now();
                
                // Calculate completion percentage for sound selection
                const totalQuestions = questionsData.length;
                const masteredQuestions = questionsData.length - activeQuestions.size;
                const completionPercentage = totalQuestions > 0 ? (masteredQuestions / totalQuestions) * 100 : 0;
                
                // 💀 LETHAL FINAL GRADING SOUND - ENCOURAGEMENT FOR LEARN MISTAKES 💀
                if (quizStarted) {

                    
                    // 💀 LEARN MISTAKES = ENCOURAGEMENT SOUND 💀
                    // This is specifically designed for learn mistakes system

                    
                    // 💀 LETHAL DIRECT ENCOURAGEMENT SOUND SYSTEM 💀
                    let soundPlayed = false;
                    
                    // Layer 1: Fresh encouragement audio (ALWAYS WORKS)
                    try {
                        const freshAudio = new Audio('/sounds/encouragement.mp3');
                        freshAudio.volume = 0.7;
                        freshAudio.preload = 'auto';
                        
                        // Force play with multiple attempts
                        const playPromise = freshAudio.play();
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                soundPlayed = true;

                            }).catch(() => {

                                // Layer 2: Preloaded encouragement element fallback
                                const encouragementAudio = document.getElementById('encouragementSound');
                                if (encouragementAudio) {
                                    encouragementAudio.currentTime = 0;
                                    encouragementAudio.volume = 0.7;
                                    encouragementAudio.play().catch(() => {

                                    });
                                }
                            });
                        }
                    } catch (error) {

                    }
                    

                }
                
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                const popupTitle = document.getElementById('popup-title');
                const popupScore = document.getElementById('popup-score');
                const popupMessage = document.getElementById('popup-message');
                const gradeIcon = document.getElementById('grade-icon');
                
                // Calculate stats
                const timeSpentValue = Math.round((Date.now() - startTime) / 1000);
                
                // Calculate and display study time
                const studyTimeSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(studyTimeSeconds / 60);
                const seconds = studyTimeSeconds % 60;
                const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                // Update popup content
                popupTitle.textContent = 'Session Complete!';
                document.getElementById('popup-time').textContent = timeString;
                
                // Always show celebration message
                gradeIcon.innerHTML = '🎉';
                popupMessage.textContent = 'Great work! You\'re making excellent progress!';
                
                // Save the study time to the database
                fetch(String(mistakesLearnPageConfig.saveTimeUrl || ''), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        study_time: timeSpentValue
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to save study time:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving study time:', error);
                });
                
                // Show popup with animation
                popupOverlay.classList.add('show');
                setTimeout(() => {
                    popup.classList.add('show');
                    
                    // PERFECT CENTER CONFETTI - SAFARI-CORRECTED POSITIONING
                    // Detect if the browser is Safari
                    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                    let originX = 0.62; // Default to center for all other browsers

                    // If it is Safari, apply a corrected origin value to counteract zoom effect
                    if (isSafari) {
                        // Fine-tuned for perfect centering - using 0.47 for optimal positioning
                        originX = 0.47;
                    }

                    confetti({
                        particleCount: 150,
                        spread: 60,
                        origin: { x: originX, y: 0.45 },
                        colors: ['#007bff', '#0056b3', '#3b82f6', '#60a5fa'],
                        ticks: 400,
                        disableForReducedMotion: true,
                        scalar: 1.0,
                        gravity: 0.6,
                        drift: 0,
                        zIndex: 10000
                    });
                }, 100);
            }

            // Update completion message styling
            const completionMessage = document.querySelector('.completion-message');
            if (completionMessage) {
                completionMessage.style.background = 'white';
                completionMessage.style.padding = '2rem';
                completionMessage.style.borderRadius = '12px';
                completionMessage.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                completionMessage.style.maxWidth = '500px';
                completionMessage.style.width = '90%';
                completionMessage.style.textAlign = 'center';
                completionMessage.style.position = 'fixed';
                completionMessage.style.top = '50%';
                completionMessage.style.left = '50%';
                completionMessage.style.transform = 'translate(-50%, -50%)';
                
                // Update the title styling
                const title = completionMessage.querySelector('h1');
                if (title) {
                    title.style.fontSize = '1.8rem';
                    title.style.fontWeight = '600';
                    title.style.color = '#1a1a1a';
                    title.style.marginBottom = '1.5rem';
                    title.innerHTML = 'Session Complete! 🎉';
                }
                
                // Update the stats styling
                const statsContainer = completionMessage.querySelector('.completion-stats');
                if (statsContainer) {
                    statsContainer.style.display = 'grid';
                    statsContainer.style.gridTemplateColumns = 'repeat(2, 1fr)'; // Changed to 2 columns
                    statsContainer.style.gap = '1rem';
                    statsContainer.style.margin = '1.5rem 0';
                    
                    // Update individual stat items
                    statsContainer.querySelectorAll('.stat-item').forEach(item => {
                        // Remove accuracy rate item
                        if (item.querySelector('p').textContent.trim() === 'Accuracy Rate') {
                            item.remove();
                            return;
                        }
                        
                        item.style.background = '#f8f9fa';
                        item.style.padding = '1rem';
                        item.style.borderRadius = '8px';
                        item.style.border = '1px solid #e9ecef';
                        
                        const statTitle = item.querySelector('h3');
                        if (statTitle) {
                            statTitle.style.fontSize = '1.5rem';
                            statTitle.style.fontWeight = '600';
                            statTitle.style.color = '#007bff';
                            statTitle.style.margin = '0 0 0.5rem 0';
                        }
                        
                        const statLabel = item.querySelector('p');
                        if (statLabel) {
                            statLabel.style.fontSize = '0.875rem';
                            statLabel.style.color = '#6c757d';
                            statLabel.style.margin = '0';
                        }
                    });
                }
                
                // Update buttons styling
                const buttons = completionMessage.querySelectorAll('.completion-button');
                buttons.forEach(button => {
                    button.style.background = '#007bff';
                    button.style.color = 'white';
                    button.style.border = 'none';
                    button.style.padding = '0.75rem 1.5rem';
                    button.style.borderRadius = '6px';
                    button.style.fontSize = '0.95rem';
                    button.style.fontWeight = '500';
                    button.style.textDecoration = 'none';
                    button.style.transition = 'all 0.2s ease';
                    button.style.display = 'inline-block';
                    button.style.margin = '0 0.5rem';
                    
                    button.addEventListener('mouseover', () => {
                        button.style.background = '#0056b3';
                        button.style.transform = 'translateY(-1px)';
                    });
                    
                    button.addEventListener('mouseout', () => {
                        button.style.background = '#007bff';
                        button.style.transform = 'translateY(0)';
                    });
                });
            }

            // BULLETPROOF SMOOTH FILTER - WORKS EVERY FUCKING TIME
            if (courseFilter || topicFilter || questionTypeFilter) {
                const handleFilterChange = function() {
                    const baseUrl = String(window.MistakesLearnPageConfig?.reviewLearnUrl || '');
                    const url = new URL(baseUrl, window.location.origin);

                    appendCurrentLearnFilters(url);

                    if (currentMode) {
                        url.searchParams.set('mode', currentMode);
                    }

                    loadLearnFilterInPlace(url.toString());
                };

                [courseFilter, topicFilter, questionTypeFilter].filter(Boolean).forEach((filterElement) => {
                    filterElement.addEventListener('change', handleFilterChange);
                    filterElement.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            handleFilterChange.call(this);
                        }
                    });
                });
            } else {
                console.error('❌ LEARN FILTERS NOT FOUND');
            }

            function removeMistake(questionId) {
                if (!confirm('Are you sure you want to remove this question from your mistakes list?')) {
                    return;
                }

                fetch(`/review/remove/${questionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the question card from the UI
                        const card = document.querySelector(`[data-question-id="${questionId}"]`);
                        if (card) {
                            card.remove();
                            // Update the total questions count
                            const totalQuestions = document.querySelectorAll('.question-card').length;
                            if (totalQuestions === 0) {
                                // If no questions left, show completion message
                                document.querySelector('.completion-message').style.display = 'block';
                                document.querySelector('.questions-container').style.display = 'none';
                            }
                        }
                    } else {
                        alert(data.message || 'Error removing question');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing question');
                });
            }
}

document.addEventListener('DOMContentLoaded', initializeMistakesLearnPage);
window.initializeMistakesLearnPage = initializeMistakesLearnPage;

if (!window.__mistakesLearnPopstateBound) {
    window.__mistakesLearnPopstateBound = true;
    window.addEventListener('popstate', () => {
        loadLearnFilterInPlace(window.location.href, { pushState: false });
    });
}
