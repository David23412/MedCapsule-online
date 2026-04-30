<div class="question-box written-question {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
    <div class="question-content-wrapper">
        <div class="question-main">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="question-text flex-grow-1">
                    {{ $question->question }}
                </div>
                <div class="ms-2 d-flex flex-column align-items-end gap-1">
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <button class="btn-admin-edit" onclick="openQuizEditModal({{ $question->id }})" title="Edit Question">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                    @endif
                </div>
            </div>

            <div class="written-answer-container">
                <div class="form-group">
                    <textarea 
                        name="answers[{{ $question->id }}]" 
                        id="written_answer_{{ $question->id }}" 
                        class="form-control written-answer" 
                        rows="4" 
                        placeholder="Type your answer here..."
                        data-question-id="{{ $question->id }}"
                        data-char-count-target="char_count_{{ $question->id }}"></textarea>
                    <div class="answer-feedback" id="feedback_{{ $question->id }}"></div>
                    <div class="answer-character-count">
                        <span id="char_count_{{ $question->id }}">0</span> characters
                    </div>
                </div>
            </div>
        </div>

        @if($question->image_url)
            <div class="question-image-container">
                <img src="{{ asset($question->image_url) }}" 
                     alt="Question image" 
                     class="question-image"
                     onerror="this.style.display='none'">
            </div>
        @endif
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
        background-color: #e3fcef !important;
        border: 1px solid #00a854 !important;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
        color: #00a854 !important;
        font-weight: 500;
    }

    .answer-character-count {
        text-align: right;
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 5px;
    }
</style>
