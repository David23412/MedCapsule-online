@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>{{ $course->name }} Topics</h2><a class="btn btn-outline-secondary" href="{{ route('topic-locking.index') }}">Topic Management</a></div>
    <p class="text-muted">Locked topics are unavailable to students. Unlock a topic to make it available again.</p>
    <div class="list-group">
        @forelse($topics as $topic)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $topic->name }}</strong><div class="small text-muted">{{ $topic->is_locked ? 'Locked' : 'Unlocked' }}</div></div>
                <form method="POST" action="{{ route($topic->is_locked ? 'topic-locking.unlock' : 'topic-locking.lock', $topic->id) }}" data-json-form data-success-url="{{ route('topic-locking.course', $course) }}">
                    @csrf
                    <div data-form-error role="alert" class="small text-danger" hidden></div>
                    <button class="btn btn-sm btn-outline-{{ $topic->is_locked ? 'primary' : 'secondary' }}">{{ $topic->is_locked ? 'Unlock' : 'Lock' }}</button>
                </form>
            </div>
        @empty<div class="list-group-item text-muted">No topics found.</div>@endforelse
    </div>
</div>
@endsection
@push('scripts')<script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-json-forms.js') }}" defer></script>@endpush
