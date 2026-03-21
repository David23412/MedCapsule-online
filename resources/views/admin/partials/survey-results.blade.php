@php
    $summary = $surveyInsights['summary'] ?? [];
    $questionInsights = collect($surveyInsights['questions'] ?? []);
    $totalResponses = (int) ($summary['total_responses'] ?? 0);
    $completionRate = (float) ($summary['completion_rate'] ?? 0);
    $palette = ['#93c5fd', '#c4b5fd', '#f9a8d4', '#86efac', '#fcd34d', '#67e8f9', '#fca5a5'];
    $useMockPreview = $totalResponses <= 1;

    if ($useMockPreview) {
        $demoTotalResponses = 126;
        $demoEligibleStudents = 164;
        $demoAnsweredCount = 118;
        $patternsByChoiceCount = [
            4 => [
                [48, 26, 16, 10],
                [41, 29, 18, 12],
                [36, 31, 20, 13],
            ],
            5 => [
                [34, 24, 18, 14, 10],
                [29, 25, 19, 15, 12],
                [32, 23, 20, 14, 11],
            ],
            6 => [
                [28, 22, 17, 13, 11, 9],
                [26, 21, 18, 14, 12, 9],
            ],
        ];

        $singleChoiceIndex = 0;

        $questionInsights = $questionInsights->map(function ($question) use (&$singleChoiceIndex, $demoTotalResponses, $demoAnsweredCount, $patternsByChoiceCount) {
            if (($question['type'] ?? null) !== 'single_choice') {
                $question['answered_count'] = 92;
                $question['response_rate'] = round((92 / $demoTotalResponses) * 100, 1);
                return $question;
            }

            $choices = collect($question['choices'] ?? [])->values();
            $patternSet = $patternsByChoiceCount[$choices->count()] ?? [[100]];
            $pattern = $patternSet[$singleChoiceIndex % count($patternSet)];
            $singleChoiceIndex++;

            $question['answered_count'] = $demoAnsweredCount;
            $question['response_rate'] = round(($demoAnsweredCount / $demoTotalResponses) * 100, 1);
            $question['choices'] = $choices->map(function ($choice, $index) use ($pattern, $demoAnsweredCount) {
                $percentage = (float) ($pattern[$index] ?? 0);

                return array_merge($choice, [
                    'count' => (int) round(($demoAnsweredCount * $percentage) / 100),
                    'percentage' => $percentage,
                    'is_top' => $index === 0,
                ]);
            })->all();

            return $question;
        });

        $summary = array_merge($summary, [
            'eligible_students' => $demoEligibleStudents,
            'completed_students' => 118,
            'students_pending' => 46,
            'total_responses' => $demoTotalResponses,
            'drafts_in_progress' => 9,
            'completion_rate' => 72.0,
        ]);

        $totalResponses = (int) $summary['total_responses'];
        $completionRate = (float) $summary['completion_rate'];
    }

    $topChoice = function (?array $question) {
        return collect($question['choices'] ?? [])->sortByDesc('count')->sortByDesc('percentage')->first();
    };

    $formatPercent = function ($value, int $decimals = 0) {
        return number_format((float) $value, $decimals, '.', '') . '%';
    };

    $questionById = fn (string $id) => $questionInsights->firstWhere('id', $id);

    $percentageForValues = function (?array $question, array $values) {
        return round(collect($question['choices'] ?? [])->whereIn('value', $values)->sum('percentage'), 1);
    };

    $buildChart = function (?array $question) use ($palette) {
        $choices = collect($question['choices'] ?? [])->filter(fn ($choice) => ($choice['count'] ?? 0) > 0)->values();

        if ($choices->isEmpty()) {
            return 'conic-gradient(#eef2ff 0 100%)';
        }

        $start = 0;
        $segments = [];

        foreach ($choices as $index => $choice) {
            $percentage = (float) ($choice['percentage'] ?? 0);
            $end = min(100, $start + $percentage);
            $segments[] = sprintf('%s %.1f%% %.1f%%', $palette[$index % count($palette)], $start, $end);
            $start = $end;
        }

        if ($start < 100) {
            $segments[] = sprintf('#eef2ff %.1f%% 100%%', $start);
        }

        return 'conic-gradient(' . implode(', ', $segments) . ')';
    };

    $timeSavedQuestion = $questionById('q5');
    $deviceQuestion = $questionById('q6');
    $featureQuestion = $questionById('q9');
    $yearQuestion = $questionById('q10');

@endphp

