<div class="tab-pane fade {{ $activeTab === 'question-quality' ? 'show active' : '' }}" id="question-quality" role="tabpanel" aria-labelledby="question-quality-tab">
    <div class="card p-3 p-md-4">
        <h2 class="h4">Question Quality</h2>
        <p class="text-muted">Review invalid correct answers and explanations that only repeat the answer. Nothing changes until you edit and save.</p>
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label for="quality-course" class="form-label">Course</label>
                <select id="quality-course" class="form-select"><option value="">All courses</option>
                    @foreach(($courses ?? []) as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="quality-issue" class="form-label">Issue</label>
                <select id="quality-issue" class="form-select">
                    <option value="all">All issues</option><option value="broken_answer">Broken answer</option><option value="repeated_explanation">Answer-only explanation</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end"><button type="button" id="quality-refresh" class="btn btn-outline-primary w-100">Refresh</button></div>
        </div>
        <p id="quality-summary" class="text-muted" role="status" aria-live="polite"></p>
        <div id="quality-results" aria-busy="false"></div>
        <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
            <button type="button" id="quality-prev" class="btn btn-outline-secondary" disabled>Previous</button>
            <span id="quality-page" class="text-muted"></span>
            <button type="button" id="quality-next" class="btn btn-outline-secondary" disabled>Next</button>
        </div>
    </div>
</div>
<div class="modal fade" id="quality-editor" tabindex="-1" aria-labelledby="quality-editor-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="quality-editor-title">Edit question</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form id="quality-edit-form" class="d-flex flex-column overflow-hidden">
            <div class="modal-body">
                <div id="quality-edit-error" class="alert alert-danger d-none" role="alert"></div>
                <label for="quality-question" class="form-label">Question</label>
                <textarea id="quality-question" name="question" class="form-control mb-3" rows="4" maxlength="5000" required></textarea>
                <div id="quality-options">
                    @foreach(['a', 'b', 'c', 'd', 'e'] as $letter)
                        <label for="quality-option-{{ $letter }}" class="form-label">Option {{ strtoupper($letter) }}</label>
                        <textarea id="quality-option-{{ $letter }}" name="option_{{ $letter }}" class="form-control mb-2" rows="2" maxlength="1000"></textarea>
                    @endforeach
                    <label for="quality-answer" class="form-label">Correct option</label>
                    <select id="quality-answer" class="form-select mb-3" required><option value="">Choose a valid option</option>@foreach(['A','B','C','D','E'] as $letter)<option value="{{ $letter }}">{{ $letter }}</option>@endforeach</select>
                </div>
                <div id="quality-written-fields" hidden>
                    <label for="quality-written-answer" class="form-label">Correct answer</label>
                    <textarea id="quality-written-answer" class="form-control mb-3" rows="3" maxlength="5000"></textarea>
                </div>
                <label for="quality-explanation" class="form-label">Explanation</label>
                <textarea id="quality-explanation" name="explanation" class="form-control" rows="6" maxlength="10000"></textarea>
                <p class="form-text">Explain why the answer is correct. Repeating the option alone stays flagged.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" id="quality-save" class="btn btn-primary">Save changes</button></div>
        </form>
    </div></div>
</div>
