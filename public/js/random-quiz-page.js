        const randomQuizPageConfig = window.RandomQuizPageConfig || {};

document.addEventListener('DOMContentLoaded', function() {
            // NUCLEAR ELEMENT DETECTION - FIND THEM NO MATTER WHAT
            let progressBarContainer = document.getElementById('progress-bar-container') || document.querySelector('.progress-bar-container');
            let submitBtn = document.getElementById('submit-btn') || document.querySelector('.submit-btn') || document.querySelector('button[type="submit"]');
            const quizForm = document.getElementById('quiz-form');
            const timeTakenField = document.getElementById('time-taken');
            const timerDisplay = document.getElementById('timer');
            
            // NUCLEAR FALLBACK - WAIT AND TRY AGAIN
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
                if (!progressBarContainer || !submitBtn) return;
                
                // LEAN SOLUTION: Single Intersection Observer
                const observer = new IntersectionObserver(([entry]) => {
                    progressBarContainer.style.display = entry.isIntersecting ? 'none' : '';
                }, { rootMargin: '100px' });
                
                observer.observe(submitBtn);
            }
            
            let selectedAnswers = {};
            const quizDraft = window.QuizSessionDraft?.create({
                key: randomQuizPageConfig.draftKey,
                questionIds: randomQuizPageConfig.questionIds || [],
                onExpire: () => window.location.reload()
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
                        syncWrittenAnswerCharacterCount(textarea);
                        if (normalizedAnswer.trim() !== '') {
                            selectedAnswers[questionId] = normalizedAnswer;
                        }
                    }
                });
            }

            function syncWrittenAnswerCharacterCount(textarea) {
                if (!textarea) {
                    return;
                }

                const targetId = textarea.getAttribute('data-char-count-target');
                if (!targetId) {
                    return;
                }

                const counter = document.getElementById(targetId);
                if (counter) {
                    counter.textContent = String((textarea.value || '').length);
                }
            }

            function saveDraft() {
                if (!quizDraft || quizSubmitted) {
                    return;
                }

                quizDraft.save({
                    answers: selectedAnswers,
                    startedAt: startTime,
                    scrollY: window.scrollY || 0
                });
            }
            
            // Update progress function
            function updateProgress() {
                const totalQuestions = Number(randomQuizPageConfig.totalQuestions || 0);
                const answeredQuestions = Object.keys(selectedAnswers).length;
                
                // Update progress bar
                const progressFill = document.getElementById('progress-fill');
                const progressText = document.getElementById('progress-text');
                const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                
                progressFill.style.width = `${progressPercentage}%`;
                progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                
                // Enable submit button when all questions are answered
                submitBtn.disabled = answeredQuestions !== totalQuestions;
            }
            
            // Handle multiple choice question answers
            document.querySelectorAll('.form-check input').forEach(input => {
                input.addEventListener('change', function() {
                    const questionId = this.name.match(/\[(\d+)\]/)[1];
                    selectedAnswers[questionId] = this.value;
                    updateProgress();
                    saveDraft();
                });
            });
            
            // Handle written question answers
            document.querySelectorAll('.written-answer').forEach(textarea => {
                syncWrittenAnswerCharacterCount(textarea);
                textarea.addEventListener('input', function() {
                    const questionId = this.getAttribute('data-question-id');
                    syncWrittenAnswerCharacterCount(this);
                    if (this.value.trim() !== '') {
                        selectedAnswers[questionId] = this.value;
                } else {
                        delete selectedAnswers[questionId];
                    }
                    updateProgress();
                    saveDraft();
                });
            });

            // Timer functionality
            let timeLimit = Number(randomQuizPageConfig.timeLimitSeconds || 0);
            let timeRemaining = timeLimit;
            let timeTaken = 0;
            let timerInterval;
            let startTime = Date.now(); // Initialize start time for timer calculations
            let quizSubmitted = false;

            const savedDraft = quizDraft?.load();
            if (savedDraft) {
                applyDraftAnswers(savedDraft.answers);
                if (Number.isFinite(Number(savedDraft.startedAt)) && Number(savedDraft.startedAt) > 0) {
                    startTime = Number(savedDraft.startedAt);
                }
            }
            
            // NUCLEAR SPEED: Eliminate setTimeout delays in timer
            function updateTimer() {
                if (quizSubmitted) {
                    if (timerInterval) clearInterval(timerInterval);
                    return;
                }

                const now = Date.now();
                const timeElapsed = Math.floor((now - startTime) / 1000); // Standardize units
                const timeRemainingActual = Math.max(0, timeLimit - timeElapsed);
                const timeTakenActual = timeLimit - timeRemainingActual;
                
                // Update outer variables for shared state if needed
                timeRemaining = timeRemainingActual;
                timeTaken = timeTakenActual;

                const minutes = Math.floor(timeRemainingActual / 60);
                const seconds = timeRemainingActual % 60;
                
                if (timerDisplay) {
                    timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    // Visual indication when time is running low
                    if (timeRemainingActual <= 30) {
                        timerDisplay.style.color = '#ff4d4f';
                        timerDisplay.style.fontWeight = '700';
                        
                        if (timeRemainingActual <= 10) {
                            timerDisplay.classList.add('pulsing');
                        }
                    }
                } else {
                    console.warn('⚠️ Timer display element not found!');
                }
                
                // Update hidden field with time taken
                if (timeTakenField) {
                    timeTakenField.value = timeTakenActual;
                }
                
                // Timer expiration logic
                if (timeRemainingActual === 0) {
                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = null;
                    
                    if (!quizSubmitted) {
                        quizSubmitted = true;
                        submitQuiz();
                    }
                }
            }
            
            // Start the timer - BUT ONLY IF TIME NOT EXPIRED OR CONFIG OK
            if (timeLimit > 0) {
                updateTimer(); // Initial call
                
                // Only start interval if we haven't already submitted (e.g. if timeLimit was tiny)
                if (!quizSubmitted) {
                    timerInterval = setInterval(updateTimer, 1000);
                }
            } else {
                console.warn('⚠️ Timer not starting - timeLimit is 0 or negative');
            }

            updateProgress();
            if (savedDraft && Number.isFinite(Number(savedDraft.scrollY))) {
                requestAnimationFrame(() => window.scrollTo({ top: Number(savedDraft.scrollY), behavior: 'auto' }));
            }

            const popupOverlay = document.getElementById("popup-overlay");
            const popup = document.getElementById("popup");
            const popupTitle = document.getElementById("popup-title");
            const popupScore = document.getElementById("popup-score");
            const popupMessage = document.getElementById("popup-message");
            const reviewButton = document.getElementById("review-btn");
            const viewProgressButton = document.getElementById("view-progress-btn");
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
                // Disable all inputs and submit button
                document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => input.disabled = true);
                primeQuizSounds();
                
                // Submit quiz
                const gradeUrl = String(randomQuizPageConfig.gradeUrl || '');

                const formDataObj = {};
                formDataObj.answers = {};
                formDataObj.time_taken = document.getElementById('time-taken').value;
                if (randomQuizPageConfig.sessionKey) {
                    formDataObj.session_key = randomQuizPageConfig.sessionKey;
                }

                // Collect answers from multiple choice questions
                document.querySelectorAll('.form-check-input').forEach(input => {
                    // Extract question ID from name attribute like "answers[123]"
                    const nameMatch = input.getAttribute('name').match(/answers\[(\d+)\]/);
                    if (nameMatch) {
                        const questionId = nameMatch[1];
                        const selectedAnswer = document.querySelector(`input[name="answers[${questionId}]"]:checked`);
                        
                        // Initialize answer as empty string (unanswered)
                        formDataObj.answers[questionId] = '';
                        
                        // Update if an answer was selected
                        if (selectedAnswer) {
                            formDataObj.answers[questionId] = selectedAnswer.value;
                        }
                    }
                });
                
                // Collect answers from written questions
                document.querySelectorAll('.written-answer').forEach(textarea => {
                    const questionId = textarea.getAttribute('data-question-id');
                    // Initialize answer as empty string (unanswered)
                    formDataObj.answers[questionId] = '';
                    
                    // Update if an answer was provided
                    if (textarea.value.trim() !== '') {
                        formDataObj.answers[questionId] = textarea.value;
                    }
                });

                // Make sure all questions have an entry in answers (even if unanswered)
                document.querySelectorAll('[data-question-id]').forEach(questionBox => {
                    const questionId = questionBox.getAttribute('data-question-id');
                    if (!formDataObj.answers.hasOwnProperty(questionId)) {
                        formDataObj.answers[questionId] = '';
                    }
                });

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
                        if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'An error occurred while grading the quiz');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        quizDraft?.clear();
                        // Store quiz results globally for review functionality
                        window.lastQuizResults = data;
                        setPopupActionsDisabled(false);
                        
                        // Update score display and UI
                        const percentage = (data.score / data.total) * 100;

                        // Always show the score popup
                        popupScore.textContent = `${data.score}/${data.total}`;
                        popupMessage.textContent = data.message || 'Quiz completed!';

                        // Update grade icon and trigger confetti/sound
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

                        playQuizCompletionSound(percentage);

                        const progressSignal = {
                            mode: 'random',
                            timestamp: Math.floor(Date.now() / 1000)
                        };

                        localStorage.setItem('quizProgressUpdate', JSON.stringify(progressSignal));
                        document.dispatchEvent(new CustomEvent('sourceProgressUpdated', {
                            detail: progressSignal
                        }));

                        // Show the completion popup
                        popupOverlay.classList.add('show');
                        popup.classList.add('show');

                        // Trigger confetti AFTER popup is shown and positioned
                        requestAnimationFrame(() => {
                            if (percentage === 100) {
                                // Direct confetti call for perfect scores
                                if (typeof confetti !== 'undefined') {
                                    confetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { x: 0.5, y: 0.45 },
                                        colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                                        zIndex: 10000
                                    });
                                }
                            } else if (percentage >= 70) {
                                // Direct confetti call for good scores
                                if (typeof confetti !== 'undefined') {
                                    confetti({
                                        particleCount: 100,
                                        spread: 70,
                                        origin: { x: 0.5, y: 0.45 },
                                        colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                                        zIndex: 10000
                                    });
                                }
                            }
                        });

                        // Update review button link if provided
                        if (data.review_url) {
                            reviewButton.href = data.review_url;
                        }

                        // Emit completion event for real-time updates
                        window.dispatchEvent(new CustomEvent('quizCompleted', {
                            detail: {
                                percentage_grade: percentage,
                                topic_name: 'Random Quiz',
                                correct_answers: data.score,
                                total_questions: data.total
                            }
                        }));
                    } else {
                        throw new Error(data.error || 'An error occurred while grading the quiz');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error message in a popup
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
                    
                    // Re-enable form elements on error
                    document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => input.disabled = false);
                    saveDraft();
                });
            }

            // Handle form submission
            quizForm.addEventListener("submit", function (event) {
                event.preventDefault();
                clearInterval(timerInterval);
                submitQuiz();
            });

            // Add event listeners to popup buttons
            reviewButton.addEventListener("click", function () {
                // Hide popup first
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                    
                    // Show review mode - highlight correct/incorrect answers
                    showReviewMode();
                }, 300);
            });

            window.addEventListener('beforeunload', saveDraft);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') {
                    saveDraft();
                }
            });
            window.addEventListener('scroll', saveDraft, { passive: true });
            saveDraft();

            // Function to show review mode with correct/incorrect answer highlighting
            function showReviewMode() {
                // Get the quiz results data from the last grading response
                if (typeof lastQuizResults === 'undefined') {
                    console.warn('No quiz results available for review');
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

                // Process each question for review
                document.querySelectorAll('[data-question-id]').forEach(questionBox => {
                    const questionId = questionBox.getAttribute('data-question-id');
                    const correctAnswer = correctAnswers[questionId];
                    const incorrectData = incorrectAnswers[questionId];
                    const submittedAnswer = incorrectData ? incorrectData.submitted_answer : null;
                    const explanation = incorrectData?.explanation || questionExplanations[questionId] || '';

                    if (correctAnswer) {
                        // Check if this is a written question
                        const writtenAnswer = questionBox.querySelector('.written-answer');
                        
                        if (writtenAnswer) {
                            // Handle written question review
                            writtenAnswer.disabled = true;
                            
                            const feedbackElement = questionBox.querySelector('.answer-feedback') || 
                                                  (() => {
                                                      const feedback = document.createElement('div');
                                                      feedback.className = 'answer-feedback mt-2';
                                                      writtenAnswer.parentNode.appendChild(feedback);
                                                      return feedback;
                                                  })();

                            if (incorrectData) {
                                // Wrong answer - show in red with correct answer
                                writtenAnswer.classList.add('incorrect');
                                feedbackElement.classList.add('incorrect');
                                
                                // Create a card to show the correct answer
                                const correctAnswerCard = document.createElement('div');
                                correctAnswerCard.className = 'correct-answer-card';
                                correctAnswerCard.innerHTML = `<strong>Correct answer:</strong> ${correctAnswer}`;
                                feedbackElement.appendChild(correctAnswerCard);
                            } else {
                                // Correct answer - show in green
                                writtenAnswer.classList.add('correct');
                                feedbackElement.classList.add('correct');
                                feedbackElement.innerHTML = '<i class="fas fa-check-circle text-success"></i> Correct!';
                            }

                            const explanationCard = buildExplanationCard(explanation);
                            if (explanationCard) {
                                feedbackElement.appendChild(explanationCard);
                            }
                        } else {
                            // Handle multiple choice question review
                            const labels = questionBox.querySelectorAll('.form-check label');
                            const inputs = questionBox.querySelectorAll('.form-check input');
                            
                            // First disable all inputs
                            inputs.forEach(input => input.disabled = true);
                            
                            // Then mark answers - ROBUST REVIEW LOGIC
                            labels.forEach(label => {
                                const input = label.previousElementSibling;
                                if (input) {
                                    const isCorrectAnswer = input.value === correctAnswer;
                                    const isSelectedAnswer = input.value === submittedAnswer;
                                    const isIncorrectSelection = isSelectedAnswer && !isCorrectAnswer;

                                    // Remove any existing classes first
                                    label.classList.remove('correct', 'incorrect');

                                    if (isCorrectAnswer) {
                                        // Always mark correct answer in green
                                        label.classList.add('correct');
                                    } else if (isIncorrectSelection) {
                                        // Mark selected wrong answer in red
                                        label.classList.add('incorrect');
                                    }
                                }
                            });
                            const optionsContainer = questionBox.querySelector('.options');
                            const explanationCard = buildExplanationCard(explanation);
                            if (optionsContainer && explanationCard) {
                                optionsContainer.appendChild(explanationCard);
                            }
                        }
                    }
                });

                // Scroll to top to show the review
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Show a message that review mode is active
                const reviewMessage = document.createElement('div');
                reviewMessage.className = 'alert alert-info review-mode-alert';
                reviewMessage.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-search me-2"></i>
                            <strong>Review Mode:</strong> Correct answers are highlighted in green, incorrect answers in red.
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-redo me-1"></i>Try Again
                        </button>
                    </div>
                `;
                
                // Insert the message at the top of the quiz container
                const quizContainer = document.querySelector('.quiz-container');
                if (quizContainer) {
                    quizContainer.insertBefore(reviewMessage, quizContainer.firstChild);
                }
            }
        });

        // Function to create confetti
        function createConfetti() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { x: 0.5, y: 0.45 },
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                zIndex: 10000
            });
        }

        // EDGE/SAFARI CONFETTI TEST FUNCTION
        window.testConfetti = function() {
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { x: 0.5, y: 0.45 },
                    colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                    zIndex: 10000
                });
                alert('Testing direct confetti... check if confetti appears!');
            } else {
                alert('ERROR: No confetti library loaded! Check browser or network issues.');
            }
        };

        // Utility function to safely decode HTML entities
        function decodeHtmlEntities(text) {
            if (!text) return '';

            try {
                // Create a DOM element to leverage browser's built-in decoder
                const textArea = document.createElement('textarea');
                textArea.innerHTML = text;
                const decodedText = textArea.value;

                // Return safely decoded text
                return decodedText;
            } catch (_e) {
                return text; // Return the original text if decoding fails
            }
        }

        // Initialize progress bar visibility control on load
        window.addEventListener('load', function() {
            setTimeout(function() {
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
                
                let isHidden = false;
                
                // REMOVED: No complex positioning needed
                
            }, 1000);
        });
