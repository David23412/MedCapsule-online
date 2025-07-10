<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $topic->name }} Quiz - MedCapsule</title>
    
    <!-- PERFORMANCE: Critical CSS inline -->
    <style>
        /* CRITICAL PATH CSS - Instant rendering */
        html { font-size: 12.8px; }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            margin: 0; padding: 0; min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeSpeed;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .question-box { will-change: transform; transform: translateZ(0); }
        .loading-skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%; animation: loading 1.5s infinite;
        }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .fade-in { opacity: 0; transform: translateY(20px) translateZ(0); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .fade-in.visible { opacity: 1; transform: translateY(0) translateZ(0); }
        .progress-fill { will-change: width; transform: translateZ(0); }
        .quiz-timer { font-weight: 600; color: #2563eb; }
    </style>

    <!-- PERFORMANCE: Async resource loading -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Preload critical scripts -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- PERFORMANCE: Defer non-critical CSS -->
    <link rel="stylesheet" href="{{ asset('css/optimized-quiz.css') }}" media="print" onload="this.media='all'">
</head>
<body>
    <!-- PERFORMANCE: Instant navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('courses') }}">MedCapsule</a>
            
            <!-- PERFORMANCE: Quiz timer with instant display -->
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <div class="quiz-timer me-3" id="quiz-timer">
                    <i class="fas fa-clock"></i>
                    <span id="timer">00:00</span>
                </div>
                
                <!-- Profile with skeleton -->
                <div class="profile-placeholder" id="profile-container">
                    <div class="loading-skeleton" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- PERFORMANCE: Header with instant appearance -->
        <div class="quiz-header fade-in">
            <div class="course-info">
                <h1 class="quiz-title">{{ $topic->name }} Quiz</h1>
                <p class="quiz-subtitle">{{ $course->name }}</p>
            </div>
            
            <!-- PERFORMANCE: Progress indicator -->
            <div class="progress-indicator" id="progress-indicator">
                <div class="progress-text">
                    <span id="current-question">1</span> of <span id="total-questions">{{ $questions->count() }}</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PERFORMANCE: Quiz form with instant loading -->
        <form id="quiz-form" data-submit-url="{{ route('quiz.grade', ['course' => $course->id, 'topic' => $topic->id]) }}">
            @csrf
            
            <!-- PERFORMANCE: Questions container -->
            <div class="questions-container" id="questions-container">
                <!-- Initial loading skeleton -->
                @for($i = 0; $i < 3; $i++)
                    <div class="question-skeleton">
                        <div class="loading-skeleton" style="height: 300px; border-radius: 16px; margin-bottom: 2rem;"></div>
                    </div>
                @endfor
            </div>

            <!-- PERFORMANCE: Submit button with instant feedback -->
            <button type="submit" id="submit-btn" class="submit-btn" disabled>
                <i class="fas fa-paper-plane"></i>
                Submit Quiz
            </button>
        </form>
    </div>

    <!-- PERFORMANCE: Results popup -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon"></div>
            <h2 class="popup-title" id="popup-title">Quiz Complete!</h2>
            <div class="score-container">
                <div class="popup-score" id="popup-score">0/0</div>
                <div class="score-details" id="score-details"></div>
            </div>
            <p class="popup-message" id="popup-message">Keep practicing!</p>
            <div class="popup-buttons">
                <button class="popup-btn review-btn" id="review-btn">
                    <i class="fas fa-search"></i>
                    Review Answers
                </button>
                <button class="popup-btn retry-btn" id="retry-btn">
                    <i class="fas fa-redo"></i>
                    Retry Quiz
                </button>
                <a href="{{ route('topics.forCourse', ['course' => $course->id]) }}" class="popup-btn view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    View Progress
                </a>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE: Load JavaScript after critical content -->
    <script>
        // PERFORMANCE: Global quiz data
        window.quizData = {
            questions: @json($questions),
            course: @json($course),
            topic: @json($topic),
            mode: '{{ $mode ?? 'quiz' }}',
            totalQuestions: {{ $questions->count() }},
            currentQuestion: 1,
            answers: {},
            startTime: Date.now(),
            timeElapsed: 0,
            isSubmitting: false
        };
        
        window.csrfToken = '{{ csrf_token() }}';

        // PERFORMANCE: Quiz manager with caching
        const QuizManager = {
            cache: new Map(),
            
            // PERFORMANCE: Instant question rendering
            renderQuestions() {
                const container = document.getElementById('questions-container');
                const fragment = document.createDocumentFragment();
                
                window.quizData.questions.forEach((question, index) => {
                    const questionElement = this.createQuestionElement(question, index);
                    questionElement.style.setProperty('--animation-delay', `${index * 100}ms`);
                    fragment.appendChild(questionElement);
                });
                
                // PERFORMANCE: Single reflow
                container.innerHTML = '';
                container.appendChild(fragment);
                
                // PERFORMANCE: Trigger animations with RAF
                requestAnimationFrame(() => {
                    container.querySelectorAll('.question-box').forEach(box => {
                        box.classList.add('visible');
                    });
                });
            },

            // PERFORMANCE: Optimized question element creation
            createQuestionElement(question, index) {
                const questionBox = document.createElement('div');
                questionBox.className = 'question-box fade-in';
                questionBox.setAttribute('data-question-id', question.id);
                questionBox.style.animationDelay = 'var(--animation-delay)';

                // PERFORMANCE: Handle different question types
                if (question.question_type === 'written') {
                    questionBox.innerHTML = this.createWrittenQuestionHTML(question, index);
                } else {
                    questionBox.innerHTML = this.createMultipleChoiceHTML(question, index);
                }

                return questionBox;
            },

            // PERFORMANCE: Written question HTML
            createWrittenQuestionHTML(question, index) {
                return `
                    <div class="question-header">
                        <span class="question-number">Question ${index + 1}</span>
                        <span class="question-type-badge">Written</span>
                    </div>
                    <div class="question-text">${question.question}</div>
                    ${question.image_url ? `
                        <img src="${question.image_url}" 
                             alt="Question image" 
                             class="question-image"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    ` : ''}
                    <div class="written-answer-container">
                        <textarea 
                            name="answers[${question.id}]" 
                            class="written-answer"
                            data-question-id="${question.id}"
                            placeholder="Type your answer here..."
                            rows="4"></textarea>
                        <div class="answer-feedback"></div>
                    </div>
                `;
            },

            // PERFORMANCE: Multiple choice HTML
            createMultipleChoiceHTML(question, index) {
                const options = ['A', 'B', 'C', 'D'].map(option => {
                    const optionField = `option_${option.toLowerCase()}`;
                    const optionText = question[optionField];
                    
                    return `
                        <div class="form-check">
                            <input type="radio" 
                                   name="answers[${question.id}]" 
                                   value="${option}" 
                                   id="q${question.id}_${option.toLowerCase()}" 
                                   class="form-check-input">
                            <label for="q${question.id}_${option.toLowerCase()}" class="form-check-label">
                                ${option}. ${optionText}
                            </label>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="question-header">
                        <span class="question-number">Question ${index + 1}</span>
                        <span class="question-type-badge">Multiple Choice</span>
                    </div>
                    <div class="question-text">${question.question}</div>
                    ${question.image_url ? `
                        <img src="${question.image_url}" 
                             alt="Question image" 
                             class="question-image"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    ` : ''}
                    <div class="options">
                        ${options}
                    </div>
                `;
            },

            // PERFORMANCE: Instant progress update
            updateProgress() {
                const answeredCount = Object.keys(window.quizData.answers).length;
                const totalCount = window.quizData.totalQuestions;
                const percentage = (answeredCount / totalCount) * 100;
                
                // Update progress bar
                const progressFill = document.getElementById('progress-fill');
                const currentQuestion = document.getElementById('current-question');
                
                progressFill.style.width = `${percentage}%`;
                currentQuestion.textContent = answeredCount;
                
                // Update submit button
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = answeredCount !== totalCount;
                
                if (answeredCount === totalCount && !submitBtn.classList.contains('ready')) {
                    submitBtn.classList.add('ready');
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Quiz';
                }
            },

            // PERFORMANCE: Timer with RAF optimization
            startTimer() {
                let lastUpdate = Date.now();
                
                const updateTimer = () => {
                    const now = Date.now();
                    if (now - lastUpdate >= 1000) { // Update every second
                        window.quizData.timeElapsed = Math.floor((now - window.quizData.startTime) / 1000);
                        
                        const minutes = Math.floor(window.quizData.timeElapsed / 60);
                        const seconds = window.quizData.timeElapsed % 60;
                        
                        document.getElementById('timer').textContent = 
                            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        
                        lastUpdate = now;
                    }
                    
                    if (!window.quizData.isSubmitting) {
                        requestAnimationFrame(updateTimer);
                    }
                };
                
                requestAnimationFrame(updateTimer);
            },

            // PERFORMANCE: Instant answer handling
            handleAnswer(input) {
                const questionId = input.name.match(/\[(\d+)\]/)[1];
                
                if (input.type === 'radio' && input.checked) {
                    window.quizData.answers[questionId] = input.value;
                } else if (input.type === 'textarea' || input.tagName === 'TEXTAREA') {
                    if (input.value.trim() !== '') {
                        window.quizData.answers[questionId] = input.value.trim();
                    } else {
                        delete window.quizData.answers[questionId];
                    }
                }
                
                this.updateProgress();
                this.saveToLocalStorage();
            },

            // PERFORMANCE: Local storage for recovery
            saveToLocalStorage() {
                const saveData = {
                    answers: window.quizData.answers,
                    timeElapsed: window.quizData.timeElapsed,
                    startTime: window.quizData.startTime
                };
                
                try {
                    localStorage.setItem(`quiz_${window.quizData.topic.id}`, JSON.stringify(saveData));
                } catch (e) {
                    console.warn('Failed to save quiz progress:', e);
                }
            },

            // PERFORMANCE: Restore from local storage
            restoreFromLocalStorage() {
                try {
                    const saved = localStorage.getItem(`quiz_${window.quizData.topic.id}`);
                    if (saved) {
                        const data = JSON.parse(saved);
                        window.quizData.answers = data.answers || {};
                        
                        // Restore answers to form
                        Object.entries(window.quizData.answers).forEach(([questionId, answer]) => {
                            const input = document.querySelector(`[name="answers[${questionId}]"][value="${answer}"]`) ||
                                         document.querySelector(`[name="answers[${questionId}]"]`);
                            
                            if (input) {
                                if (input.type === 'radio') {
                                    input.checked = true;
                                } else {
                                    input.value = answer;
                                }
                            }
                        });
                        
                        this.updateProgress();
                    }
                } catch (e) {
                    console.warn('Failed to restore quiz progress:', e);
                }
            },

            // PERFORMANCE: Async quiz submission
            async submitQuiz() {
                if (window.quizData.isSubmitting) return;
                
                window.quizData.isSubmitting = true;
                const submitBtn = document.getElementById('submit-btn');
                const originalContent = submitBtn.innerHTML;
                
                // PERFORMANCE: Instant feedback
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.disabled = true;
                
                try {
                    // Prepare form data
                    const formData = new FormData();
                    formData.append('_token', window.csrfToken);
                    formData.append('time_taken', window.quizData.timeElapsed);
                    formData.append('mode', window.quizData.mode);
                    
                    // Add answers
                    Object.entries(window.quizData.answers).forEach(([questionId, answer]) => {
                        formData.append(`answers[${questionId}]`, answer);
                    });
                    
                    const response = await fetch(document.getElementById('quiz-form').dataset.submitUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Show results
                    this.showResults(data);
                    this.markAnswers(data.correct_answers);
                    
                    // Clear saved progress
                    localStorage.removeItem(`quiz_${window.quizData.topic.id}`);
                    
                } catch (error) {
                    console.error('Submit error:', error);
                    alert(error.message || 'Failed to submit quiz. Please try again.');
                    
                    // Restore button
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                    window.quizData.isSubmitting = false;
                }
            },

            // PERFORMANCE: Instant results display
            showResults(data) {
                const popup = document.getElementById('popup');
                const overlay = document.getElementById('popup-overlay');
                const gradeIcon = document.getElementById('grade-icon');
                const popupTitle = document.getElementById('popup-title');
                const popupScore = document.getElementById('popup-score');
                const popupMessage = document.getElementById('popup-message');
                const scoreDetails = document.getElementById('score-details');
                
                // Update content
                popupScore.textContent = `${data.score}/${data.total}`;
                popupMessage.textContent = data.message;
                
                const percentage = (data.score / data.total) * 100;
                
                // Set grade icon and title
                if (percentage === 100) {
                    gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                    gradeIcon.className = 'grade-icon perfect';
                    popupTitle.textContent = 'Perfect Score!';
                    this.createConfetti();
                } else if (percentage >= 70) {
                    gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                    gradeIcon.className = 'grade-icon good';
                    popupTitle.textContent = 'Well Done!';
                } else {
                    gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                    gradeIcon.className = 'grade-icon needs-improvement';
                    popupTitle.textContent = 'Keep Learning!';
                }
                
                // Show XP if available
                if (data.xp_earned !== undefined) {
                    scoreDetails.innerHTML = `<div class="xp-earned">+${data.xp_earned} XP</div>`;
                }
                
                // Show popup with animation
                overlay.classList.add('show');
                requestAnimationFrame(() => {
                    popup.classList.add('show');
                });
            },

            // PERFORMANCE: Mark answers instantly
            markAnswers(correctAnswers) {
                Object.entries(correctAnswers).forEach(([questionId, correctAnswer]) => {
                    const questionBox = document.querySelector(`[data-question-id="${questionId}"]`);
                    if (!questionBox) return;
                    
                    const writtenAnswer = questionBox.querySelector('.written-answer');
                    if (writtenAnswer) {
                        // Handle written question
                        const feedback = questionBox.querySelector('.answer-feedback');
                        const userAnswer = window.quizData.answers[questionId];
                        
                        writtenAnswer.disabled = true;
                        
                        if (correctAnswer === userAnswer) {
                            writtenAnswer.classList.add('correct');
                            feedback.innerHTML = '<i class="fas fa-check"></i> Correct!';
                            feedback.classList.add('correct');
                        } else {
                            writtenAnswer.classList.add('incorrect');
                            feedback.innerHTML = `<i class="fas fa-times"></i> Correct answer: <strong>${correctAnswer}</strong>`;
                            feedback.classList.add('incorrect');
                        }
                    } else {
                        // Handle multiple choice
                        const userAnswer = window.quizData.answers[questionId];
                        const labels = questionBox.querySelectorAll('.form-check label');
                        const inputs = questionBox.querySelectorAll('.form-check input');
                        
                        // Disable all inputs
                        inputs.forEach(input => input.disabled = true);
                        
                        // Mark answers
                        labels.forEach(label => {
                            const input = label.previousElementSibling;
                            if (input) {
                                const isCorrect = input.value === correctAnswer;
                                const isSelected = input.value === userAnswer;
                                
                                if (isCorrect) {
                                    label.classList.add('correct');
                                } else if (isSelected) {
                                    label.classList.add('incorrect');
                                }
                            }
                        });
                    }
                });
            },

            // PERFORMANCE: Confetti animation
            createConfetti() {
                // Simple confetti implementation
                const colors = ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'];
                
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        left: ${Math.random() * 100}%;
                        top: -10px;
                        border-radius: 50%;
                        animation: confetti-fall 3s linear forwards;
                        z-index: 10000;
                    `;
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3000);
                }
            }
        };

        // PERFORMANCE: Initialize animations
        function initializeAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
        }

        // PERFORMANCE: DOM ready optimization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

        function init() {
            // PERFORMANCE: Initialize in priority order
            QuizManager.renderQuestions();
            initializeAnimations();
            QuizManager.startTimer();
            
            // Setup event listeners
            document.getElementById('quiz-form').addEventListener('submit', (e) => {
                e.preventDefault();
                QuizManager.submitQuiz();
            });
            
            // Handle answer changes
            document.addEventListener('change', (e) => {
                if (e.target.matches('[name^="answers"]')) {
                    QuizManager.handleAnswer(e.target);
                }
            });
            
            document.addEventListener('input', (e) => {
                if (e.target.matches('.written-answer')) {
                    QuizManager.handleAnswer(e.target);
                }
            });
            
            // Popup buttons
            document.getElementById('review-btn').addEventListener('click', () => {
                document.getElementById('popup-overlay').classList.remove('show');
            });
            
            document.getElementById('retry-btn').addEventListener('click', () => {
                window.location.reload();
            });
            
            // PERFORMANCE: Restore progress after setup
            requestIdleCallback(() => {
                QuizManager.restoreFromLocalStorage();
            });
        }

        // PERFORMANCE: Add confetti CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confetti-fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- PERFORMANCE: Load heavy scripts after content -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>