@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Manage Deletions</h2>
        <a href="{{ route('admin.user-course') }}" class="btn btn-outline-secondary">Manage Access</a>
    </div>
    <p class="text-muted">Choose the records to review. Deleting a record is permanent and removes its associated data.</p>
    <div class="row g-3">
        @foreach(['courses' => 'Courses', 'topics' => 'Topics', 'questions' => 'Questions', 'users' => 'Users'] as $resource => $label)
            <div class="col-sm-6 col-lg-3">
                <a class="card h-100 text-decoration-none text-dark" href="{{ route('admin.delete.' . $resource) }}">
                    <div class="card-body">
                        <h3 class="h5">{{ $label }}</h3>
                        <p class="text-muted mb-0">{{ number_format($stats[$resource]) }} records · Review {{ strtolower($label) }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <p class="text-muted mt-4">{{ number_format($stats['quiz_attempts']) }} quiz attempts · {{ number_format($stats['mistakes']) }} saved mistakes</p>
</div>
@endsection
