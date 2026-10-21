<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Read Mode - MedCapsule</title>
    @include('partials.favicon')
    @include('partials.admin-dark-mode-head')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            color: #0f172a;
        }

        .read-shell {
            width: 100%;
            min-height: 100vh;
            padding: 0 0 64px;
        }

        .back-link-wrap {
            position: sticky;
            top: 0;
            z-index: 30;
            padding: 18px 40px;
            margin-bottom: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .read-header {
            background: #ffffff;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            padding: 32px 40px;
            margin-bottom: 0;
        }

        .read-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }

        .read-header h1 {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            margin: 0 0 8px;
            font-weight: 700;
        }

        .read-meta {
            color: #475569;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            font-size: 0.95rem;
        }

        .source-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .source-chip {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .question-card {
            background: #ffffff;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            padding: 32px 40px;
            margin-bottom: 0;
        }

        .question-card.has-image {
            padding-right: 36px;
        }

        .question-card:last-of-type {
            border-bottom: none;
            border-radius: 0;
        }

        .read-content-sheet {
            background: #ffffff;
            min-height: 100vh;
        }

        .question-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .question-header-main {
            flex: 1 1 auto;
            min-width: 0;
        }

        .question-number {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .question-text {
            font-size: 1.1rem;
            line-height: 1.7;
            font-weight: 600;
            color: #0f172a;
        }

        .question-body {
            display: flex;
            align-items: flex-start;
            gap: 2.5rem;
            width: 100%;
        }

        .question-main-content {
            flex: 1 1 auto;
            min-width: 0;
        }

        .badge-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            flex: 0 0 auto;
        }

        .badge-stack .badge {
            font-size: 0.75rem;
            border-radius: 4px;
        }

        .read-edit-btn {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #fff;
            color: #475569;
            padding: 6px 10px;
            font: inherit;
            font-size: 0.8rem;
        }

        .read-edit-btn:hover { background: #f8fafc; color: #0f172a; }

        .mistake-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            padding: 0;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
        }

        .mistake-btn:hover:not(:disabled) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .mistake-btn.is-saved {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #15803d;
            cursor: default;
            box-shadow: none;
        }



        .mistake-btn:disabled {
            cursor: default;
            opacity: 0.85;
        }

        .question-image-container {
            flex: 0 0 450px;
            max-width: 45%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .question-image {
            width: 100%;
            height: auto;
            max-height: 550px;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .question-image:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15);
        }

        .options-list {
            display: grid;
            gap: 12px;
            margin: 18px 0 20px;
        }

        .option-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            background: #f8fafc;
        }

        .option-row.is-correct {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .option-letter {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .option-row.is-correct .option-letter {
            background: #16a34a;
            color: #fff;
        }

        .option-body {
            flex: 1;
            color: #1e293b;
            line-height: 1.65;
        }

        .correct-answer,
        .explanation-panel {
            border-radius: 16px;
            padding: 16px 18px;
        }

        .correct-answer {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 18px;
            min-height: 0;
        }

        .explanation-panel {
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .panel-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .correct-answer .panel-label {
            color: #1d4ed8;
        }

        .explanation-panel .panel-label {
            color: #c2410c;
        }

        .panel-body {
            color: #1e293b;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .correct-answer .panel-label {
            margin-bottom: 0;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .correct-answer .panel-body {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            min-height: 32px;
            font-size: 1.02rem;
            font-weight: 600;
            line-height: 1.5;
            white-space: normal;
            text-align: left;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            padding: 4px 0;
        }

        .back-link:hover {
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        @media (max-width: 768px) {
            .read-shell {
                width: 100%;
                padding: 0 0 48px;
            }

            .read-content-sheet {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .back-link-wrap {
                top: 0;
                padding: 12px 16px;
                border-bottom: 1px solid #f1f5f9;
            }

            .question-card,
            .read-header {
                border-radius: 0;
                padding: 24px 16px;
                border-bottom: 1px solid #f1f5f9;
            }

            .question-card.has-image {
                padding-right: 16px;
            }

            .question-top {
                flex-direction: column;
            }

            .question-body {
                flex-direction: column;
                gap: 1.5rem;
            }

            .question-image-container {
                flex: none;
                width: 100%;
                max-width: 100%;
                margin: 0 0 0.5rem;
            }

            .question-image {
                max-height: 400px;
            }

            .question-header-main {
                width: 100%;
            }

            .badge-stack {
                align-items: center;
                flex-direction: row;
                flex-wrap: wrap;
                width: 100%;
                margin-top: 16px;
                gap: 10px;
            }

            .correct-answer {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 14px 16px;
            }

            .correct-answer .panel-body {
                width: 100%;
                min-height: 0;
                font-size: 0.98rem;
                line-height: 1.55;
            }

            .back-link {
                width: 100%;
                justify-content: center;
                padding: 11px 14px;
            }

            .mistake-btn {
                width: 36px;
                height: 36px;
                margin: 0;
            }
        }
    </style>
    @include('partials.phone-accessibility')
</head>
<body>
    @php
        $mistakeLookup = array_fill_keys($mistakeQuestionIds ?? [], true);
    @endphp
    <div class="read-shell">
        <div class="read-content-sheet">
            <div class="back-link-wrap">
                <a href="{{ route('topics.forCourse', $course->id) }}" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Topics</span>
                </a>
            </div>
            <section class="read-header">
                <div class="read-kicker">
                    <i class="fas fa-book-open"></i>
                    <span>Read Mode</span>
                </div>
            <h1>{{ $topic->name }}</h1>
            <div class="read-meta">
                <span><strong>Course:</strong> {{ $course->name }}</span>
                <span><strong>Questions:</strong> {{ $questions->count() }}</span>
                <span><strong>Mode:</strong> Solved with explanations</span>
            </div>
            <div class="source-chip-row">
                @foreach($selected_sources as $source)
                    <span class="source-chip">{{ $source === 'all' ? 'All Sources' : $source }}</span>
                @endforeach
            </div>
        </section>

        @foreach($questions as $index => $question)
            @php
                $rawOptions = [
                    'A' => $question->option_a,
                    'B' => $question->option_b,
                    'C' => $question->option_c,
                    'D' => $question->option_d,
                    'E' => $question->option_e,
                ];
            @endphp
            <article data-question-id="{{ $question->id }}" class="question-card {{ $question->image_url ? 'has-image' : '' }}">
                <div class="question-top">
                    <div class="question-header-main">
                        <div class="question-number">Question {{ $index + 1 }}</div>
                        <div class="question-text">{{ $question->question }}</div>
                    </div>
                    <div class="badge-stack">
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="read-edit-btn" onclick="openEditModal({{ $question->id }})" aria-label="Edit question {{ $index + 1 }}">
                                <i class="fas fa-pen" aria-hidden="true"></i> Edit
                            </button>
                        @endif
                        @php $isMistakeSaved = isset($mistakeLookup[$question->id]); @endphp
                        <button
                            type="button"
                            class="mistake-btn{{ $isMistakeSaved ? ' is-saved' : '' }}"
                            data-question-id="{{ $question->id }}"
                            data-add-mistake-url="{{ route('questions.add-mistake', $question->id) }}"
                            title="{{ $isMistakeSaved ? 'In Mistakes' : 'Add to Mistakes' }}"
                            {{ $isMistakeSaved ? 'disabled' : '' }}>
                            <i class="fas {{ $isMistakeSaved ? 'fa-check' : 'fa-plus' }}"></i>
                        </button>
                        @if(!empty($question->source))
                            <span class="badge text-bg-secondary">{{ $question->source }}</span>
                        @endif
                        @if(!empty($question->enhanced_source_badges))
                            @foreach($question->enhanced_source_badges as $badge)
                                @if(isset($topic) && strtoupper($topic->name) === strtoupper($badge))
                                    @continue
                                @endif
                                @if(str_contains($badge, 'HIGH YIELD'))
                                    <span class="badge text-bg-danger">🔥 {{ $badge }}</span>
                                @elseif(str_contains($badge, 'REPEATED'))
                                    <span class="badge text-bg-warning">📈 {{ $badge }}</span>
                                @else
                                    <span class="badge text-bg-primary">{{ $badge }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="question-body">
                    <div class="question-main-content">
                        @if(($question->question_type ?? 'multiple_choice') === 'multiple_choice')
                            <div class="options-list">
                                @foreach($rawOptions as $letter => $optionValue)
                                    @if(!empty(trim((string) $optionValue)) && trim((string) $optionValue) !== '-')
                                        <div class="option-row {{ $question->correct_answer === $letter ? 'is-correct' : '' }}">
                                            <span class="option-letter">{{ $letter }}</span>
                                            <div class="option-body">{{ $optionValue }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="correct-answer">
                            <div class="panel-label">
                                <i class="fas fa-check-circle"></i>
                                <span>Correct Answer</span>
                            </div>
                            <div class="panel-body">
                                @if(($question->question_type ?? 'multiple_choice') === 'multiple_choice')
                                    {{ $question->correct_answer }}
                                    @if(!empty($rawOptions[$question->correct_answer] ?? null))
                                        : {{ $rawOptions[$question->correct_answer] }}
                                    @endif
                                @else
                                    {{ $question->correct_answer ?: 'No model answer provided.' }}
                                @endif
                            </div>
                        </div>

                        <div class="explanation-panel">
                            <div class="panel-label">
                                <i class="fas fa-lightbulb"></i>
                                <span>Explanation</span>
                            </div>
                            <div class="panel-body">{{ $question->explanation ?: 'No explanation available.' }}</div>
                        </div>
                    </div>

                    @if($question->image_url)
                        <div class="question-image-container">
                            <img src="{{ asset($question->image_url) }}"
                                 alt="Question image"
                                 class="question-image"
                                 onerror="this.closest('.question-image-container').style.display='none'">
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
        </div>
    </div>
    @if(auth()->user()->isAdmin())
        @php
            $adminQuestionData = $questions->mapWithKeys(fn ($question) => [$question->id => [
                'question_text' => $question->question,
                'question_type' => $question->question_type,
                'correct_answer' => $question->correct_answer,
                'alternative_answers' => $question->alternative_answers,
                'explanation' => $question->explanation,
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'option_d' => $question->option_d,
                'option_e' => $question->option_e,
            ]]);
        @endphp
        <script>
            window.TUTOR_CONFIG = {
                isAdmin: true,
                refreshAfterEdit: true,
                questionData: {{ Illuminate\Support\Js::from($adminQuestionData) }}
            };
        </script>
        @include('partials.admin-question-editor')
    @endif
    <script>
        (function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function setMistakeButtonSavedState(button) {
                button.disabled = true;
                button.classList.add('is-saved');
                button.title = 'In Mistakes';
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-check';
                }
            }

            document.querySelectorAll('.mistake-btn[data-add-mistake-url]').forEach((button) => {
                button.addEventListener('click', async function() {
                    if (button.disabled) {
                        return;
                    }

                    const originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    try {
                        const response = await fetch(button.dataset.addMistakeUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Failed to add mistake');
                        }

                        setMistakeButtonSavedState(button);
                    } catch (_error) {
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                        alert('Failed to add this question to mistakes. Please try again.');
                    }
                });
            });
        })();
    </script>
</body>
</html>
