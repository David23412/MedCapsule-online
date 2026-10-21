@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>Assignment History</h2><a class="btn btn-outline-secondary" href="{{ route('student.assignments.index') }}">My Assignments</a></div>
    <div class="list-group">
        @forelse($assignments as $assignment)
            <a class="list-group-item list-group-item-action" href="{{ route('student.assignments.show', $assignment) }}"><strong>{{ $assignment->title }}</strong><div class="small text-muted">{{ $assignment->course?->name }} · {{ $assignment->topic?->name }} · Score: {{ round($assignment->score_percentage, 1) }}%</div></a>
        @empty<div class="list-group-item text-muted">No assignment attempts yet.</div>@endforelse
    </div>
    <div class="mt-3">{{ $assignments->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
