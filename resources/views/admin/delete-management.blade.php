@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-trash-alt me-2"></i>
                        Delete Management Dashboard
                    </h3>
                    <p class="mb-0 mt-2 text-white-50">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Warning: All deletions are permanent and cannot be undone!
                    </p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h4 class="text-primary">{{ $stats['courses'] }}</h4>
                                    <p class="mb-2">Courses</p>
                                    <a href="{{ route('admin.delete.courses') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-trash me-1"></i>Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h4 class="text-success">{{ $stats['topics'] }}</h4>
                                    <p class="mb-2">Topics</p>
                                    <a href="{{ route('admin.delete.topics') }}" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-trash me-1"></i>Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h4 class="text-warning">{{ $stats['questions'] }}</h4>
                                    <p class="mb-2">Questions</p>
                                    <a href="{{ route('admin.delete.questions') }}" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-trash me-1"></i>Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h4 class="text-info">{{ $stats['users'] }}</h4>
                                    <p class="mb-2">Users</p>
                                    <a href="{{ route('admin.delete.users') }}" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-trash me-1"></i>Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Important Safety Information</h5>
                                <ul class="mb-0">
                                    <li>All deletions are <strong>permanent and irreversible</strong></li>
                                    <li>Deleting a course will remove all its topics, questions, and related data</li>
                                    <li>Deleting a topic will remove all its questions and related data</li>
                                    <li>Deleting a question will remove all user attempts and mistakes</li>
                                    <li>Deleting a user will remove all their progress and data</li>
                                    <li>All deletions are logged for audit purposes</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-chart-bar me-2"></i>System Statistics</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Quiz Attempts
                                            <span class="badge bg-primary rounded-pill">{{ number_format($stats['quiz_attempts']) }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Mistakes Recorded
                                            <span class="badge bg-warning rounded-pill">{{ number_format($stats['mistakes']) }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Quick Actions</h6>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('admin.delete.courses') }}" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-graduation-cap me-1"></i>Manage Courses
                                            </a>
                                            <a href="{{ route('admin.delete.questions') }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-question-circle me-1"></i>Manage Questions
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 