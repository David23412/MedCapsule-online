<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $student->name }} - Accountability Report - MedCapsule</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-700: #334155;
            --slate-900: #0f172a;
            --radius: 16px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #fbfcff;
            font-family: 'Outfit', sans-serif;
            color: var(--slate-900);
            padding-bottom: 50px;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--slate-200);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container { max-width: 1200px; }

        .discipline-banner {
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }

        .discipline-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            pointer-events: none;
        }

        .discipline-banner.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; }
        .discipline-banner.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; }
        .discipline-banner.bg-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; }

        .banner-content { color: white; }
        .banner-title { font-weight: 700; font-size: 1.2rem; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .banner-desc { opacity: 0.9; font-size: 0.95rem; }

        .whatsapp-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.4);
        }

        .whatsapp-btn:hover {
            transform: scale(1.05);
            background: #128c7e;
            color: white;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }

        .profile-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            border: 1px solid var(--slate-100);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .avatar-lg {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 4px solid var(--slate-100);
            object-fit: cover;
        }

        .placeholder-avatar {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .stat-card {
            background: var(--slate-50);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--slate-200);
            transition: all 0.3s ease;
        }

        .stat-card:hover { background: white; border-color: var(--primary); transform: translateY(-3px); }
        .stat-icon { font-size: 1.5rem; color: var(--primary); margin-bottom: 12px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; display: block; }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--slate-700); font-weight: 600; }

        .matrix-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            border: 1px solid var(--slate-100);
            box-shadow: var(--shadow);
        }

        .section-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--slate-900);
        }

        .table-custom { margin: 0; }
        .table-custom thead th {
            background: var(--slate-50);
            border-bottom: 2px solid var(--slate-200);
            color: var(--slate-700);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 15px;
        }

        .table-custom tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.95rem;
        }

        .score-pill {
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .score-pill.excellent { background: #dcfce7; color: #166534; }
        .score-pill.good { background: #eff6ff; color: #1e40af; }
        .score-pill.poor { background: #fef2f2; color: #991b1b; }

        .progress-compact {
            height: 8px;
            background: var(--slate-100);
            border-radius: 10px;
            overflow: hidden;
            width: 100px;
        }

        .progress-compact-bar { height: 100%; border-radius: 10px; }

        .planner-stat {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--slate-50);
            border-radius: var(--radius);
            margin-bottom: 10px;
        }

        .planner-icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            background: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .nudge-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1001;
        }

        @media print {
            .glass-nav, .nudge-float, .whatsapp-btn, .back-btn { display: none !important; }
            body { background: white; padding: 0; }
            .profile-card, .matrix-container { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>

<nav class="glass-nav mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('tutor.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill back-btn">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
            <h5 class="mb-0 fw-bold">Student Accountability Report</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Quick Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Assign Task</a></li>
                    <li><a class="dropdown-item" href="#">Lock Topic</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#">Reset Progress</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Discipline Banner -->
    <div class="discipline-banner bg-{{ $discipline['color'] }} fade-in">
        <div class="banner-content">
            <div class="banner-title">
                @if($discipline['status'] == 'active')
                    <i class="fas fa-check-circle"></i> DISCIPLINE STATUS: EXCELLENT
                @elseif($discipline['status'] == 'at_risk')
                    <i class="fas fa-exclamation-triangle"></i> DISCIPLINE STATUS: ATTENTION REQUIRED
                @else
                    <i class="fas fa-times-circle"></i> DISCIPLINE STATUS: INACTIVE / STALLED
                @endif
            </div>
            <div class="banner-desc">{{ $discipline['reason'] }}</div>
        </div>
        <a href="{{ $discipline['whatsapp_link'] }}" target="_blank" class="whatsapp-btn">
            <i class="fab fa-whatsapp"></i> MESSAGE STUDENT
        </a>
    </div>

    <div class="row">
        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="profile-card text-center">
                <div class="mb-4 d-flex justify-content-center">
                    @if($student->profile_picture_url)
                        <img src="{{ \App\Models\User::getProfileImageUrlFor($student) }}" class="avatar-lg">
                    @else
                        <div class="placeholder-avatar">{{ substr($student->name, 0, 1) }}</div>
                    @endif
                </div>
                <h3 class="fw-bold mb-1">{{ $student->name }}</h3>
                <p class="text-muted small mb-3">{{ $student->university }}</p>
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-light text-dark rounded-pill border">{{ $student->number }}</span>
                    <span class="badge bg-light text-dark rounded-pill border">ID: #{{ $student->id }}</span>
                </div>

                <hr class="my-4 opacity-50">

                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card">
                            <i class="fas fa-fire stat-icon" style="color: #f97316"></i>
                            <span class="stat-value">{{ $student->study_streak_days }}</span>
                            <span class="stat-label">Streak</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <i class="fas fa-star stat-icon" style="color: #fbbf24"></i>
                            <span class="stat-value">{{ $student->xp }}</span>
                            <span class="stat-label">Total XP</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="stat-card">
                            <i class="fas fa-bullseye stat-icon"></i>
                            <span class="stat-value">{{ $planner['completion_rate'] }}%</span>
                            <span class="stat-label">Planner Adherence</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics -->
            <div class="matrix-container mb-4">
                <h5 class="section-title"><i class="fas fa-chart-pie"></i> Activity Breakdown</h5>
                <div class="planner-stat">
                    <div class="planner-icon text-success"><i class="fas fa-tasks"></i></div>
                    <div>
                        <div class="fw-bold">{{ $planner['recent_outcomes']->where('is_completed', true)->count() }} / {{ $planner['recent_outcomes']->count() }}</div>
                        <div class="text-muted small">Tasks completed (last 14 days)</div>
                    </div>
                </div>
                <div class="planner-stat">
                    <div class="planner-icon text-primary"><i class="fas fa-video"></i></div>
                    <div>
                        <div class="fw-bold">{{ collect($timeline)->where('video_percentage', '>', 0)->count() }}</div>
                        <div class="text-muted small">Topics Watched</div>
                    </div>
                </div>
                <div class="planner-stat">
                    <div class="planner-icon text-warning"><i class="fas fa-pen-nib"></i></div>
                    <div>
                        <div class="fw-bold">{{ $student->total_questions_attempted }}</div>
                        <div class="text-muted small">Questions Solved</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Data -->
        <div class="col-lg-8">
            <!-- Topic Matrix -->
            <div class="matrix-container mb-4">
                <h5 class="section-title"><i class="fas fa-list-ul"></i> Topic Progress Matrix</h5>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Topic</th>
                                <th>Solve Mode</th>
                                <th>Quiz %</th>
                                <th>Video %</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timeline as $item)
                                <tr>
                                    <td><span class="fw-bold">{{ $item['name'] }}</span></td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill">
                                            {{ strtoupper($item['quiz_mode'] ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item['quiz_score'] !== null)
                                            <span class="score-pill {{ $item['quiz_score'] >= 80 ? 'excellent' : ($item['quiz_score'] >= 60 ? 'good' : 'poor') }}">
                                                {{ round($item['quiz_score'], 1) }}%
                                            </span>
                                        @else
                                            <span class="text-muted small">Not attempted</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress-compact">
                                                <div class="progress-compact-bar bg-success" style="width: {{ $item['video_percentage'] }}%"></div>
                                            </div>
                                            <span class="small fw-bold">{{ round($item['video_percentage']) }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($item['last_activity'])->diffForHumans() }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Performance Trend -->
            @if(count($data['recent_quizzes']) > 0)
            <div class="matrix-container">
                <h5 class="section-title"><i class="fas fa-chart-line"></i> Performance Trend</h5>
                <div style="height: 300px;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="nudge-float">
    <a href="{{ $discipline['whatsapp_link'] }}" target="_blank" class="whatsapp-btn">
        <i class="fab fa-whatsapp"></i> NUDGE STUDENT
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@if(count($data['recent_quizzes']) > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        const quizData = @json(array_reverse(array_slice($data['recent_quizzes'], 0, 10)));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: quizData.map(quiz => new Date(quiz.date).toLocaleDateString(undefined, {month:'short', day:'numeric'})),
                datasets: [{
                    label: 'Score %',
                    data: quizData.map(quiz => quiz.score),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 4,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: '#0f172a',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { borderDash: [5, 5], color: '#e2e8f0' },
                        ticks: { stepSize: 20 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endif

</body>
</html>