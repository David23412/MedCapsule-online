<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $topic->name ?? 'Tutor Mode' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        .navbar-brand {
            color: #007bff;
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            color: #0056b3;
            transform: translateY(-1px);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .profile-circle.no-image {
            background-color: #007bff;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }

        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 1000px;
            margin: 0 auto;
        }

        .question-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .question-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .question-text-container {
            flex: 1;
            min-width: 0; /* Prevents flex item from overflowing */
        }

        .question-text {
            font-size: 1.25rem;
            line-height: 1.6;
            color: #333;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .question-image {
            width: 280px;
            flex-shrink: 0;
        }

        .question-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .options-container {
            width: 100%;
        }

        .option-card {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .option-card:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .option-card.selected {
            border-color: #007bff;
            background: #f0f7ff;
        }

        .option-card.correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .option-card.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .option-letter {
            background: #e9ecef;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
            flex-shrink: 0;
        }

        .progress-bar-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            width: 0;
            transition: width 0.5s ease;
            border-radius: 5px;
        }

        .progress-text {
            text-align: center;
            color: #6c757d;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .timer {
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .timer i {
            color: #007bff;
        }

        .answer-feedback .alert {
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .answer-feedback .alert.alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .answer-feedback .alert.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .question-content {
            overflow: hidden; /* Clear float */
        }

        .options-container {
            clear: both; /* Clear float */
        }

        .option-content {
            font-size: 1rem;
            line-height: 1.5;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #007bff;
            border-color: #007bff;
        }

        .btn-success {
            background: #28a745;
            border-color: #28a745;
        }

        @media (max-width: 1100px) {
            .container, .progress-bar-container {
                max-width: 90%;
            }
        }

        @media (max-width: 850px) {
            .container {
                padding: 1.5rem;
                width: 100%;
                border-radius: 8px;
            }
            
            .progress-bar-container {
                transform: translate(-50%, 0);
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .question-content {
                flex-direction: column;
            }

            .question-image {
                width: 100%;
                max-width: 280px;
                margin: 0 auto 1.5rem;
            }

            .question-text {
                font-size: 1.1rem;
            }
        }

        .topic-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .popup {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            text-align: center;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .popup.show {
            transform: scale(1);
            opacity: 1;
        }

        .grade-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .grade-icon.perfect i {
            color: #ffd700;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .grade-icon.good i {
            color: #40a9ff;
            text-shadow: 0 0 10px rgba(64, 169, 255, 0.5);
        }

        .grade-icon.needs-improvement i {
            color: #ff4d4f;
            text-shadow: 0 0 10px rgba(255, 77, 79, 0.5);
        }

        .popup-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .popup-score {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .popup-message {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .popup-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .popup-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
        }

        .review-btn {
            background: #007bff;
            color: white;
        }

        .review-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .view-progress-btn {
            background: #6c757d;
            color: white;
        }

        .view-progress-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .popup-buttons {
                flex-direction: column;
        }

            .popup-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">← Back</a>
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="topic-header mb-4">
                <h1>{{ $topic->name }} - Tutor Mode</h1>
            </div>

            <!-- Source information display -->
            @if(isset($selected_sources) && count($selected_sources) > 0 && !in_array('all', $selected_sources))
                <div class="sources-info text-center mb-4">
                    <span class="text-muted">
                        Source: {{ implode(', ', $selected_sources) }}
                    </span>
                </div>
            @endif

            <!-- Progress bar -->
            <div class="progress-bar-container">
                <div class="timer">
                    <i class="fas fa-clock"></i>
                    <span id="timer">00:00</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions completed</div>
            </div>

            <!-- Questions Container -->
            @foreach($questions as $index => $question)
                <div class="question-card" id="question-{{ $index + 1 }}" style="{{ $index === 0 ? '' : 'display: none;' }}">
                    <div class="question-content">
                        <div class="question-text-container">
                            <div class="question-text">
                                {!! $question->question !!}
                            </div>
                        </div>

                        @if($question->image_url)
                            <div class="question-image">
                                <img src="{{ asset($question->image_url) }}" alt="Question Image" class="img-fluid">
                            </div>
                        @endif
                    </div>

                    <div class="options-container" data-question-id="{{ $question->id }}">
                        @if($question->question_type === 'multiple_choice')
                            @foreach(['A' => $question->option_a, 'B' => $question->option_b, 'C' => $question->option_c, 'D' => $question->option_d] as $letter => $option)
                                @if($option)
                                    <div class="option-card" data-option="{{ $letter }}" onclick="selectOption(this)">
                                        <span class="option-letter">{{ $letter }}</span>
                                        <div class="option-content">{!! $option !!}</div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="written-answer-container">
                                <textarea class="form-control written-answer" rows="4" placeholder="Type your answer here..."></textarea>
                            </div>
                        @endif
                    </div>

                    <!-- Answer Explanation (Hidden by default) -->
                    <div class="answer-feedback mt-4" style="display: none;">
                        <div class="alert" role="alert">
                            <h5 class="result-text mb-3"></h5>
                            <div class="explanation-content"></div>
                        </div>
                    </div>

                    <div class="actions mt-4">
                        <button class="btn btn-primary check-answer" onclick="checkAnswer({{ $index + 1 }}, {{ $question->id }})">
                            Check Answer
                        </button>
                        <button class="btn btn-success next-question" style="display: none;" onclick="showNextQuestion({{ $index + 1 }})">
                            Next Question
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Add audio elements -->
    <audio id="quizPerfectSound" preload="auto">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mpeg">
    </audio>

    <!-- Quiz Results Popup -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon"></div>
            <h2 class="popup-title" id="popup-title">Question Complete!</h2>
            <div class="score-container">
                <div class="popup-score" id="popup-score"></div>
            </div>
            <p class="popup-message" id="popup-message"></p>
            <div class="popup-buttons">
                <button class="popup-btn review-btn" id="review-btn">
                    <i class="fas fa-search"></i>
                    Continue Learning
                </button>
                <a href="{{ route('topics.forCourse', ['course' => $course->id]) }}" class="popup-btn view-progress-btn" id="view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    Back to Topics
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <script>
        let startTime = new Date();
        let currentQuestionIndex = 1;
        let totalQuestions = {{ $questions->count() }};
        let correctAnswers = 0;
        let totalAnswers = 0;
        let questionStartTime = new Date(); // Track time for each question
        let totalStudyTime = 0; // Track total study time
        
        // Timer functionality
        let timerInterval;
        
        function updateTimer() {
            const currentTime = new Date();
            const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
            const minutes = Math.floor(elapsedSeconds / 60);
            const seconds = elapsedSeconds % 60;
            
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timerInterval = setTimeout(updateTimer, 1000);
        }
        
        // Start the timer
        updateTimer();

        // Update progress bar
        function updateProgress(completed, total) {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const percentage = (completed / total) * 100;
            
            progressFill.style.width = `${percentage}%`;
            progressText.textContent = `${completed} of ${total} questions completed`;
        }

        // Function to select an option
        function selectOption(element) {
            // Remove selected class from all options in this container
            const container = element.closest('.options-container');
            container.querySelectorAll('.option-card').forEach(opt => {
                opt.classList.remove('selected');
            });
            // Add selected class to clicked option
            element.classList.add('selected');
        }

        // Function to check answer
        function checkAnswer(questionNumber, questionId) {
            const questionCard = document.getElementById(`question-${questionNumber}`);
            const optionsContainer = questionCard.querySelector('.options-container');
            const selectedOption = optionsContainer.querySelector('.option-card.selected');
            const writtenAnswer = optionsContainer.querySelector('.written-answer');
            const checkButton = questionCard.querySelector('.check-answer');
            
            let answer;
            if (selectedOption) {
                answer = selectedOption.dataset.option;
            } else if (writtenAnswer) {
                answer = writtenAnswer.value.trim();
            }

            if (!answer) {
                alert('Please select or write an answer before checking.');
                return;
            }

            // Calculate time taken for this question
            const timeTaken = Math.floor((new Date() - questionStartTime) / 1000);
            totalStudyTime += timeTaken; // Add to total study time

            // Disable the check button
            checkButton.disabled = true;

            fetch(`/quiz/tutor/check/${questionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    answer: answer,
                    time_taken: timeTaken
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'An error occurred while checking your answer.');
                }

                totalAnswers++;
                if (data.is_correct) {
                    correctAnswers++;
                }

                // Update the UI to show correct/incorrect answer
                updateQuestionUI(questionNumber, {
                    correct: data.is_correct,
                    explanation: data.explanation,
                    correctAnswer: data.correct_answer
                });

                // Play sound based on result
                playQuizCompletionSound(data.is_correct);

                // If we've answered all questions in the set, show the final popup
                if (totalAnswers === totalQuestions) {
                    const percentage = (correctAnswers / totalAnswers) * 100;
                    showQuizResults({
                        correct: correctAnswers,
                        total: totalAnswers,
                        percentage: percentage
                    });
                }
            })
            .catch(error => {
                console.error('Error checking answer:', error);
                alert(error.message || 'An error occurred while checking your answer. Please try again.');
                checkButton.disabled = false;
                
                // Re-enable options if there was an error
                if (selectedOption) {
                    optionsContainer.querySelectorAll('.option-card').forEach(opt => {
                        opt.style.pointerEvents = 'auto';
                        opt.style.cursor = 'pointer';
                    });
                } else if (writtenAnswer) {
                    writtenAnswer.disabled = false;
                }
            });
        }

        // Function to show next question
        function showNextQuestion(currentNumber) {
            const currentQuestion = document.getElementById(`question-${currentNumber}`);
            const nextQuestion = document.getElementById(`question-${currentNumber + 1}`);
            
            // Reset timer for new question
            questionStartTime = new Date();
            
            if (currentQuestion) {
                currentQuestion.style.display = 'none';
            }
            if (nextQuestion) {
                nextQuestion.style.display = 'block';
                // Update progress text
                document.getElementById('progress-text').textContent = `Question ${currentNumber + 1} of ${totalQuestions}`;
                // Update progress bar
                const progressPercentage = ((currentNumber + 1) / totalQuestions) * 100;
                document.getElementById('progress-fill').style.width = `${progressPercentage}%`;
            } else {
                // No more questions, save total study time
                fetch('/learn/save-time', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        duration_seconds: totalStudyTime,
                        total_questions: totalQuestions,
                        completed_questions: correctAnswers
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to save study time:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving study time:', error);
                });
            }
        }

        // Function to update question UI after checking answer
        function updateQuestionUI(questionNumber, data) {
            const questionCard = document.getElementById(`question-${questionNumber}`);
            const feedbackDiv = questionCard.querySelector('.answer-feedback');
            const optionsContainer = questionCard.querySelector('.options-container');
            
            // Show feedback
            feedbackDiv.style.display = 'block';
            const alertDiv = feedbackDiv.querySelector('.alert');
            alertDiv.className = `alert ${data.correct ? 'alert-success' : 'alert-danger'}`;
            
            // Update feedback text
            feedbackDiv.querySelector('.result-text').textContent = data.correct ? 'Correct! 🎉' : 'Incorrect';
            
            // Show explanation and correct answer if incorrect
            let explanationHtml = data.explanation || '';
            if (!data.correct && data.correctAnswer) {
                explanationHtml = `<strong>Correct Answer: ${data.correctAnswer}</strong><br><br>${explanationHtml}`;
            }
            feedbackDiv.querySelector('.explanation-content').innerHTML = explanationHtml;

            // Update option styling
            const selectedOption = questionCard.querySelector('.option-card.selected');
            const writtenAnswer = questionCard.querySelector('.written-answer');
            
            if (selectedOption) {
                selectedOption.classList.remove('selected');
                selectedOption.classList.add(data.correct ? 'correct' : 'incorrect');
                
                // If incorrect, highlight the correct answer
                if (!data.correct && data.correctAnswer) {
                    const correctOption = Array.from(optionsContainer.querySelectorAll('.option-card'))
                        .find(opt => opt.dataset.option === data.correctAnswer);
                    if (correctOption) {
                        correctOption.classList.add('correct');
                    }
                }
            } else if (writtenAnswer) {
                writtenAnswer.classList.add(data.correct ? 'correct' : 'incorrect');
                writtenAnswer.disabled = true;
            }

            // Show next button if not the last question
            const nextButton = questionCard.querySelector('.next-question');
            if (nextButton && questionNumber < totalQuestions) {
                nextButton.style.display = 'inline-block';
            }

            // Disable all options and the check button
            const checkButton = questionCard.querySelector('.check-answer');
            checkButton.disabled = true;

            if (selectedOption) {
                // Disable all option cards
                optionsContainer.querySelectorAll('.option-card').forEach(opt => {
                    opt.style.pointerEvents = 'none';
                    opt.style.cursor = 'default';
                });
            } else if (writtenAnswer) {
                writtenAnswer.disabled = true;
            }
        }

        // Function to show quiz results
        function showQuizResults(stats) {
            const popupOverlay = document.getElementById('popup-overlay');
            const popup = document.getElementById('popup');
            const gradeIcon = document.getElementById('grade-icon');
            const popupTitle = document.getElementById('popup-title');
            const popupScore = document.getElementById('popup-score');
            const popupMessage = document.getElementById('popup-message');

            popupScore.textContent = `${stats.correct}/${stats.total}`;

            if (stats.percentage === 100) {
                gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                gradeIcon.className = 'grade-icon perfect';
                popupTitle.textContent = 'Perfect Score!';
                popupMessage.textContent = 'Outstanding! You\'ve mastered this set!';
                createConfetti();
                playQuizCompletionSound(true);
            } else if (stats.percentage >= 70) {
                gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                gradeIcon.className = 'grade-icon good';
                popupTitle.textContent = 'Great Job!';
                popupMessage.textContent = 'Good progress! Keep up the great work!';
                playQuizCompletionSound(true);
            } else {
                gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                gradeIcon.className = 'grade-icon needs-improvement';
                popupTitle.textContent = 'Keep Learning!';
                popupMessage.textContent = 'Don\'t give up! Practice makes perfect!';
                playQuizCompletionSound(false);
            }

            // Show popup with animation
            popupOverlay.classList.add('show');
            setTimeout(() => popup.classList.add('show'), 100);
        }

        // Function to create confetti
        function createConfetti() {
            confetti({
                particleCount: 200,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                zIndex: 9999
            });
        }

        // Function to play quiz completion sound
        function playQuizCompletionSound(isCorrect) {
            const perfectSound = document.getElementById('quizPerfectSound');
            const partialSound = document.getElementById('quizPartialSound');
            const wrongSound = document.getElementById('quizWrongSound');
            
            // Reset all sounds to start
            [perfectSound, partialSound, wrongSound].forEach(sound => {
                if (sound) {
                    sound.pause();
                    sound.currentTime = 0;
                }
            });

            try {
                // For individual answer checks, just use perfect for correct and wrong for incorrect
                if (typeof isCorrect === 'boolean') {
                    if (isCorrect) {
                        perfectSound.play().catch(e => console.error('Perfect sound play failed:', e));
                    } else {
                        wrongSound.play().catch(e => console.error('Wrong sound play failed:', e));
                    }
                } else {
                    // For quiz completion, use the percentage logic
                    const percentage = (isCorrect.correct / isCorrect.total) * 100;
                    if (percentage === 100) {
                        perfectSound.play().catch(e => console.error('Perfect sound play failed:', e));
                    } else if (percentage >= 70) {
                        partialSound.play().catch(e => console.error('Partial sound play failed:', e));
                    } else {
                        wrongSound.play().catch(e => console.error('Wrong sound play failed:', e));
                    }
                }
            } catch (error) {
                console.error('Error playing sound:', error);
            }
        }

        // Add event listeners to popup buttons
        document.addEventListener('DOMContentLoaded', function() {
            const reviewBtn = document.getElementById('review-btn');
            if (reviewBtn) {
                reviewBtn.addEventListener('click', function() {
                    const popupOverlay = document.getElementById('popup-overlay');
                    const popup = document.getElementById('popup');
                    
            popup.classList.remove('show');
            setTimeout(() => {
                popupOverlay.classList.remove('show');
            }, 300);
        });
            }

            const viewProgressBtn = document.getElementById('view-progress-btn');
            if (viewProgressBtn) {
                viewProgressBtn.addEventListener('click', function() {
                    const popupOverlay = document.getElementById('popup-overlay');
                    const popup = document.getElementById('popup');
                    
                    popup.classList.remove('show');
                    setTimeout(() => {
                        popupOverlay.classList.remove('show');
                    }, 300);
                });
            }
        });
    </script>
</body>
</html> 