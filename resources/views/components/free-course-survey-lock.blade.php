@php
    $surveyDefinition = config('free_course_survey');
    $surveyQuestions = $surveyDefinition['questions'] ?? [];
@endphp

<div class="survey-lock-backdrop" id="freeCourseSurveyBackdrop" hidden></div>
<div class="survey-lock-modal" id="freeCourseSurveyModal" role="dialog" aria-modal="true" aria-labelledby="freeCourseSurveyTitle" hidden>
    <button type="button" class="survey-lock-close" id="freeCourseSurveyClose" aria-label="إغلاق">
        <i class="fas fa-times"></i>
    </button>

    <div id="freeCourseSurveyIntro">
        <div class="survey-lock-eyebrow">استبيان واحد. فتح فوري.</div>
        <h2 class="survey-lock-title" id="freeCourseSurveyTitle">افتح كل الكورسات المجانية</h2>
        <p class="survey-lock-body">جاوب استبيان قصير مرة واحدة فقط، وافتح كل الكورسات المجانية فوراً.</p>

        <div class="survey-lock-course-box">
            <div class="survey-lock-course-box-title">الكورسات المتاحة بعد الفتح</div>
            <ul class="survey-lock-course-list">
                @foreach ($freeCoursesForSurveyUnlock as $freeCourse)
                    <li>{{ $freeCourse['name'] }}</li>
                @endforeach
            </ul>
        </div>

        <div class="survey-lock-actions">
            <button type="button" class="survey-lock-primary" id="freeCourseSurveyStart">
                ابدأ الاستبيان
            </button>
            <button type="button" class="survey-lock-secondary" id="freeCourseSurveyLater">
                لاحقاً
            </button>
        </div>
    </div>

    <div id="freeCourseSurveyFlow" hidden>
        <div class="survey-progress-header">
            <div>
                <div class="survey-progress-label">تقدّم الاستبيان</div>
                <div class="survey-progress-value" id="surveyProgressValue"></div>
            </div>
            <div class="survey-save-status" id="surveySaveStatus">يتم الحفظ تلقائياً</div>
        </div>

        <div class="survey-progress-track">
            <div class="survey-progress-fill" id="surveyProgressFill"></div>
        </div>

        <form id="freeCourseSurveyForm" class="survey-form" novalidate>
            @foreach ($surveyQuestions as $question)
                <section
                    class="survey-question-card"
                    data-question-id="{{ $question['id'] }}"
                    data-question-type="{{ $question['type'] }}"
                    data-required="{{ ($question['required'] ?? false) ? 'true' : 'false' }}"
                >
                    <div class="survey-question-meta">
                        <span>السؤال {{ $loop->iteration }}</span>
                        @unless($question['required'] ?? false)
                            <span>{{ $surveyDefinition['optional_label'] }}</span>
                        @endunless
                    </div>

                    <h2 class="survey-question-title">{{ $question['prompt'] }}</h2>

                    @if (($question['type'] ?? null) === 'single_choice')
                        <div class="survey-options-grid">
                            @foreach (($question['options'] ?? []) as $optionValue => $optionLabel)
                                <label class="survey-option-card">
                                    <input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $optionValue }}">
                                    <span>{{ $optionLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <textarea
                            class="survey-textarea"
                            name="answers[{{ $question['id'] }}]"
                            rows="5"
                            maxlength="{{ $question['max_length'] ?? 1000 }}"
                            placeholder="{{ $question['placeholder'] ?? '' }}"
                        ></textarea>
                    @endif

                    <div class="survey-error" data-error-for="{{ $question['id'] }}"></div>
                </section>
            @endforeach

            <div class="survey-actions">
                <button type="button" class="survey-btn survey-btn-secondary" id="surveyPrevBtn">السابق</button>
                <button type="button" class="survey-btn survey-btn-primary" id="surveyNextBtn">التالي</button>
                <button type="submit" class="survey-btn survey-btn-primary" id="surveySubmitBtn">إرسال وفتح الكورسات</button>
            </div>
        </form>
    </div>
</div>

<div class="survey-success-overlay" id="surveySuccessOverlay" hidden>
    <div class="survey-success-card">
        <div class="survey-success-icon">✓</div>
        <h2>{{ $surveyDefinition['success_message'] }}</h2>
        <p>جاري تحويلك للكورس الذي اخترته.</p>
    </div>
</div>
