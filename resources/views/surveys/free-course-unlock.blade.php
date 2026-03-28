<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $survey['title'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/free-course-survey.css') }}">
</head>
<body class="free-course-survey-page">
    <div class="survey-page-shell">
        <aside class="survey-side-panel">
            <div class="survey-side-eyebrow">{{ $survey['intro_eyebrow'] }}</div>
            <h1 class="survey-side-title">{{ $survey['intro_title'] }}</h1>
            <p class="survey-side-body">{{ $survey['intro_body'] }}</p>

            <div class="survey-side-card">
                <div class="survey-side-card-title">These courses will unlock immediately</div>
                <ul class="survey-side-list">
                    @foreach ($freeCourses as $freeCourse)
                        <li>{{ $freeCourse->name }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="survey-side-meta">
                <span>One time only</span>
                <span>Auto-save</span>
                <span>Under 2 minutes</span>
            </div>
        </aside>

        <main class="survey-main-panel">
            <a href="{{ route('courses.index') }}" class="survey-back-link">Back to Courses</a>

            <div class="survey-progress-header">
                <div>
                    <div class="survey-progress-label">Survey progress</div>
                    <div class="survey-progress-value" id="surveyProgressValue"></div>
                </div>
            </div>

            <div class="survey-progress-track">
                <div class="survey-progress-fill" id="surveyProgressFill"></div>
            </div>

            <form id="freeCourseSurveyForm" class="survey-form" novalidate>
                @foreach ($questions as $question)
                    <section
                        class="survey-question-card"
                        data-question-id="{{ $question['id'] }}"
                        data-question-type="{{ $question['type'] }}"
                        data-required="{{ $question['required'] ? 'true' : 'false' }}"
                    >
                        <div class="survey-question-meta">
                            <span>Question {{ $loop->iteration }}</span>
                            @unless($question['required'])
                                <span>{{ $survey['optional_label'] }}</span>
                            @endunless
                        </div>

                        <h2 class="survey-question-title">{{ $question['prompt'] }}</h2>
                        @unless($question['required'])
                            <p class="survey-optional-hint">You can leave this blank and unlock the course right away.</p>
                        @endunless

                        @if ($question['type'] === 'single_choice')
                            <div class="survey-options-grid">
                                @foreach ($question['options'] as $optionValue => $optionLabel)
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
                                rows="6"
                                maxlength="{{ $question['max_length'] ?? 1000 }}"
                                placeholder="{{ $question['placeholder'] ?? '' }}"
                            ></textarea>
                        @endif

                        <div class="survey-error" data-error-for="{{ $question['id'] }}"></div>
                    </section>
                @endforeach

                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="survey-actions">
                    <button type="button" class="survey-btn survey-btn-secondary" id="surveyPrevBtn">Previous</button>
                    <button type="button" class="survey-btn survey-btn-primary" id="surveyNextBtn">Next</button>
                    <button
                        type="submit"
                        class="survey-btn survey-btn-primary"
                        id="surveySubmitBtn"
                        data-default-label="Unlock Course"
                        data-skip-label="Skip and Unlock Course"
                        data-submitting-label="Unlocking..."
                    >Unlock Course</button>
                </div>
            </form>
        </main>
    </div>

    <div class="survey-success-overlay" id="surveySuccessOverlay" hidden>
        <div class="survey-success-card">
            <div class="survey-success-icon">✓</div>
            <h2>{{ $survey['success_message'] }}</h2>
            <p>Redirecting you now.</p>
        </div>
    </div>

    @php
        $draftPayload = $draft ? [
            'answers' => $draft->answers ?? [],
            'current_step' => $draft->current_step,
            'redirect_to' => $draft->redirect_to,
        ] : null;
    @endphp

    <script>
        window.FreeCourseSurveyConfig = {
            draftUrl: @json(route('free-course-survey.draft')),
            submitUrl: @json(route('free-course-survey.submit')),
            redirectTo: @json($redirectTo),
            survey: @json($survey),
            questions: @json($questions),
            draft: @json($draftPayload),
        };
    </script>
    <script src="{{ asset('js/free-course-survey.js') }}" defer></script>
</body>
</html>
