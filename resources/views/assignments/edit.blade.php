@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>Edit Assignment</h2><a class="btn btn-outline-secondary" href="{{ route('assignments.show', $assignment) }}">Back to Assignment</a></div>
    <form class="card" method="POST" action="{{ route('assignments.update', $assignment) }}" data-json-form data-success-url="{{ route('assignments.show', $assignment) }}">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="mb-3"><label class="form-label" for="assignment-title">Title</label><input class="form-control" id="assignment-title" name="title" maxlength="255" value="{{ $assignment->title }}" required></div>
            <div class="mb-3"><label class="form-label" for="assignment-deadline">Deadline (UTC)</label><input class="form-control" id="assignment-deadline" name="deadline" type="datetime-local" value="{{ $assignment->deadline->format('Y-m-d\TH:i') }}" required></div>
            <div class="mb-3"><label class="form-label" for="assignment-duration">Time limit in minutes</label><input class="form-control" id="assignment-duration" name="duration_minutes" type="number" min="0" max="1440" step="0.01" value="{{ $assignment->duration_minutes }}"></div>
            <h3 class="h5 mt-4">Individual Deadline Extensions</h3>
            @forelse($assignment->assignedUsers as $student)
                <div class="mb-3"><label class="form-label" for="extension-{{ $student->id }}">{{ $student->name }} · {{ $student->email }} (UTC)</label><input class="form-control" id="extension-{{ $student->id }}" name="user_extensions[{{ $student->id }}]" type="datetime-local" value="{{ $student->pivot->extended_deadline ? \Carbon\Carbon::parse($student->pivot->extended_deadline)->format('Y-m-d\TH:i') : '' }}"></div>
            @empty<p class="text-muted">No students assigned.</p>@endforelse
            <p class="small text-muted">Leave an individual extension blank to keep its current deadline.</p>
            <div class="alert alert-danger" data-form-error role="alert" hidden></div>
            <button class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')<script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-json-forms.js') }}" defer></script>@endpush
