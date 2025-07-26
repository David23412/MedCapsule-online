@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Random Quiz Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="fas fa-random text-primary me-2"></i>Random Quiz Mode
                </h4>
                <span class="badge bg-primary">New!</span>
            </div>
            
            <p class="text-muted">Challenge yourself with questions from multiple courses!</p>
            
            <form action="{{ route('quiz.random.show') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Select Courses:</label>
                    <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;">
                        @php
                            // Get all free courses
                            $freeCourses = \App\Models\Course::where(function($query) {
                                $query->where('price', 0)
                                      ->orWhereNull('price');
                            })->get();
                            
                            // Get user's enrolled courses
                            $enrolledCourses = auth()->user()->courses;
                            
                            // Combine free courses and enrolled courses, avoiding duplicates
                            $allAvailableCourses = $freeCourses->merge($enrolledCourses)->unique('id');
                        @endphp
                        
                        @foreach($allAvailableCourses as $course)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                    name="selected_courses[]" 
                                    value="{{ $course->id }}" 
                                    id="course{{ $course->id }}"
                                    @if($freeCourses->contains('id', $course->id) || $enrolledCourses->contains('id', $course->id)) checked @endif>
                                <label class="form-check-label" for="course{{ $course->id }}">
                                    {{ $course->name }}
                                    @if($freeCourses->contains('id', $course->id))
                                        <span class="badge bg-success ms-1">Free</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="questionLimit" class="form-label">Number of Questions:</label>
                    <select class="form-select" id="questionLimit" name="question_limit" required>
                        <option value="">Select questions</option>
                        <option value="5">5 Questions</option>
                        <option value="10">10 Questions</option>
                        <option value="15">15 Questions</option>
                        <option value="20">20 Questions</option>
                        <option value="30">30 Questions</option>
                        <option value="50">50 Questions</option>
                        <option value="100">100 Questions</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="timeLimit" class="form-label">Time Limit:</label>
                    <select class="form-select" id="timeLimit" name="duration_minutes" required>
                        <option value="">Select time</option>
                        <option value="0.167">10 Seconds (Test)</option>
                        <option value="5">5 Minutes</option>
                        <option value="10">10 Minutes</option>
                        <option value="15">15 Minutes</option>
                        <option value="20">20 Minutes</option>
                        <option value="30">30 Minutes</option>
                        <option value="45">45 Minutes</option>
                        <option value="60">60 Minutes</option>
                        <option value="90">90 Minutes</option>
                        <option value="120">2 Hours</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i>Start Random Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Topics Content -->
    <div class="card">
        <div class="card-header bg-white">
            <h4 class="mb-0">{{ $course->name }} - Topics</h4>
        </div>
        // ... rest of existing topics view code ...
    </div>
</div>

@push('scripts')
<script>
    // Ensure at least one course is selected
    document.querySelector('form').addEventListener('submit', function(e) {
        const selectedCourses = document.querySelectorAll('input[name="selected_courses[]"]:checked');
        if (selectedCourses.length === 0) {
            e.preventDefault();
            alert('Please select at least one course for the random quiz.');
        }
    });
</script>
@endpush
@endsection 