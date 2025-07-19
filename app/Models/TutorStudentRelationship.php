<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TutorStudentRelationship extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tutor_id',
        'student_id',
        'assigned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the tutor associated with the relationship.
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id')->where('role', 'tutor');
    }

    /**
     * Get the student associated with the relationship.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id')->where('role', 'student');
    }

    /**
     * Scope a query to only include active relationships.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('assigned_at', '<=', Carbon::now());
    }

    /**
     * Check if a tutor has access to a specific student
     *
     * @param int $tutorId
     * @param int $studentId
     * @return bool
     */
    public static function hasAccess(int $tutorId, int $studentId): bool
    {
        return static::where('tutor_id', $tutorId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Get all students for a specific tutor
     *
     * @param int $tutorId
     * @return Builder
     */
    public static function getStudentsForTutor(int $tutorId): Builder
    {
        return static::where('tutor_id', $tutorId)
            ->with('student')
            ->active();
    }

    /**
     * Get all tutors for a specific student
     *
     * @param int $studentId
     * @return Builder
     */
    public static function getTutorsForStudent(int $studentId): Builder
    {
        return static::where('student_id', $studentId)
            ->with('tutor')
            ->active();
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Set assigned_at timestamp if not set
        static::creating(function ($relationship) {
            if (!$relationship->assigned_at) {
                $relationship->assigned_at = Carbon::now();
            }
        });

        // Validate roles before saving
        static::saving(function ($relationship) {
            $tutor = User::find($relationship->tutor_id);
            $student = User::find($relationship->student_id);

            if (!$tutor || $tutor->role !== 'tutor') {
                throw new \InvalidArgumentException('Invalid tutor ID or role');
            }

            if (!$student || $student->role !== 'student') {
                throw new \InvalidArgumentException('Invalid student ID or role');
            }
        });
    }
} 