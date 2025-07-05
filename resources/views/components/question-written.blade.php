<div class="question-box written-question {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
    <div class="question-text">
        {{ $question->question }}
    </div>

    @if($question->image_url)
        <img src="{{ asset($question->image_url) }}" 
             alt="Question image" 
             class="question-image"
             onerror="this.style.display='none'">
    @endif

    <div class="written-answer-container">
        <div class="form-group">
            <textarea 
                name="answers[{{ $question->id }}]" 
                id="written_answer_{{ $question->id }}" 
                class="form-control written-answer" 
                rows="4" 
                placeholder="Type your answer here..."
                data-question-id="{{ $question->id }}"></textarea>
            <div class="answer-feedback" id="feedback_{{ $question->id }}"></div>
            <div class="answer-character-count">
                <span id="char_count_{{ $question->id }}">0</span> characters
            </div>
        </div>
    </div>
</div>

<style>
    .written-answer {
        width: 100%;
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
        resize: vertical;
        min-height: 120px;
    }

    .written-answer:focus {
        border-color: #4dabf7;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        outline: 0;
    }

    .written-answer.correct {
        background-color: rgba(227, 252, 239, 0.3) !important;
        border-color: rgba(0, 168, 84, 0.5) !important;
    }

    .written-answer.incorrect {
        background-color: rgba(255, 241, 240, 0.3) !important;
        border-color: rgba(245, 34, 45, 0.5) !important;
    }

    .written-answer-container {
        margin-top: 1rem;
    }

    .answer-feedback {
        margin-top: 8px;
        font-size: 0.9rem;
        min-height: 20px;
    }

    .answer-feedback.correct {
        color: rgba(0, 168, 84, 0.7) !important;
        font-weight: bold;
    }

    .answer-feedback.incorrect {
        color: rgba(0, 168, 84, 0.7) !important;
        font-weight: bold;
    }

    /* Card style for correct answer */
    .correct-answer-card {
        background-color: rgba(227, 252, 239, 0.3) !important;
        border: 1px solid rgba(0, 168, 84, 0.5) !important;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
        color: rgba(0, 168, 84, 0.7) !important;
        font-weight: bold;
    }

    .answer-character-count {
        text-align: right;
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 5px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('written_answer_{{ $question->id }}');
        const charCount = document.getElementById('char_count_{{ $question->id }}');
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Add answer to the selectedAnswers object used by the main quiz script
            if (typeof selectedAnswers !== 'undefined') {
                selectedAnswers['{{ $question->id }}'] = this.value;
                
                // Trigger progress update if the progress update function exists
                if (typeof updateProgress === 'function') {
                    updateProgress();
                }
            }
        });
        
        // After quiz submission, handle review display
        if (typeof reviewButton !== 'undefined') {
            reviewButton.addEventListener('click', function() {
                const questionBox = document.querySelector('[data-question-id="{{ $question->id }}"]');
                if (questionBox) {
                    const feedbackElement = document.getElementById('feedback_{{ $question->id }}');
                    const writtenAnswer = document.getElementById('written_answer_{{ $question->id }}');
                    
                    if (feedbackElement && writtenAnswer) {
                        // Get stored correct answer from data attribute
                        const storedAnswer = questionBox.getAttribute('data-correct-answer');
                        const submittedAnswer = writtenAnswer.value;
                        
                        if (storedAnswer && typeof decodeHtmlEntities === 'function') {
                            const decodedCorrectAnswer = decodeHtmlEntities(storedAnswer);
                            
                            // Check if answer was correct
                            if (feedbackElement.textContent === 'Correct') {
                                // Style for correct answer
                                writtenAnswer.classList.add('correct');
                                feedbackElement.classList.add('correct');
                                
                                // Still show the correct answer in a card for reference
                                const correctAnswerCard = document.createElement('div');
                                correctAnswerCard.className = 'correct-answer-card';
                                correctAnswerCard.innerHTML = `Correct answer: <strong>${decodedCorrectAnswer}</strong>`;
                                feedbackElement.innerHTML = 'Correct';
                                feedbackElement.appendChild(correctAnswerCard);
                            } else {
                                // Style for incorrect answer
                                writtenAnswer.classList.add('incorrect');
                                feedbackElement.classList.add('incorrect');
                                
                                // Create a card for the correct answer
                                const correctAnswerCard = document.createElement('div');
                                correctAnswerCard.className = 'correct-answer-card';
                                correctAnswerCard.innerHTML = `Correct answer: <strong>${decodedCorrectAnswer}</strong>`;
                                feedbackElement.innerHTML = '';
                                feedbackElement.appendChild(correctAnswerCard);
                            }
                        }
                    }
                }
            });
        }
    });
</script> 