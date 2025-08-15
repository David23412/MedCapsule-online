<?php

/**
 * SPLIT-BRAIN FIX: User model enrollment accessor
 * Add this to app/Models/User.php to replace is_enrolled boolean with computed accessor
 */

// Add this method to User model
public function getIsEnrolledAttribute(): bool
{
    // SSOT: enrolled_at timestamp determines enrollment status
    return !is_null($this->pivot->enrolled_at ?? null);
}

// Update courses relationship to use SSOT
public function courses()
{
    return $this->belongsToMany(Course::class, 'course_user')
                ->withPivot('enrolled_at') // Remove 'is_enrolled' from pivot
                ->whereNotNull('course_user.enrolled_at') // Use timestamp as filter
                ->withTimestamps();
}

// Add method to check enrollment status for specific course
public function isEnrolledInCourse($courseId): bool
{
    return DB::table('course_user')
        ->where('user_id', $this->id)
        ->where('course_id', $courseId)
        ->whereNotNull('enrolled_at') // SSOT: timestamp determines enrollment
        ->exists();
}

// Update enrollment method to use SSOT
public function enrollInCourse($courseId)
{
    $this->courses()->syncWithoutDetaching([$courseId => [
        'enrolled_at' => now() // Only set timestamp, no boolean
    ]]);
}

// Update unenrollment method to use SSOT
public function unenrollFromCourse($courseId)
{
    DB::table('course_user')
        ->where('user_id', $this->id)
        ->where('course_id', $courseId)
        ->update(['enrolled_at' => null]); // Set timestamp to null instead of deleting
}

// Add method to get enrollment date
public function getEnrollmentDate($courseId): ?Carbon
{
    $enrollment = DB::table('course_user')
        ->where('user_id', $this->id)
        ->where('course_id', $courseId)
        ->first();
        
    return $enrollment && $enrollment->enrolled_at 
        ? Carbon::parse($enrollment->enrolled_at) 
        : null;
}

// Add scope for enrolled users
public function scopeEnrolledInCourse($query, $courseId)
{
    return $query->whereHas('courses', function($q) use ($courseId) {
        $q->where('course_id', $courseId)
          ->whereNotNull('course_user.enrolled_at');
    });
}