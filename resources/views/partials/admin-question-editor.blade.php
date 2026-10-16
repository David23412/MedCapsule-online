    @if(auth()->user()->isAdmin())
        <!-- ============================================================
             ADMIN INLINE EDIT MODAL — Full support for MCQ + Written
             ============================================================ -->
        <style>
            /* ── Modal Overlay ── */
            .edit-question-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(4px);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 10500;
                padding: 1rem;
            }

            .edit-question-overlay.show {
                display: flex;
            }

            /* ── Modal Box ── */
            .edit-question-modal {
                background: #fff;
                border-radius: 12px;
                border: 1px solid #dbe1e8;
                width: 100%;
                max-width: 660px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
                animation: editModalIn 0.25s cubic-bezier(0.22, 1, 0.36, 1);
            }

            @keyframes editModalIn {
                from {
                    opacity: 0;
                    transform: translateY(24px) scale(0.97);
                }

                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            /* ── Header ── */
            .edit-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.25rem 1.5rem;
                border-bottom: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 12px 12px 0 0;
            }

            .edit-modal-header h5 {
                color: #0f172a;
                margin: 0;
                font-size: 1.05rem;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .edit-modal-close {
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                color: #475569;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.2s, border-color 0.2s, color 0.2s;
            }

            .edit-modal-close:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            /* ── Body ── */
            .edit-modal-body {
                padding: 1.5rem;
            }

            /* Question type badge row */
            .edit-type-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
                padding: 0.75rem 1rem;
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }

            .edit-type-label {
                font-size: 0.82rem;
                font-weight: 600;
                color: #64748b;
            }

            .edit-type-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.1rem 0;
                font-size: 0.8rem;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: color 0.2s ease;
                position: relative;
            }

            .edit-type-badge::before {
                content: "";
                width: 10px;
                height: 10px;
                border-radius: 50%;
                border: 1.5px solid #94a3b8;
                background: #ffffff;
                flex-shrink: 0;
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }

            .edit-type-badge.mcq,
            .edit-type-badge.written {
                background: transparent;
                color: #475569;
            }

            .edit-type-badge.mcq.active,
            .edit-type-badge.written.active {
                background: transparent;
                color: #166534;
            }

            .edit-type-badge.active::before {
                background: #16a34a;
                border-color: #16a34a;
            }

            /* Fields */
            .edit-field {
                margin-bottom: 1.1rem;
            }

            .edit-field label {
                display: block;
                font-size: 0.82rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 0.4rem;
            }

            .edit-input {
                width: 100%;
                padding: 0.65rem 0.9rem;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                transition: border-color 0.2s, box-shadow 0.2s;
                box-sizing: border-box;
                resize: vertical;
            }

            .edit-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12);
            }

            /* Options grid */
            .edit-options-section {
                margin-bottom: 1.1rem;
            }

            .edit-options-section label {
                display: block;
                font-size: 0.82rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 0.6rem;
            }

            .edit-option-row {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                margin-bottom: 0.55rem;
            }

            .edit-option-radio {
                display: flex;
                align-items: center;
                gap: 0.4rem;
                flex-shrink: 0;
            }

            .edit-option-radio input[type="radio"] {
                width: 16px;
                height: 16px;
                accent-color: #16a34a;
                cursor: pointer;
                margin: 0;
            }

            .edit-radio-label {
                font-weight: 600;
                font-size: 0.85rem;
                color: #475569;
                background: transparent;
                border: none;
                width: auto;
                height: auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .edit-option-row .edit-input {
                margin-bottom: 0;
                flex: 1;
            }

            /* ── Footer ── */
            .edit-modal-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 0 0 12px 12px;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .edit-footer-left {
                display: flex;
                gap: 0.5rem;
            }

            .edit-footer-right {
                display: flex;
                gap: 0.5rem;
            }

            .edit-btn {
                padding: 0.6rem 1.25rem;
                border: 1px solid transparent;
                border-radius: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            }

            .edit-btn-cancel {
                background: #ffffff;
                border-color: #d1d5db;
                color: #475569;
            }

            .edit-btn-cancel:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
            }

            .edit-btn-save {
                background: #2563eb;
                border-color: #2563eb;
                color: #fff;
            }

            .edit-btn-save:hover {
                background: #1d4ed8;
                border-color: #1d4ed8;
            }

            .edit-btn-save:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .edit-btn-delete {
                background: #dc2626;
                border-color: #dc2626;
                color: #fff;
            }

            .edit-btn-delete:hover {
                background: #b91c1c;
                border-color: #b91c1c;
            }

            /* Status */
            .edit-status {
                padding: 0 1.5rem 1rem;
                font-size: 0.88rem;
                font-weight: 500;
                min-height: 1.5rem;
            }

            .edit-status.success {
                color: #16a34a;
            }

            .edit-status.error {
                color: #dc2626;
            }

            /* Delete confirmation */
            .delete-confirm-box {
                display: none;
                background: #fef2f2;
                border: 1.5px solid #fca5a5;
                border-radius: 10px;
                padding: 1rem 1.25rem;
                margin: 0 1.5rem 1rem;
            }

            .delete-confirm-box.show {
                display: block;
            }

            .delete-confirm-box p {
                margin: 0 0 0.75rem;
                color: #7f1d1d;
                font-size: 0.9rem;
                font-weight: 500;
            }

            .delete-confirm-actions {
                display: flex;
                gap: 0.5rem;
            }
        </style>

        <div class="edit-question-overlay" id="edit-question-overlay">
            <div class="edit-question-modal" id="edit-question-modal">

                <div class="edit-modal-header">
                    <h5><i class="fas fa-pen"></i> Edit Question</h5>
                    <button class="edit-modal-close" onclick="closeEditModal()" title="Close">&times;</button>
                </div>

                <div class="edit-modal-body">
                    <input type="hidden" id="edit-question-id">
                    <input type="hidden" id="edit-question-type">

                    <!-- Question type indicator + toggle -->
                    <div class="edit-type-row">
                        <span class="edit-type-label">Type:</span>
                        <span class="edit-type-badge mcq" id="edit-type-mcq" onclick="switchEditType('multiple_choice')">
                            <i class="fas fa-list-ul"></i> Multiple Choice
                        </span>
                        <span class="edit-type-badge written" id="edit-type-written" onclick="switchEditType('written')">
                            <i class="fas fa-pencil-alt"></i> Written
                        </span>
                    </div>

                    <!-- Question text field -->
                    <div class="edit-field">
                        <label for="edit-question-text"><i class="fas fa-question-circle"></i> Question Text</label>
                        <textarea id="edit-question-text" class="edit-input" rows="4"
                            placeholder="Enter question text..."></textarea>
                    </div>

                    <!-- MCQ fields -->
                    <div id="edit-mcq-section">
                        <div class="edit-options-section">
                            <label><i class="fas fa-check-circle" style="color:#16a34a"></i> Options — select the radio
                                button next to the correct answer</label>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-A" value="A">
                                    <label for="edit-correct-A" class="edit-radio-label">A</label>
                                </div>
                                <input type="text" id="edit-option-a" class="edit-input" placeholder="Option A">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-B" value="B">
                                    <label for="edit-correct-B" class="edit-radio-label">B</label>
                                </div>
                                <input type="text" id="edit-option-b" class="edit-input" placeholder="Option B">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-C" value="C">
                                    <label for="edit-correct-C" class="edit-radio-label">C</label>
                                </div>
                                <input type="text" id="edit-option-c" class="edit-input" placeholder="Option C">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-D" value="D">
                                    <label for="edit-correct-D" class="edit-radio-label">D</label>
                                </div>
                                <input type="text" id="edit-option-d" class="edit-input" placeholder="Option D">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-E" value="E">
                                    <label for="edit-correct-E" class="edit-radio-label">E</label>
                                </div>
                                <input type="text" id="edit-option-e" class="edit-input" placeholder="Option E">
                            </div>
                        </div>
                    </div>

                    <!-- Written fields -->
                    <div id="edit-written-section" style="display:none;">
                        <div class="edit-field">
                            <label for="edit-correct-answer-text"><i class="fas fa-check" style="color:#16a34a"></i> Correct
                                Answer</label>
                            <textarea id="edit-correct-answer-text" class="edit-input" rows="3"
                                placeholder="Enter the expected correct answer..."></textarea>
                        </div>
                        <div class="edit-field">
                            <label for="edit-alternative-answers"><i class="fas fa-random" style="color:#7c3aed"></i>
                                Alternative Acceptable Answers <span style="font-weight:400;color:#94a3b8">(one per line,
                                    optional)</span></label>
                            <textarea id="edit-alternative-answers" class="edit-input" rows="3"
                                placeholder="Enter alternative answers, one per line..."></textarea>
                        </div>
                    </div>

                    <!-- Explanation (both types) -->
                    <div class="edit-field">
                        <label for="edit-explanation"><i class="fas fa-lightbulb" style="color:#d97706"></i> Explanation
                            <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                        <textarea id="edit-explanation" class="edit-input" rows="3"
                            placeholder="Add an explanation for this question..."></textarea>
                    </div>
                </div>

                <!-- Delete confirmation box -->
                <div class="delete-confirm-box" id="delete-confirm-box">
                    <p><i class="fas fa-exclamation-triangle"></i> Are you sure you want to permanently delete this
                        question? This action cannot be undone.</p>
                    <div class="delete-confirm-actions">
                        <button class="edit-btn edit-btn-delete" id="confirm-delete-btn" onclick="confirmDeleteQuestion()">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>
                        <button class="edit-btn edit-btn-cancel" onclick="cancelDeleteQuestion()">Cancel</button>
                    </div>
                </div>

                <div class="edit-status" id="edit-status"></div>

                <div class="edit-modal-footer">
                    <div class="edit-footer-left">
                        <button class="edit-btn edit-btn-delete" id="edit-delete-btn" onclick="promptDeleteQuestion()">
                            <i class="fas fa-trash-alt"></i> Delete Question
                        </button>
                    </div>
                    <div class="edit-footer-right">
                        <button class="edit-btn edit-btn-cancel" onclick="closeEditModal()">Cancel</button>
                        <button class="edit-btn edit-btn-save" id="edit-save-btn" onclick="saveQuestionEdit()">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/tutor-admin-edit.js') }}" defer></script>
    @endif
