<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Mistakes</title>
    <style>
        @page {
            margin: 28px 34px;
        }

        body {
            color: #1f2937;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.48;
        }

        h1 {
            color: #111827;
            font-size: 24px;
            margin: 0 0 6px;
        }

        .meta {
            border-bottom: 1px solid #d1d5db;
            color: #4b5563;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }

        .mistake {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-bottom: 14px;
            padding: 14px;
            page-break-inside: avoid;
        }

        .mistake-title {
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .context {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .section {
            margin-top: 10px;
        }

        .label {
            color: #374151;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .answer {
            border-left: 3px solid #d1d5db;
            padding-left: 8px;
        }

        .submitted {
            border-left-color: #dc2626;
        }

        .correct {
            border-left-color: #16a34a;
        }

        .explanation {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 8px;
        }
    </style>
</head>
<body>
    <h1>My Mistakes</h1>
    <div class="meta">
        Student: {{ $user->name }}<br>
        Scope: {{ $courseName }}<br>
        Generated: {{ $generatedAt->format('M j, Y g:i A') }}<br>
        Total mistakes: {{ $mistakes->count() }}
    </div>

    @foreach($mistakes as $mistake)
        <div class="mistake">
            <div class="mistake-title">{{ $mistake->number }}. {!! nl2br(e($mistake->question)) !!}</div>
            <div class="context">
                {{ $mistake->course_name }} / {{ $mistake->topic_name }}
                @if($mistake->last_attempt_date)
                    / Last attempted {{ \Carbon\Carbon::parse($mistake->last_attempt_date)->format('M j, Y') }}
                @endif
            </div>

            <div class="section">
                <div class="label">My submitted answer</div>
                <div class="answer submitted">{!! nl2br(e($mistake->submitted_answer_display)) !!}</div>
            </div>

            <div class="section">
                <div class="label">Correct answer</div>
                <div class="answer correct">{!! nl2br(e($mistake->correct_answer_display)) !!}</div>
            </div>

            <div class="section">
                <div class="label">Explanation</div>
                <div class="explanation">
                    {!! nl2br(e($mistake->explanation ?: 'No explanation provided.')) !!}
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
