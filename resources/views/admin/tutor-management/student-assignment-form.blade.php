@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h2>Assign Students{{ isset($tutor) ? ' to ' . $tutor->name : '' }}</h2><a class="btn btn-outline-secondary" href="{{ route('admin.tutors.index') }}">Tutor Management</a></div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <form class="card" method="POST" action="{{ isset($tutor) ? route('admin.tutors.process-assignment', $tutor) : route('admin.tutors.bulk-process') }}">
        @csrf
        <div class="card-body">
            @unless(isset($tutor))
                <div class="mb-3"><label class="form-label" for="assignment-tutor">Tutor</label><select id="assignment-tutor" name="tutor_id" class="form-select" required><option value="">Choose a tutor</option>@foreach($tutors as $option)<option value="{{ $option->id }}">{{ $option->name }} · {{ $option->email }}</option>@endforeach</select></div>
            @endunless
            <p class="text-muted">Select the students to assign. Existing assignments are preserved.</p>
            <div class="list-group mb-3">
                @forelse($availableStudents ?? $students as $student)
                    <label class="list-group-item"><input class="form-check-input me-2" type="checkbox" name="student_ids[]" value="{{ $student->id }}">{{ $student->name }} <small class="text-muted">{{ $student->email }}</small></label>
                @empty<div class="list-group-item text-muted">No students available.</div>@endforelse
            </div>
            <button class="btn btn-primary" @disabled(($availableStudents ?? $students)->isEmpty())>Assign Selected Students</button>
        </div>
    </form>
</div>
@endsection
