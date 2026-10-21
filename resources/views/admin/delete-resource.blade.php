@extends('layouts.app')

@section('content')
@php($singular = \Illuminate\Support\Str::singular($resource))
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Delete {{ ucfirst($resource) }}</h2>
        <a href="{{ route('admin.delete.index') }}" class="btn btn-outline-secondary">Back to Deletions</a>
    </div>
    @foreach(['success', 'error'] as $status)
        @if(session($status))<div class="alert alert-{{ $status === 'error' ? 'danger' : 'success' }}">{{ session($status) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <p id="delete-controls-status" class="small text-muted" role="status">Loading deletion controls…</p>
    <p class="text-muted">Review each record carefully. Deletion is permanent and removes associated study data.</p>

    @if($resource !== 'topics')
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md">
                <label for="delete-search" class="form-label">Search {{ $resource }}</label>
                <input id="delete-search" name="search" value="{{ request('search') }}" maxlength="255" class="form-control">
            </div>
            @if($resource === 'questions')
                <div class="col-md">
                    <label for="delete-course" class="form-label">Course</label>
                    <select id="delete-course" name="course_id" class="form-select">
                        <option value="">All courses</option>
                        @foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md">
                    <label for="delete-topic" class="form-label">Topic</label>
                    <select id="delete-topic" name="topic_id" class="form-select" @disabled($topics->isEmpty())>
                        <option value="">{{ $topics->isEmpty() ? 'Choose a course to filter topics' : 'All topics' }}</option>
                        @foreach($topics as $topic)<option value="{{ $topic->id }}" @selected((string) request('topic_id') === (string) $topic->id)>{{ $topic->name }}</option>@endforeach
                    </select>
                </div>
            @else
                <div class="col-md">
                    <label for="delete-role" class="form-label">Account role</label>
                    <select id="delete-role" name="is_admin" class="form-select">
                        <option value="">All roles</option>
                        <option value="1" @selected(request('is_admin') === '1')>Administrators</option>
                        <option value="0" @selected(request('is_admin') === '0')>Students and tutors</option>
                    </select>
                </div>
            @endif
            <div class="col-md-auto align-self-end">
                <button class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('admin.delete.' . $resource) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    @endif

    @if($resource === 'questions')
        <form id="bulk-question-delete" method="POST" action="{{ route('admin.delete.questions.bulk') }}" class="mb-3" data-delete-form data-label="the selected questions">
            @csrf
            <button class="btn btn-outline-danger" id="delete-selected" disabled>Delete Selected (<span id="delete-selection-count">0</span>)</button>
        </form>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    @if($resource === 'questions')<th><input id="select-all-questions" type="checkbox" aria-label="Select all questions on this page"></th>@endif
                    <th>{{ ucfirst($singular) }}</th><th>Context</th><th>Study Data</th><th>Action</th>
                </tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        @if($resource === 'questions')<td><input type="checkbox" name="question_ids[]" value="{{ $item->id }}" form="bulk-question-delete" class="delete-question-selection" aria-label="Select question {{ $item->id }}"></td>@endif
                        <td class="text-break">
                            <strong>{{ $resource === 'questions' ? \Illuminate\Support\Str::limit(strip_tags($item->question), 250) : $item->name }}</strong>
                            <div class="small text-muted">#{{ $item->id }}@if($resource === 'users') · {{ $item->email }}@endif</div>
                        </td>
                        <td>
                            @if($resource === 'questions'){{ $item->topic?->course?->name }}<div class="small text-muted">{{ $item->topic?->name }}</div>
                            @elseif($resource === 'topics'){{ $item->course?->name }}
                            @else{{ $item->isAdmin() ? 'Admin' : ($item->isTutor() ? 'Tutor' : 'Student') }}@endif
                        </td>
                        <td class="small text-muted">
                            @if($resource === 'questions'){{ $item->mistakes_count }} saved mistakes
                            @elseif($resource === 'topics'){{ $item->questions_count }} questions · {{ $item->quiz_attempts_count }} attempts
                            @else{{ $item->quiz_attempts_count }} attempts · {{ $item->mistakes_count }} saved mistakes · {{ $item->payments_count }} payments
                            @endif
                        </td>
                        <td>
                            @if($resource === 'users' && $item->id === auth()->id())
                                <span class="small text-muted">Your account</span>
                            @else
                                <form method="POST" action="{{ route('admin.delete.' . $singular, $item->id) }}" data-delete-form data-label="{{ $singular }} #{{ $item->id }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" data-delete-trigger disabled>Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $resource === 'questions' ? 5 : 4 }}" class="text-center text-muted py-4">No {{ $resource }} found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->withQueryString()->links('pagination::bootstrap-5') }}</div>
</div>

<div class="modal fade" id="delete-confirmation" tabindex="-1" aria-labelledby="delete-confirmation-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 id="delete-confirmation-title" class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p id="delete-confirmation-description"></p>
            <div class="form-check"><input type="checkbox" id="confirm-permanent-delete" class="form-check-input"><label for="confirm-permanent-delete" class="form-check-label">I understand this permanently removes the record and its associated data.</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="confirm-delete-button" disabled>Delete Permanently</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-delete-tools.js') }}" defer></script>
@endpush
