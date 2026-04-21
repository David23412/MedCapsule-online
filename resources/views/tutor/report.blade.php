<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Accountability Report - {{ $student->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --warning: #ca8a04;
            --danger: #dc2626;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; margin: 0 !important; }
            .report-card { border: none !important; box-shadow: none !important; }
            .topic-row:nth-child(even) { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; }
        }

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background-color: #fff;
            padding: 40px;
            line-height: 1.5;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .report-meta {
            text-align: right;
            font-size: 14px;
            color: var(--text-muted);
        }

        .student-profile {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .profile-info h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
        }

        .profile-info p {
            margin: 0;
            color: var(--text-muted);
        }

        .status-banner {
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .status-banner.active { background-color: #dcfce7; color: #166534; }
        .status-banner.at_risk { background-color: #fffbeb; color: #92400e; }
        .status-banner.inactive { background-color: #fee2e2; color: #991b1b; }

        .status-icon { font-size: 24px; }
        .status-text h4 { margin: 0; font-size: 16px; }
        .status-text p { margin: 0; font-size: 14px; opacity: 0.9; }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }

        .metric-box {
            padding: 20px;
            background: var(--bg-light);
            border-radius: 12px;
            text-align: center;
        }

        .metric-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }

        .metric-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
            border-left: 4px solid var(--primary);
            padding-left: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            text-align: left;
            padding: 12px;
            background-color: var(--bg-light);
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .score-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .score-high { background: #dcfce7; color: #166534; }
        .score-mid { background: #fffbeb; color: #92400e; }
        .score-low { background: #fee2e2; color: #991b1b; }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .print-btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Report
    </button>

    <div class="report-header">
        <div class="logo">MEDCAPSULE <span style="font-weight: 300;">ACADEMY</span></div>
        <div class="report-meta">
            <div>Confidential Accountability Report</div>
            <div>Generated on {{ $generated_at->format('M d, Y') }}</div>
            <div>Tutor: {{ $tutor->name }}</div>
        </div>
    </div>

    <div class="student-profile">
        <div class="profile-info">
            <h1>{{ $student->name }}</h1>
            <p>{{ $student->university }} | {{ $student->email }}</p>
            <p>Member Since: {{ $student->created_at->format('M Y') }}</p>
        </div>
    </div>

    <div class="status-banner {{ $discipline['status'] }}">
        <div class="status-icon">
            @if($discipline['status'] === 'active')
                <i class="fas fa-check-circle"></i>
            @elseif($discipline['status'] === 'at_risk')
                <i class="fas fa-exclamation-triangle"></i>
            @else
                <i class="fas fa-times-circle"></i>
            @endif
        </div>
        <div class="status-text">
            <h4>Accountability Status: {{ ucfirst(str_replace('_', ' ', $discipline['status'])) }}</h4>
            <p>{{ $discipline['reason'] }}</p>
        </div>
    </div>

    <div class="metrics-grid">
        <div class="metric-box">
            <span class="metric-value">{{ number_format($data['average_score'], 1) }}%</span>
            <span class="metric-label">Average Score</span>
        </div>
        <div class="metric-box">
            <span class="metric-value">{{ $data['total_quizzes'] }}</span>
            <span class="metric-label">Total Quizzes</span>
        </div>
        <div class="metric-box">
            <span class="metric-value">{{ $data['study_streak'] }} Days</span>
            <span class="metric-label">Study Streak</span>
        </div>
        <div class="metric-box">
            <span class="metric-value">{{ $student->xp }}</span>
            <span class="metric-label">Total XP</span>
        </div>
    </div>

    <h2 class="section-title">Topic-by-Topic Performance Matrix</h2>
    <table>
        <thead>
            <tr>
                <th>Topic Name</th>
                <th>Quiz Mode</th>
                <th>Video Progress</th>
                <th>Best Quiz Score</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($timeline as $item)
                <tr>
                    <td style="font-weight: 600;">{{ $item['name'] }}</td>
                    <td><span style="opacity: 0.7;">{{ ucfirst($item['quiz_mode'] ?: 'N/A') }}</span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px;">
                                <div style="width: {{ $item['video_percentage'] }}%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                            </div>
                            <span style="font-size: 11px;">{{ $item['video_percentage'] }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($item['quiz_score'] !== null)
                            <span class="score-pill @if($item['quiz_score'] >= 80) score-high @elseif($item['quiz_score'] >= 65) score-mid @else score-low @endif">
                                {{ number_format($item['quiz_score'], 0) }}%
                            </span>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item['quiz_score'] >= 80 && $item['video_percentage'] >= 90)
                            <span style="color: var(--success); font-size: 11px;"><i class="fas fa-check-double"></i> Mastered</span>
                        @elseif($item['quiz_score'] !== null || $item['video_percentage'] > 0)
                            <span style="color: var(--warning); font-size: 11px;"><i class="fas fa-clock"></i> In Progress</span>
                        @else
                            <span style="color: var(--text-muted); font-size: 11px;">Not Started</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: var(--text-muted); text-align: center;">
        This document serves as an official study report for the MedCapsule Mentorship Program. 
        It is intended for tutoring and accountability purposes only.
    </div>

    <script>
        // Auto-print if param exists
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>
