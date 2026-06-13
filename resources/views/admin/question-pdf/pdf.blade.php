<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 34px 36px 42px;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #070707;
            font-size: 13px;
            line-height: 1.25;
        }

        .page-shell {
            border: 3px solid #12a9db;
            padding: 28px 42px 34px;
            min-height: 970px;
            position: relative;
        }

        .page-shell:before {
            content: "";
            position: absolute;
            inset: 5px;
            border: 2px solid #168bc5;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .top-logo {
            position: absolute;
            top: 18px;
            right: 34px;
            width: 52px;
            height: 26px;
            z-index: 2;
        }

        .course-band {
            background: #1e5f9f;
            color: #fff;
            text-align: center;
            font-size: 17px;
            font-weight: 800;
            padding: 5px 10px 6px;
            margin: 18px 42px 12px;
        }

        .topic-heading {
            color: #1e5f9f;
            text-align: center;
            font-size: 21px;
            font-weight: 800;
            margin: 0 0 24px;
        }

        .topic-meta {
            color: #4b5563;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            margin-top: -16px;
            margin-bottom: 18px;
            text-transform: uppercase;
        }

        .watermark {
            position: fixed;
            top: 330px;
            left: 96px;
            color: rgba(0, 0, 0, 0.09);
            font-size: 72px;
            font-weight: 800;
            transform: rotate(-42deg);
            z-index: 0;
        }

        .question {
            margin-bottom: 22px;
            page-break-inside: avoid;
        }

        .question-text {
            font-weight: 800;
            font-size: 13.7px;
        }

        .question-number {
            color: #1e5f9f;
            font-weight: 900;
        }

        .source-label {
            color: #64748b;
            font-size: 9.5px;
            font-weight: 700;
            margin-left: 4px;
        }

        .options {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .options td {
            width: 50%;
            padding: 3px 20px 5px 26px;
            vertical-align: top;
            font-size: 13px;
            line-height: 1.35;
        }

        .long-options td {
            display: block;
            width: auto;
            padding-left: 26px;
            padding-right: 18px;
            padding-bottom: 5px;
        }

        .written-lines {
            margin: 12px 0 4px 26px;
            color: #111827;
            font-size: 15px;
            letter-spacing: 1px;
            line-height: 1.55;
        }

        .answer-key h2 {
            color: #1e5f9f;
            font-size: 14px;
            margin: 16px 0 8px;
        }

        .answer-key-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .answer-key-table th,
        .answer-key-table td {
            border: 1px solid #49648e;
            padding: 6px 8px;
            text-align: center;
            font-weight: 800;
        }

        .answer-key-table th {
            background: #213f6d;
            color: #fff;
        }

        .answer-key-table td {
            background: #dbe5f5;
            color: #1e5f9f;
        }

        .footer {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #111827;
        }

        .marketing-line {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            color: #1e5f9f;
            font-size: 11px;
            font-weight: 800;
        }

        .topic-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="watermark">MedCapsule</div>
    <div class="footer">
        <script type="text/php">
            if (!function_exists("mc_pdf_reverse_rtl")) {
                function mc_pdf_reverse_rtl($text) {
                    return implode("", array_reverse(preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY)));
                }
            }

            if (isset($pdf)) {
                $pdf->page_text(297, 812, "{PAGE_NUM}", "DejaVu Sans", 10, [0, 0, 0]);
                $pdf->page_text(202, 796, "medcapsule.xyz " . mc_pdf_reverse_rtl("باقي الأسئلة على"), "DejaVu Sans", 11, [0.117, 0.373, 0.624]);
            }
        </script>
    </div>

    @php
        $globalNumber = 1;
        $printedTopicCount = 0;
    @endphp

    @foreach ($topics as $topicIndex => $topic)
        @php
            $questions = $questionsByTopic->get($topic->id, collect());
        @endphp

        @if ($questions->isNotEmpty())
            @php $topicAnswerRows = []; @endphp
            <div class="page-shell {{ $printedTopicCount > 0 ? 'topic-break' : '' }}">
                <img class="top-logo" src="data:image/svg+xml;base64,{{ $logoSvg }}" alt="MedCapsule">
                <div class="content">
                    <div class="course-band">{{ $course->name }}</div>
                    <h1 class="topic-heading">{{ $topic->name }}</h1>
                    <div class="topic-meta">
                        Sources: {{ $questions->pluck('source')->filter()->unique()->implode(', ') }}
                    </div>

                    @foreach ($questions as $question)
                        @php
                            $options = collect([
                                'A' => $question->option_a,
                                'B' => $question->option_b,
                                'C' => $question->option_c,
                                'D' => $question->option_d,
                                'E' => $question->option_e,
                            ])->filter(function ($value) {
                                $normalized = trim((string) $value);

                                return $normalized !== '' && !in_array($normalized, ['-', '–', '—'], true);
                            });
                            $hasLongOption = $options->contains(fn ($value) => mb_strlen((string) $value) > 42);
                            $isWrittenQuestion = $options->isEmpty() || $question->question_type === \App\Models\Question::TYPE_WRITTEN;

                            if (!$isWrittenQuestion) {
                                $topicAnswerRows[] = [
                                    'number' => $globalNumber,
                                    'answer' => $question->correct_answer,
                                ];
                            }
                        @endphp

                        <div class="question">
                            <div class="question-text">
                                <span class="question-number">{{ $globalNumber }}.</span>
                                {{ $question->question }}
                                @if ($question->source)
                                    <span class="source-label">({{ $question->source }})</span>
                                @endif
                            </div>

                            @if ($options->isNotEmpty())
                                <table class="options {{ $hasLongOption ? 'long-options' : '' }}">
                                    @if ($hasLongOption)
                                        @foreach ($options as $letter => $option)
                                            <tr><td>{{ $letter }}. {{ $option }}</td></tr>
                                        @endforeach
                                    @else
                                        @foreach ($options->chunk(2) as $optionRow)
                                            <tr>
                                                @foreach ($optionRow as $letter => $option)
                                                    <td>{{ $letter }}. {{ $option }}</td>
                                                @endforeach
                                                @if ($optionRow->count() === 1)
                                                    <td></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                </table>
                            @else
                                <div class="written-lines">
                                    ---------<br>
                                    ---------<br>
                                    ---------
                                </div>
                            @endif
                        </div>

                        @php $globalNumber++; @endphp
                    @endforeach

                    @if ($includeAnswerKey && count($topicAnswerRows) > 0)
                        <div class="answer-key">
                            <h2>MCQ Answer Key</h2>
                            <table class="answer-key-table">
                                @foreach (collect($topicAnswerRows)->chunk(10) as $answerChunk)
                                    <tr>
                                        @foreach ($answerChunk as $row)
                                            <th>Q{{ $row['number'] }}</th>
                                        @endforeach
                                        @for ($i = $answerChunk->count(); $i < 10; $i++)
                                            <th></th>
                                        @endfor
                                    </tr>
                                    <tr>
                                        @foreach ($answerChunk as $row)
                                            <td>{{ $row['answer'] }}</td>
                                        @endforeach
                                        @for ($i = $answerChunk->count(); $i < 10; $i++)
                                            <td></td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @php $printedTopicCount++; @endphp
        @endif
    @endforeach
</body>
</html>
