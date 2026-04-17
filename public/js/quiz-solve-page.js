(function () {
    const quizPageConfig = window.QuizSolvePageConfig || {};

    document.addEventListener('DOMContentLoaded', function () {
        let progressBarContainer = document.getElementById('progress-bar-container') || document.querySelector('.progress-bar-container');
        let submitBtn = document.getElementById('submit-btn') || document.querySelector('.submit-btn') || document.querySelector('button[type="submit"]');
        const quizForm = document.getElementById('quiz-form');
        const timeTakenField = document.getElementById('time-taken');
        const timerDisplay = document.getElementById('timer');

        if (!progressBarContainer || !submitBtn) {
            setTimeout(() => {
                progressBarContainer = document.getElementById('progress-bar-container') || document.querySelector('.progress-bar-container');
                submitBtn = document.getElementById('submit-btn') || document.querySelector('.submit-btn') || document.querySelector('button[type="submit"]');
                initializeProgressBarSystem();
            }, 500);
        } else {
            initializeProgressBarSystem();
        }

        function initializeProgressBarSystem() {
            if (!progressBarContainer || !submitBtn) {
                return;
            }

            const observer = new IntersectionObserver(([entry]) => {
                progressBarContainer.style.display = entry.isIntersecting ? 'none' : '';
            }, { rootMargin: '100px' });

            observer.observe(submitBtn);
        }

        const selectedAnswers = {};
        const quizDraft = window.QuizSessionDraft?.create({
            key: quizPageConfig.draftKey,
            questionIds: quizPageConfig.questionIds || []
        }) || null;

        function applyDraftAnswers(answers) {
            if (!answers || typeof answers !== 'object') {
                return;
            }

            Object.entries(answers).forEach(([questionId, answer]) => {
                const normalizedAnswer = typeof answer === 'string' ? answer : '';
                const radio = document.querySelector(`input[name="answers[${questionId}]"][value="${normalizedAnswer}"]`);
                if (radio) {
                    radio.checked = true;
                    selectedAnswers[questionId] = normalizedAnswer;
                    return;
                }

                const textarea = document.querySelector(`.written-answer[data-question-id="${questionId}"]`);
                if (textarea) {
                    textarea.value = normalizedAnswer;
                    if (normalizedAnswer.trim() !== '') {
                        selectedAnswers[questionId] = normalizedAnswer;
                    }
                }
            });
        }

        function saveDraft() {
            if (!quizDraft) {
                return;
            }

            quizDraft.save({
                answers: selectedAnswers,
                startedAt: startTime,
                scrollY: window.scrollY || 0
            });
        }

        function updateProgress() {
            const totalQuestions = Number(quizPageConfig.totalQuestions || 0);
            const answeredQuestions = Object.keys(selectedAnswers).length;

            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const progressPercentage = totalQuestions > 0 ? (answeredQuestions / totalQuestions) * 100 : 0;

            if (progressFill) {
                progressFill.style.width = `${progressPercentage}%`;
            }
            if (progressText) {
                progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
            }

            if (submitBtn) {
                submitBtn.disabled = answeredQuestions !== totalQuestions;
            }
        }

        document.querySelectorAll('.form-check input').forEach(input => {
            input.addEventListener('change', function () {
                const questionId = this.name.match(/\[(\d+)\]/)[1];
                selectedAnswers[questionId] = this.value;
                updateProgress();
                saveDraft();
            });
        });

        document.querySelectorAll('.written-answer').forEach(textarea => {
            textarea.addEventListener('input', function () {
                const questionId = this.getAttribute('data-question-id');
                if (this.value.trim() !== '') {
                    selectedAnswers[questionId] = this.value;
                } else {
                    delete selectedAnswers[questionId];
                }
                updateProgress();
                saveDraft();
            });
        });

        const timeLimit = Number(quizPageConfig.timeLimitSeconds || 0);
        let startTime = Date.now();
        let timerInterval;
        const savedDraft = quizDraft?.load();
        if (savedDraft) {
            applyDraftAnswers(savedDraft.answers);
            if (Number.isFinite(Number(savedDraft.startedAt)) && Number(savedDraft.startedAt) > 0) {
                startTime = Number(savedDraft.startedAt);
            }
        }

        function updateTimer() {
            const now = Date.now();
            const timeElapsed = Math.floor((now - startTime) / 1000);
            const timeRemaining = Math.max(0, timeLimit - timeElapsed);
            const timeTaken = timeLimit - timeRemaining;

            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;

            if (timerDisplay) {
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                if (timeRemaining <= 30) {
                    timerDisplay.style.color = '#ff4d4f';
                    timerDisplay.style.fontWeight = '700';
                    if (timeRemaining <= 10) {
                        timerDisplay.classList.add('pulsing');
                    }
                }
            }

            if (timeTakenField) {
                timeTakenField.value = timeTaken;
            }

            if (timeRemaining === 0) {
                clearInterval(timerInterval);
                submitQuiz();
            }
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
        updateProgress();
        if (savedDraft && Number.isFinite(Number(savedDraft.scrollY))) {
            requestAnimationFrame(() => window.scrollTo({ top: Number(savedDraft.scrollY), behavior: 'auto' }));
        }

        const popupOverlay = document.getElementById('popup-overlay');
        const popup = document.getElementById('popup');
        const popupTitle = document.getElementById('popup-title');
        const popupScore = document.getElementById('popup-score');
        const popupMessage = document.getElementById('popup-message');
        const reviewButton = document.getElementById('review-btn');
        const viewProgressButton = document.getElementById('view-progress-btn');
        const gradeIcon = document.getElementById('grade-icon');
        const quizSounds = {
            perfect: new Audio('/sounds/quiz-perfect.mp3'),
            partial: new Audio('/sounds/quiz-partial.mp3'),
            wrong: new Audio('/sounds/quiz-wrong.mp3'),
        };
        let quizSoundsPrimed = false;

        function setPopupActionsDisabled(disabled) {
            [reviewButton, viewProgressButton].forEach(action => {
                if (!action) {
                    return;
                }

                if ('disabled' in action) {
                    action.disabled = disabled;
                }

                action.style.pointerEvents = disabled ? 'none' : '';
                action.style.opacity = disabled ? '0.55' : '';
            });
        }

        function primeQuizSounds() {
            if (quizSoundsPrimed) {
                return;
            }

            quizSoundsPrimed = true;

            Object.values(quizSounds).forEach(audio => {
                try {
                    audio.preload = 'auto';
                    audio.volume = 0.7;
                    audio.muted = true;

                    const playPromise = audio.play();
                    if (playPromise && typeof playPromise.then === 'function') {
                        playPromise.then(() => {
                            audio.pause();
                            audio.currentTime = 0;
                            audio.muted = false;
                        }).catch(() => {
                            audio.muted = false;
                            audio.load();
                        });
                    } else {
                        audio.muted = false;
                        audio.load();
                    }
                } catch (_error) {
                    audio.muted = false;
                    audio.load();
                }
            });
        }

        function playQuizCompletionSound(percentage) {
            const soundKey = percentage === 100 ? 'perfect' : (percentage >= 50 ? 'partial' : 'wrong');
            const audio = quizSounds[soundKey];

            if (!audio) {
                return;
            }

            try {
                audio.pause();
                audio.currentTime = 0;
                audio.muted = false;
                audio.volume = 0.7;
                audio.play().catch(error => {
                    console.warn(`Audio play failed for ${soundKey}:`, error);
                });
            } catch (error) {
                console.warn(`Audio playback error for ${soundKey}:`, error);
            }
        }

        function submitQuiz() {
            document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => {
                input.disabled = true;
            });
            primeQuizSounds();

            const formDataObj = {
                answers: {},
                time_taken: document.getElementById('time-taken').value
            };

            document.querySelectorAll('.form-check-input').forEach(input => {
                const nameMatch = input.getAttribute('name').match(/answers\[(\d+)\]/);
                if (nameMatch) {
                    const questionId = nameMatch[1];
                    const selectedAnswer = document.querySelector(`input[name="answers[${questionId}]"]:checked`);
                    formDataObj.answers[questionId] = selectedAnswer ? selectedAnswer.value : '';
                }
            });

            document.querySelectorAll('.written-answer').forEach(textarea => {
                const questionId = textarea.getAttribute('data-question-id');
                formDataObj.answers[questionId] = textarea.value.trim() !== '' ? textarea.value : '';
            });

            document.querySelectorAll('[data-question-id]').forEach(questionBox => {
                const questionId = questionBox.getAttribute('data-question-id');
                if (!Object.prototype.hasOwnProperty.call(formDataObj.answers, questionId)) {
                    formDataObj.answers[questionId] = '';
                }
            });

            fetch(quizPageConfig.gradeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formDataObj)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'An error occurred while grading the quiz');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'An error occurred while grading the quiz');
                    }

                    quizDraft?.clear();
                    window.lastQuizResults = data;
                    setPopupActionsDisabled(false);

                    const percentage = (data.score / data.total) * 100;
                    playQuizCompletionSound(percentage);

                    popupScore.textContent = `${data.score}/${data.total}`;
                    popupMessage.textContent = data.message || 'Quiz completed!';

                    if (percentage === 100) {
                        gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                        gradeIcon.className = 'grade-icon perfect';
                        popupTitle.textContent = 'Perfect Score!';
                    } else if (percentage >= 70) {
                        gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                        gradeIcon.className = 'grade-icon good';
                        popupTitle.textContent = 'Well Done!';
                    } else {
                        gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                        gradeIcon.className = 'grade-icon needs-improvement';
                        popupTitle.textContent = 'Keep Learning!';
                    }

                    setTimeout(() => {
                        try {
                            let soundFile;
                            if (percentage === 100) {
                                soundFile = '/sounds/quiz-perfect.mp3';
                            } else if (percentage >= 50) {
                                soundFile = '/sounds/quiz-partial.mp3';
                            } else {
                                soundFile = '/sounds/quiz-wrong.mp3';
                            }

                            const audio = new Audio(soundFile);
                            audio.volume = 0.7;
                            audio.play().catch(() => {
                                const fallbackAudio = new Audio(soundFile);
                                fallbackAudio.volume = 0.5;
                                fallbackAudio.play().catch(() => {});
                            });
                        } catch (_error) {
                            // Ignore audio errors.
                        }
                    }, 300);

                    popupOverlay.classList.add('show');
                    popup.classList.add('show');

                    requestAnimationFrame(() => {
                        if (typeof confetti !== 'undefined' && percentage >= 70) {
                            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                            let originX = 0.62;
                            if (isSafari) {
                                originX = 0.47;
                            }

                            confetti({
                                particleCount: 100,
                                spread: 70,
                                origin: { x: originX, y: 0.45 },
                                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                                zIndex: 10000
                            });
                        }
                    });

                    if (data.review_url && reviewButton) {
                        reviewButton.href = data.review_url;
                    }

                    window.dispatchEvent(new CustomEvent('quizCompleted', {
                        detail: {
                            percentage_grade: percentage,
                            topic_name: 'Random Quiz',
                            correct_answers: data.score,
                            total_questions: data.total
                        }
                    }));
                })
                .catch(error => {
                    console.error('Error:', error);
                    setPopupActionsDisabled(false);
                    popupTitle.textContent = 'Error';
                    popupScore.textContent = '';
                    popupMessage.textContent = error.message || 'An error occurred while submitting the quiz. Please try again.';
                    if (gradeIcon) {
                        gradeIcon.innerHTML = '<i class="fas fa-circle-exclamation"></i>';
                        gradeIcon.className = 'grade-icon needs-improvement';
                    }
                    if (reviewButton) {
                        reviewButton.disabled = true;
                        reviewButton.style.pointerEvents = 'none';
                        reviewButton.style.opacity = '0.55';
                    }
                    popupOverlay.classList.add('show');
                    popup.classList.add('show');

                    document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => {
                        input.disabled = false;
                    });
                    saveDraft();
                });
        }

        if (quizForm) {
            quizForm.addEventListener('submit', function (event) {
                event.preventDefault();
                clearInterval(timerInterval);
                submitQuiz();
            });
        }

        if (reviewButton) {
            reviewButton.addEventListener('click', function () {
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                    showReviewMode();
                }, 300);
            });
        }

        window.addEventListener('beforeunload', saveDraft);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                saveDraft();
            }
        });
        window.addEventListener('scroll', saveDraft, { passive: true });
        saveDraft();

        function showReviewMode() {
            if (typeof lastQuizResults === 'undefined') {
                return;
            }

            const correctAnswers = lastQuizResults.correct_answers || {};
            const incorrectAnswers = lastQuizResults.incorrect_answers || {};
            const questionExplanations = lastQuizResults.question_explanations || {};

            function buildExplanationCard(explanation) {
                if (!explanation) {
                    return null;
                }

                const explanationCard = document.createElement('div');
                explanationCard.className = 'explanation-review-card';

                const title = document.createElement('strong');
                title.textContent = 'Explanation:';

                const body = document.createElement('div');
                body.className = 'explanation-review-text';
                body.textContent = explanation;

                explanationCard.appendChild(title);
                explanationCard.appendChild(body);

                return explanationCard;
            }

            document.querySelectorAll('[data-question-id]').forEach(questionBox => {
                const questionId = questionBox.getAttribute('data-question-id');
                const correctAnswer = correctAnswers[questionId];
                const incorrectData = incorrectAnswers[questionId];
                const submittedAnswer = Object.prototype.hasOwnProperty.call(selectedAnswers, questionId)
                    ? selectedAnswers[questionId]
                    : '';
                const explanation = incorrectData?.explanation || questionExplanations[questionId] || '';

                if (!correctAnswer) {
                    return;
                }

                const writtenAnswer = questionBox.querySelector('.written-answer');
                if (writtenAnswer) {
                    writtenAnswer.disabled = true;

                    const feedbackElement = questionBox.querySelector('.answer-feedback') || (() => {
                        const feedback = document.createElement('div');
                        feedback.className = 'answer-feedback mt-2';
                        writtenAnswer.parentNode.appendChild(feedback);
                        return feedback;
                    })();

                    feedbackElement.innerHTML = '';
                    feedbackElement.classList.remove('correct', 'incorrect');

                    if (incorrectData) {
                        writtenAnswer.classList.add('incorrect');
                        feedbackElement.classList.add('incorrect');

                        const correctAnswerCard = document.createElement('div');
                        correctAnswerCard.className = 'correct-answer-card';
                        correctAnswerCard.innerHTML = `<strong>Correct answer:</strong> ${correctAnswer}`;
                        feedbackElement.appendChild(correctAnswerCard);
                    } else {
                        writtenAnswer.classList.add('correct');
                        feedbackElement.classList.add('correct');
                        feedbackElement.innerHTML = '<i class="fas fa-check-circle text-success"></i> Correct!';
                    }

                    const explanationCard = buildExplanationCard(explanation);
                    if (explanationCard) {
                        feedbackElement.appendChild(explanationCard);
                    }
                } else {
                    const labels = questionBox.querySelectorAll('.form-check label');
                    const inputs = questionBox.querySelectorAll('.form-check input');

                    inputs.forEach(input => {
                        input.disabled = true;
                    });

                    labels.forEach(label => {
                        const input = label.previousElementSibling;
                        if (!input) {
                            return;
                        }

                        const isCorrectAnswer = input.value === correctAnswer;
                        const isSelectedAnswer = input.value === submittedAnswer;
                        const isIncorrectSelection = isSelectedAnswer && !isCorrectAnswer;

                        label.classList.remove('correct', 'incorrect');

                        if (isCorrectAnswer) {
                            label.classList.add('correct');
                        } else if (isIncorrectSelection) {
                            label.classList.add('incorrect');
                        }
                    });

                    const optionsContainer = questionBox.querySelector('.options');
                    const existingExplanationCard = optionsContainer?.querySelector('.explanation-review-card');
                    if (existingExplanationCard) {
                        existingExplanationCard.remove();
                    }
                    const explanationCard = buildExplanationCard(explanation);
                    if (optionsContainer && explanationCard) {
                        optionsContainer.appendChild(explanationCard);
                    }
                }
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Review Mode';
                submitBtn.style.pointerEvents = 'none';
                submitBtn.style.opacity = '0.7';
            }
        }
    });

    window.addEventListener('load', function () {
        setTimeout(function () {
            const progressBar = document.getElementById('progress-bar-container') ||
                document.querySelector('.progress-bar-container') ||
                document.querySelector('[class*="progress"]');

            const submitButton = document.getElementById('submit-btn') ||
                document.querySelector('.submit-btn') ||
                document.querySelector('button[type="submit"]') ||
                document.querySelector('[class*="submit"]');

            if (!progressBar || !submitButton) {
                return;
            }
        }, 1000);
    });
})();
