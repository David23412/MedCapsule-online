@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Tutor Dashboard</h1>
            
            @if(auth()->user()->isAdmin())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You're viewing this as an administrator. You can see all students or filter by tutor.
            </div>
            @endif
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <h2 class="mb-0">{{ $analytics['total_students'] }}</h2>
                    <small class="text-muted">{{ $analytics['active_today'] }} active today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Course Completion</h5>
                    <h2 class="mb-0">{{ number_format($analytics['course_completion']['completion_rate'], 1) }}%</h2>
                    <small class="text-muted">{{ $analytics['course_completion']['completed'] }}/{{ $analytics['course_completion']['total'] }} courses completed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Quiz Performance</h5>
                    <h2 class="mb-0">{{ number_format($analytics['quiz_performance']['average_score'], 1) }}%</h2>
                    <small class="text-muted">{{ $analytics['quiz_performance']['total_attempts'] }} total attempts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Student Engagement</h5>
                    <h2 class="mb-0">{{ number_format($analytics['engagement_metrics']['engagement_rate'], 1) }}%</h2>
                    <small class="text-muted">{{ $analytics['engagement_metrics']['active_last_week'] }} active last week</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Student List -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Students</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Progress</th>
                                    <th>Quiz Score</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($student->profile_picture_url)
                                                <img src="{{ secure_url($student->profile_picture_url) }}" 
                                                     class="rounded-circle me-2" 
                                                     width="32" 
                                                     height="32"
                                                     alt="{{ $student->name }}">
                                            @else
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px;">
                                                    {{ substr($student->name, 0, 1) }}
                                                </div>
                                            @endif
                                            {{ $student->name }}
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $progress = $student->courses->avg('pivot.completion_percentage');
                                        @endphp
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" 
                                                 role="progressbar" 
                                                 style="width: {{ $progress }}%"
                                                 aria-valuenow="{{ $progress }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ number_format($progress, 1) }}%</small>
                                    </td>
                                    <td>
                                        @php
                                            $avgScore = $student->quizAttempts->avg('score') ?? 0;
                                        @endphp
                                        <span class="badge bg-{{ $avgScore >= 70 ? 'success' : ($avgScore >= 50 ? 'warning' : 'danger') }}">
                                            {{ number_format($avgScore, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span title="{{ $student->last_active_at }}">
                                            {{ $student->last_active_at ? $student->last_active_at->diffForHumans() : 'Never' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('tutor.student.progress', $student->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            View Progress
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentActivity as $activity)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $activity['user'] }}</h6>
                                <small class="text-muted">{{ Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1">{{ $activity['description'] }}</p>
                        </div>
                        @empty
                        <div class="list-group-item text-center text-muted">
                            No recent activity
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize any necessary JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add any required JavaScript here
    });
</script>
@endpush

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 10px;
        transition: transform 0.2s;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }

    .progress {
        background-color: #e9ecef;
        border-radius: 3px;
    }

    .table th {
        border-top: none;
        font-weight: 600;
    }

    .list-group-item {
        border-left: none;
        border-right: none;
    }

    .list-group-item:first-child {
        border-top: none;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }
</style>
@endpush 