@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>{{ $assignment->title }}</h2><a class="btn btn-outline-secondary" href="{{ route('student.assignments.index') }}">My Assignments</a></div>
    <div class="card mb-4"><div class="card-body">
        <p>{{ $assignment->course?->name }} · {{ $assignment->topic?->name }}</p>
        <p>Deadline: {{ $assignment->deadline->format('M j, Y H:i') }} UTC</p>
        <p>Time limit: {{ (float) $assignment->duration_minutes > 0 ? $assignment->duration_minutes . ' minutes' : 'No time limit' }}</p>
        @if($assignment->student_progress['completed'])
            <p>Completed · Score: {{ round($assignment->student_progress['percentage'], 1) }}%</p>
        @elseif($assignment->canUserStart(auth()->user()))
            <form method="POST" action="{{ route('student.assignments.start', $assignment) }}">@csrf<button class="btn btn-primary">Start Assignment</button></form>
        @else<p class="text-muted">{{ $assignment->getLockReasonForUser(auth()->user()) ?? 'This assignment is unavailable.' }}</p>@endif
    </div></div>
    @unless($assignment->student_progress['completed'])
        <form class="card" method="POST" action="{{ route('student.assignments.request-extension', $assignment) }}" data-json-form>
            @csrf
            <div class="card-body"><h3 class="h5">Request a Deadline Extension</h3><p class="small text-muted">Your tutor must approve an extension before your deadline changes.</p>
                <div class="mb-3"><label class="form-label" for="extension-deadline">Requested deadline (UTC)</label><input id="extension-deadline" name="requested_deadline" type="datetime-local" class="form-control" required></div>
                <div class="mb-3"><label class="form-label" for="extension-reason">Reason</label><textarea id="extension-reason" name="reason" class="form-control" maxlength="1000" required></textarea></div>
                <div class="alert alert-danger" data-form-error role="alert" hidden></div><button class="btn btn-outline-primary">Send Request</button>
            </div>
        </form>
    @endunless
</div>
@endsection
@push('scripts')<script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-json-forms.js') }}" defer></script>@endpush