<div class="tab-pane fade {{ ($activeTab ?? 'access') === 'survey-results' ? 'show active' : '' }}" id="survey-results" role="tabpanel">
    <div class="survey-pane-shell">
    <div class="row g-4">
        <div class="col-12">
            <div class="card survey-hero-card">
                <div class="card-body p-4 p-lg-5">
                    <span class="survey-hero-orb orb-one"></span>
                    <span class="survey-hero-orb orb-two"></span>
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-start survey-hero-layout">
                        <div class="survey-hero-copy">
                            <div class="survey-question-kicker survey-premium-muted">Free Course Unlock Survey</div>
                            <h3 class="survey-hero-title">Presentation-ready survey insights</h3>
                            <p class="survey-hero-body mb-0">
                                {{ $useMockPreview ? 'Previewing polished sample distributions so you can see the presentation look before full survey volume comes in.' : 'Minimal pastel visuals designed to help you present student preferences quickly and clearly.' }}
                            </p>
                        </div>
                        <div class="survey-highlight-chip">
                            <i class="fas fa-chart-pie"></i>
                            <span>{{ $formatPercent($completionRate) }} completion</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($totalResponses === 0)
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <div>No survey responses yet</div>
                    <div class="small mt-2">Once students complete the free-course unlock survey, the pastel pie charts and insights appear here automatically.</div>
                </div>
            </div>
        @else
            <div class="col-12 survey-question-stage">
                <div class="d-flex flex-column gap-3">
                    @foreach($questionInsights as $question)
                        @php
                            $leadingChoice = $topChoice($question);
                        @endphp
                        <div class="card survey-question-card">
                            <div class="card-body">
                                <div class="survey-question-topline">
                                    <div>
                                        <div class="survey-question-kicker">{{ strtoupper($question['id'] ?? 'Q') }}</div>
                                        <h5 class="mb-1">{{ $question['prompt'] ?? 'Survey question' }}</h5>
                                        @if(($question['type'] ?? null) === 'single_choice' && !empty($leadingChoice))
                                            <div class="small survey-premium-muted mt-2">
                                                <strong>{{ $formatPercent($leadingChoice['percentage'] ?? 0) }}</strong>
                                                selected <strong>{{ $leadingChoice['label'] ?? 'the top answer' }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if(($question['type'] ?? null) === 'single_choice')
                                    <div class="d-flex flex-column flex-md-row survey-question-main">
                                        <div class="survey-chart-shell">
                                            @php
                                                $chartChoices = collect($question['choices'] ?? [])->filter(fn ($choice) => ((float) ($choice['percentage'] ?? 0)) > 0)->values();
                                                $radius = 54;
                                                $circumference = 2 * pi() * $radius;
                                                $progressOffset = 0;
                                            @endphp
                                            <div class="survey-donut">
                                                <svg class="survey-donut-svg" viewBox="0 0 120 120" aria-hidden="true">
                                                    <g transform="rotate(-90 60 60)">
                                                        <circle class="survey-donut-ring" cx="60" cy="60" r="{{ $radius }}"></circle>
                                                        @foreach($chartChoices as $index => $choice)
                                                            @php
                                                                $percentage = (float) ($choice['percentage'] ?? 0);
                                                                $segmentLength = ($circumference * $percentage) / 100;
                                                                $dashArray = $segmentLength . ' ' . max($circumference - $segmentLength, 0);
                                                                $dashOffset = -$progressOffset;
                                                                $progressOffset += $segmentLength;
                                                            @endphp
                                                            <circle
                                                                class="survey-donut-segment"
                                                                cx="60"
                                                                cy="60"
                                                                r="{{ $radius }}"
                                                                stroke="{{ $palette[$index % count($palette)] }}"
                                                                stroke-width="18"
                                                                stroke-dasharray="{{ $dashArray }}"
                                                                stroke-dashoffset="{{ $dashOffset }}"
                                                                data-label="{{ $choice['label'] ?? 'Option' }}"
                                                                data-percent="{{ $formatPercent($percentage) }}"
                                                            ></circle>
                                                        @endforeach
                                                    </g>
                                                </svg>
                                                <div class="survey-donut-core"></div>
                                                <div class="survey-donut-center">
                                                    <strong class="survey-animated-number" data-target="{{ (float) ($leadingChoice['percentage'] ?? 0) }}">{{ $formatPercent($leadingChoice['percentage'] ?? 0, 0) }}</strong>
                                                    <span>top answer</span>
                                                </div>
                                                <div class="survey-donut-tooltip"></div>
                                            </div>
                                        </div>

                                        <div class="flex-grow-1 w-100">
                                            @foreach(($question['choices'] ?? []) as $index => $choice)
                                                <div class="survey-legend-item">
                                                    <span class="survey-legend-dot" style="background: {{ $palette[$index % count($palette)] }};"></span>
                                                    <div class="survey-legend-content">
                                                        <div class="d-flex justify-content-between align-items-center gap-3">
                                                            <span class="fw-medium">{{ $choice['label'] ?? 'Option' }}</span>
                                                            <span class="small survey-premium-muted">
                                                                {{ number_format((int) ($choice['count'] ?? 0)) }} responses · {{ $formatPercent($choice['percentage'] ?? 0) }}
                                                            </span>
                                                        </div>
                                                        <div class="survey-legend-progress">
                                                            <div
                                                                class="survey-legend-progress-fill"
                                                                style="width: {{ (float) ($choice['percentage'] ?? 0) }}%; background: {{ $palette[$index % count($palette)] }};"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="survey-feedback-card rounded p-3">
                                        <div class="small survey-premium-muted mb-1">Written feedback collected</div>
                                        <div class="fw-semibold">{{ number_format((int) ($question['answered_count'] ?? 0)) }} responses</div>
                                        <div class="small survey-premium-muted mt-2">Use the sample quotes on the right for a quick presentation snapshot.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    </div>
</div>
