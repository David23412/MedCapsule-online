@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>{{ $assignment->title }}</h2><a class="btn btn-outline-secondary" href="{{ route('assignments.index') }}">Assignments</a></div>
    <div class="card mb-3"><div class="card-body">
        <p>{{ $assignment->course?->name }} · {{ $assignment->topic?->name }}</p>
        <p>Deadline: {{ $assignment->deadline->format('M j, Y H:i') }} UTC</p>
        <p>Time limit: {{ (float) $assignment->duration_minutes > 0 ? $assignment->duration_minutes . ' minutes' : 'No time limit' }}</p>
        <p>Questions: {{ $assignment->isSourceSpecific() ? $assignment->assigned_source : 'Complete topic' }}</p>
        <a class="btn btn-outline-primary" href="{{ route('assignments.edit', $assignment) }}">Edit Assignment</a>
    </div></div>
    <h3 class="h5">Assigned Students</h3>
    <div class="list-group">
        @forelse($assignment->assignedUsers as $student)
            <div class="list-group-item d-flex justify-content-between"><span>{{ $student->name }} <small class="text-muted">{{ $student->email }}</small></span><span class="small text-muted">Deadline: {{ $assignment->getDeadlineForUser($student->id)->format('M j, Y H:i') }} UTC</span></div>
        @empty<div class="list-group-item text-muted">No students assigned.</div>@endforelse
    </div>
</div>
@endsection
