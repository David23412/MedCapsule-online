@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Topic Management
                    </h4>
                    <span class="badge bg-info">{{ Auth::user()->isAdmin() ? 'Admin' : 'Tutor' }}</span>
                </div>

                <div class="card-body">
                    @if($courses->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No courses available for topic management.
                        </div>
                    @else
                        <div class="row">
                            @foreach($courses as $course)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-book me-2 text-primary"></i>
                                                {{ $course->name }}
                                            </h5>
                                            <p class="card-text text-muted">
                                                <small>
                                                    <i class="fas fa-list me-1"></i>
                                                    {{ $course->topics->count() }} topics
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                Manage topic access and locking for this course.
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <a href="{{ route('topic-locking.course', $course) }}" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-cog me-1"></i>
                                                Manage Topics
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection