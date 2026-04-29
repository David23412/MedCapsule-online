<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $student->name }} - Student Overview - MedCapsule</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @php
        $overview = $data['overview'] ?? [];
        $studyPatterns = $data['study_patterns'] ?? [];
        $realTimeStats = $data['real_time_stats'] ?? [];
        $recentQuizzes = $data['recent_quizzes'] ?? [];
        $strengthAreas = $realTimeStats['strength_areas'] ?? [];
        $weaknessAreas = $realTimeStats['weakness_areas'] ?? [];
        $improvementRate = $realTimeStats['improvement_rate'] ?? 0;
        $lastActive = $overview['last_active'] ?? null;
        $memberSince = $overview['member_since'] ?? null;
        $disciplineLabels = [
            'active' => 'On Track',
            'at_risk' => 'Needs Attention',
            'inactive' => 'Inactive',
        ];
        $disciplineClass = [
            'active' => 'status-good',
            'at_risk' => 'status-warn',
            'inactive' => 'status-bad',
        ][$discipline['status']] ?? 'status-warn';
        $disciplineLabel = $disciplineLabels[$discipline['status']] ?? ucfirst(str_replace('_', ' ', $discipline['status']));
        $trendLabel = ucfirst($studyPatterns['improvement_trend'] ?? 'stable');
        $completedTasks = $planner['recent_outcomes']->where('is_completed', true)->count();
    @endphp

    <style>
        :root {
            --bg: #f5f7fa;
            --panel: #ffffff;
            --panel-muted: #f8fafc;
            --border: #dbe2ea;
            --border-strong: #c8d3df;
            --text: #15202b;
            --text-muted: #5d6b79;
            --accent: #1f4e79;
            --accent-soft: #eaf1f7;
            --good: #1f7a4f;
            --good-soft: #e9f7ef;
            --warn: #8a5a12;
            --warn-soft: #fff6df;
            --bad: #9f2d2d;
            --bad-soft: #fdeeee;
            --radius: 14px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Manrope', sans-serif;
            line-height: 1.45;
        }

        .page-shell {
            max-width: 1360px;
            margin: 0 auto;
            padding: 24px;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(245, 247, 250, 0.94);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(200, 211, 223, 0.85);
            margin: 0 -24px 24px;
            padding: 16px 24px;
        }

        .topbar-inner,
        .header-row,
        .summary-grid,
        .main-grid,
        .meta-grid,
        .metric-grid,
        .compact-grid,
        .area-list,
        .table-meta {
            display: grid;
            gap: 16px;
        }

        .topbar-inner {
            max-width: 1360px;
            margin: 0 auto;
            grid-template-columns: 1fr auto;
            align-items: center;
        }

        .topbar-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .eyebrow {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .topbar h1,
        .panel-title {
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .topbar h1 { font-size: 1.1rem; }

        .topbar-actions,
        .header-actions,
        .identity-badges,
        .tag-list,
        .row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .btn-minimal {
            border: 1px solid var(--border-strong);
            background: #fff;
            color: var(--text);
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-minimal:hover {
            color: var(--text);
            border-color: #aebccc;
            background: #fbfcfd;
        }

        .btn-accent {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn-accent:hover {
            color: #fff;
            background: #183d5f;
            border-color: #183d5f;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
        }

        .panel-muted {
            background: var(--panel-muted);
        }

        .header-row {
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
            align-items: stretch;
            margin-bottom: 16px;
        }

        .student-identity {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }

        .avatar {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            overflow: hidden;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-name {
            margin: 0 0 6px;
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .student-subtitle {
            color: var(--text-muted);
            margin: 0 0 12px;
            font-size: 0.96rem;
        }

        .identity-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-muted);
            font-size: 0.84rem;
            font-weight: 600;
        }

        .meta-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 18px;
        }

        .meta-item {
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }

        .meta-label {
            color: var(--text-muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .meta-value {
            font-size: 0.96rem;
            font-weight: 700;
        }

        .status-block {
            display: flex;
            flex-direction: column;
            gap: 16px;
            justify-content: space-between;
            height: 100%;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .status-good { background: var(--good-soft); color: var(--good); }
        .status-warn { background: var(--warn-soft); color: var(--warn); }
        .status-bad { background: var(--bad-soft); color: var(--bad); }

        .status-copy {
            color: var(--text-muted);
            margin: 0;
            font-size: 0.94rem;
        }

        .summary-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .metric-card {
            padding: 18px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--border);
        }

        .metric-label {
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 4px;
        }

        .metric-note {
            color: var(--text-muted);
            font-size: 0.84rem;
        }

        .main-grid {
            grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.9fr);
            align-items: start;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .panel-title {
            font-size: 1rem;
        }

        .panel-subtitle {
            color: var(--text-muted);
            font-size: 0.86rem;
            margin: 4px 0 0;
        }

        .table-shell {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8fafc;
            color: var(--text-muted);
            text-align: left;
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: 14px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.92rem;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .topic-name,
        .row-title {
            font-weight: 700;
        }

        .muted {
            color: var(--text-muted);
        }

        .score-chip,
        .mode-chip,
        .status-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .score-good { background: var(--good-soft); color: var(--good); }
        .score-mid { background: var(--accent-soft); color: var(--accent); }
        .score-low { background: var(--bad-soft); color: var(--bad); }
        .mode-chip { background: #f1f5f9; color: #445566; }
        .status-chip { background: #f3f4f6; color: #4b5563; }
        .status-mastered { background: var(--good-soft); color: var(--good); }
        .status-progress { background: var(--warn-soft); color: var(--warn); }
        .status-idle { background: #f3f4f6; color: #677484; }

        .progress-track {
            width: 100%;
            max-width: 120px;
            height: 8px;
            border-radius: 999px;
            background: #e8eef4;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: #587ea3;
            border-radius: inherit;
        }

        .compact-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mini-stat {
            padding: 16px;
            border-radius: 12px;
            background: var(--panel-muted);
            border: 1px solid var(--border);
        }

        .mini-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }

        .mini-stat-label {
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .divider {
            border-top: 1px solid var(--border);
            margin: 18px 0;
        }

        .area-list {
            gap: 10px;
        }

        .area-item,
        .assignment-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            background: #fff;
        }

        .area-item-head,
        .assignment-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 6px;
        }

        .assignment-item {
            background: var(--panel-muted);
        }

        .empty-state {
            padding: 16px;
            border: 1px dashed var(--border-strong);
            border-radius: 12px;
            color: var(--text-muted);
            font-size: 0.9rem;
            background: #fbfcfd;
        }

        @media print {
            .topbar,
            .header-actions {
                display: none !important;
            }

            .page-shell {
                max-width: none;
                padding: 0;
            }

            .panel,
            .metric-card {
                box-shadow: none;
                break-inside: avoid;
            }
        }

        @media (max-width: 1199px) {
            .summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .main-grid,
            .header-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 767px) {
            .page-shell { padding: 16px; }
            .topbar { margin: 0 -16px 16px; padding: 14px 16px; }
            .topbar-inner,
            .student-identity { grid-template-columns: 1fr; }
            .meta-grid,
            .summary-grid,
            .compact-grid { grid-template-columns: 1fr 1fr; }
            .student-identity { display: block; }
            .avatar { margin-bottom: 14px; }
        }

        @media (max-width: 575px) {
            .meta-grid,
            .summary-grid,
            .compact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-inner">
            <div class="topbar-title">
                <span class="eyebrow">Tutor Workspace</span>
                <h1>Student Overview</h1>
            </div>
            <div class="topbar-actions">
                <a href="{{ route('tutor.dashboard') }}" class="btn-minimal">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <button onclick="window.print()" class="btn-minimal">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </div>

    <div class="page-shell">
        <div class="header-row">
            <section class="panel">
                <div class="student-identity">
                    <div class="avatar">
                        @if($student->profile_picture_url)
                            <img src="{{ \App\Models\User::getProfileImageUrlFor($student) }}" alt="{{ $student->name }}">
                        @else
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="w-100">
                        <h2 class="student-name">{{ $student->name }}</h2>
                        <p class="student-subtitle">
                            {{ $student->email }}
                            @if($student->university)
                                • {{ $student->university }}
                            @endif
                        </p>
                        <div class="identity-badges">
                            <span class="identity-badge">ID #{{ $student->id }}</span>
                            @if($student->number)
                                <span class="identity-badge">{{ $student->number }}</span>
                            @endif
                            <span class="identity-badge">{{ count($timeline) }} tracked topics</span>
                        </div>

                        <div class="meta-grid">
                            <div class="meta-item">
                                <div class="meta-label">Last Active</div>
                                <div class="meta-value">{{ $lastActive ? \Carbon\Carbon::parse($lastActive)->diffForHumans() : 'No activity recorded' }}</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Member Since</div>
                                <div class="meta-value">{{ $memberSince ? \Carbon\Carbon::parse($memberSince)->format('M Y') : 'Unknown' }}</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Most Active Day</div>
                                <div class="meta-value">{{ $studyPatterns['most_active_day'] ?? 'N/A' }}</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Average Session</div>
                                <div class="meta-value">{{ round($realTimeStats['average_session_time'] ?? 0, 1) }} min</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="panel panel-muted">
                <div class="status-block">
                    <div>
                        <span class="status-pill {{ $disciplineClass }}">{{ $disciplineLabel }}</span>
                        <h3 class="panel-title mt-3">Accountability Status</h3>
                        <p class="status-copy">{{ $discipline['reason'] }}</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ $discipline['whatsapp_link'] }}" target="_blank" class="btn-minimal btn-accent">
                            <i class="fab fa-whatsapp me-2"></i>Message Student
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <section class="summary-grid">
            <div class="metric-card">
                <div class="metric-label">Accuracy</div>
                <div class="metric-value">{{ round($overview['accuracy_rate'] ?? 0, 1) }}%</div>
                <div class="metric-note">{{ $overview['total_correct_answers'] ?? 0 }} correct of {{ $overview['total_questions_attempted'] ?? 0 }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Average Quiz Score</div>
                <div class="metric-value">{{ round($overview['average_score'] ?? 0, 1) }}%</div>
                <div class="metric-note">Best score {{ round($overview['best_score'] ?? 0, 1) }}%</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Quiz Volume</div>
                <div class="metric-value">{{ $overview['total_quizzes'] ?? 0 }}</div>
                <div class="metric-note">{{ $overview['recent_activity_count'] ?? 0 }} quizzes in last 30 days</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Planner Adherence</div>
                <div class="metric-value">{{ round($planner['completion_rate'] ?? 0, 1) }}%</div>
                <div class="metric-note">{{ $completedTasks }} completed of {{ $planner['recent_outcomes']->count() }} recent tasks</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Study Time</div>
                <div class="metric-value">{{ round($realTimeStats['total_study_time'] ?? 0) }} min</div>
                <div class="metric-note">Across recorded quiz sessions</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Performance Trend</div>
                <div class="metric-value">{{ $trendLabel }}</div>
                <div class="metric-note">{{ $improvementRate >= 0 ? '+' : '' }}{{ round($improvementRate, 1) }} pts vs earlier attempts</div>
            </div>
        </section>

        <div class="main-grid">
            <div>
                <section class="panel mb-3">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Topic Progress</h3>
                            <p class="panel-subtitle">Topic-level quiz performance, video completion, and most recent activity.</p>
                        </div>
                    </div>
                    <div class="table-shell">
                        <table>
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th>Mode</th>
                                    <th>Quiz</th>
                                    <th>Video</th>
                                    <th>Status</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timeline as $item)
                                    @php
                                        $topicStatus = 'Not Started';
                                        $topicStatusClass = 'status-idle';
                                        if (($item['quiz_score'] ?? null) !== null && ($item['quiz_score'] ?? 0) >= 80 && ($item['video_percentage'] ?? 0) >= 90) {
                                            $topicStatus = 'Mastered';
                                            $topicStatusClass = 'status-mastered';
                                        } elseif (($item['quiz_score'] ?? null) !== null || ($item['video_percentage'] ?? 0) > 0) {
                                            $topicStatus = 'In Progress';
                                            $topicStatusClass = 'status-progress';
                                        }
                                        $quizClass = ($item['quiz_score'] ?? 0) >= 80 ? 'score-good' : (($item['quiz_score'] ?? 0) >= 60 ? 'score-mid' : 'score-low');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="topic-name">{{ $item['name'] }}</div>
                                        </td>
                                        <td>
                                            <span class="mode-chip">{{ strtoupper($item['quiz_mode'] ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            @if($item['quiz_score'] !== null)
                                                <span class="score-chip {{ $quizClass }}">{{ round($item['quiz_score'], 1) }}%</span>
                                            @else
                                                <span class="muted">Not attempted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress-track">
                                                    <div class="progress-bar" style="width: {{ max(0, min(100, round($item['video_percentage'] ?? 0))) }}%"></div>
                                                </div>
                                                <span class="muted">{{ round($item['video_percentage'] ?? 0) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-chip {{ $topicStatusClass }}">{{ $topicStatus }}</span>
                                        </td>
                                        <td class="muted">
                                            {{ !empty($item['last_activity']) ? \Carbon\Carbon::parse($item['last_activity'])->diffForHumans() : 'No recent activity' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="muted">No topic activity recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Recent Quiz Sessions</h3>
                            <p class="panel-subtitle">Latest scored sessions with timing and question count.</p>
                        </div>
                    </div>
                    @if(count($recentQuizzes) > 0)
                        <div class="table-shell">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Topic</th>
                                        <th>Score</th>
                                        <th>Questions</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentQuizzes as $quiz)
                                        @php
                                            $quizClass = ($quiz['score'] ?? 0) >= 80 ? 'score-good' : (($quiz['score'] ?? 0) >= 60 ? 'score-mid' : 'score-low');
                                        @endphp
                                        <tr>
                                            <td class="muted">{{ \Carbon\Carbon::parse($quiz['date'])->format('d M Y') }}</td>
                                            <td>
                                                <div class="row-title">{{ $quiz['topic'] }}</div>
                                            </td>
                                            <td><span class="score-chip {{ $quizClass }}">{{ round($quiz['score'] ?? 0, 1) }}%</span></td>
                                            <td>{{ $quiz['correct_answers'] ?? 0 }} / {{ $quiz['questions_attempted'] ?? 0 }}</td>
                                            <td class="muted">{{ round(($quiz['duration'] ?? 0) / 60, 1) }} min</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">No quiz sessions have been recorded for this student yet.</div>
                    @endif
                </section>
            </div>

            <div>
                <section class="panel mb-3">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Study Summary</h3>
                            <p class="panel-subtitle">A compact view of workload, consistency, and momentum.</p>
                        </div>
                    </div>
                    <div class="compact-grid">
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $student->study_streak_days ?? 0 }} days</div>
                            <div class="mini-stat-label">Current study streak</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $student->xp ?? 0 }}</div>
                            <div class="mini-stat-label">Total XP</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ collect($timeline)->where('video_percentage', '>', 0)->count() }}</div>
                            <div class="mini-stat-label">Topics with video progress</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $overview['total_questions_attempted'] ?? 0 }}</div>
                            <div class="mini-stat-label">Questions attempted</div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="area-list">
                        <div>
                            <div class="meta-label">Strength Areas</div>
                            @if(count($strengthAreas) > 0)
                                @foreach($strengthAreas as $area)
                                    <div class="area-item">
                                        <div class="area-item-head">
                                            <div class="row-title">{{ $area['topic'] }}</div>
                                            <span class="score-chip score-good">{{ round($area['average_score'], 1) }}%</span>
                                        </div>
                                        <div class="muted">Strong recent average in this topic.</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">Not enough quiz data to identify strengths yet.</div>
                            @endif
                        </div>

                        <div>
                            <div class="meta-label">Weakness Areas</div>
                            @if(count($weaknessAreas) > 0)
                                @foreach($weaknessAreas as $area)
                                    <div class="area-item">
                                        <div class="area-item-head">
                                            <div class="row-title">{{ $area['topic'] }}</div>
                                            <span class="score-chip score-low">{{ round($area['average_score'], 1) }}%</span>
                                        </div>
                                        <div class="muted">Topic may need revision or follow-up support.</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">Not enough repeated attempts to identify weak areas yet.</div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Assignments</h3>
                            <p class="panel-subtitle">Current assignment load and most recent items.</p>
                        </div>
                    </div>

                    <div class="compact-grid mb-3">
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $assignments['active_count'] ?? 0 }}</div>
                            <div class="mini-stat-label">Active</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $assignments['completed_count'] ?? 0 }}</div>
                            <div class="mini-stat-label">Completed</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ $assignments['overdue_count'] ?? 0 }}</div>
                            <div class="mini-stat-label">Overdue</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-value">{{ round($assignments['average_score'] ?? 0, 1) }}%</div>
                            <div class="mini-stat-label">Average score</div>
                        </div>
                    </div>

                    <div class="area-list">
                        @forelse(array_slice($assignments['all_assignments'] ?? [], 0, 5) as $assignment)
                            @php
                                $assignmentStatusClass = $assignment['is_overdue'] ? 'status-bad' : ($assignment['is_completed'] ? 'status-good' : 'status-warn');
                                $assignmentStatusLabel = $assignment['is_overdue'] ? 'Overdue' : ucfirst($assignment['status']);
                            @endphp
                            <div class="assignment-item">
                                <div class="assignment-head">
                                    <div>
                                        <div class="row-title">{{ $assignment['title'] }}</div>
                                        <div class="muted">{{ $assignment['topic'] }} • {{ $assignment['course'] }}</div>
                                    </div>
                                    <span class="status-pill {{ $assignmentStatusClass }}">{{ $assignmentStatusLabel }}</span>
                                </div>
                                <div class="table-meta">
                                    <div class="muted">Deadline: {{ $assignment['deadline'] ? \Carbon\Carbon::parse($assignment['deadline'])->format('d M Y, H:i') : 'No deadline' }}</div>
                                    <div class="muted">Score: {{ $assignment['score_percentage'] !== null ? round($assignment['score_percentage'], 1) . '%' : 'Not graded' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">No assignments found for this student.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
