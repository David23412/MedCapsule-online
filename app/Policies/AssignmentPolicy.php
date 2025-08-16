<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assignments.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_TUTOR || $user->role === User::ROLE_STUDENT;
    }

    /**
     * Determine whether the user can view the assignment.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        // Tutors can view their own assignments
        if ($user->role === User::ROLE_TUTOR && $assignment->tutor_id === $user->id) {
            return true;
        }

        // Students can view assignments assigned to them
        if ($user->role === User::ROLE_STUDENT && $assignment->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create assignments.
     */
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_TUTOR;
    }

    /**
     * Determine whether the user can update the assignment.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        // Only tutors can update assignments
        if ($user->role !== User::ROLE_TUTOR) {
            return false;
        }

        // Tutors can only update their own assignments
        return $assignment->tutor_id === $user->id;
    }

    /**
     * Determine whether the user can delete the assignment.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        // Only tutors can delete assignments
        if ($user->role !== User::ROLE_TUTOR) {
            return false;
        }

        // Tutors can only delete their own assignments
        if ($assignment->tutor_id !== $user->id) {
            return false;
        }

        // Cannot delete completed assignments
        if ($assignment->status === Assignment::STATUS_COMPLETED) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can complete the assignment.
     */
    public function complete(User $user, Assignment $assignment): bool
    {
        // Students can complete their own assignments
        if ($user->role === User::ROLE_STUDENT && $assignment->user_id === $user->id) {
            return $assignment->canBeCompleted();
        }

        // Tutors can mark their assignments as completed
        if ($user->role === User::ROLE_TUTOR && $assignment->tutor_id === $user->id) {
            return $assignment->canBeCompleted();
        }

        return false;
    }

    /**
     * Determine whether the user can extend the assignment deadline.
     */
    public function extendDeadline(User $user, Assignment $assignment): bool
    {
        // Only tutors can extend deadlines
        if ($user->role !== User::ROLE_TUTOR) {
            return false;
        }

        // Tutors can only extend deadlines for their own assignments
        if ($assignment->tutor_id !== $user->id) {
            return false;
        }

        // Cannot extend deadline for completed assignments
        if ($assignment->status === Assignment::STATUS_COMPLETED) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view assignment analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->role === User::ROLE_TUTOR;
    }

    /**
     * Determine whether the user can create bulk assignments.
     */
    public function bulkCreate(User $user): bool
    {
        return $user->role === User::ROLE_TUTOR;
    }
}