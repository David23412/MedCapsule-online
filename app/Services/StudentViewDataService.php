<?php

namespace App\Services;

use App\Models\User;

class StudentViewDataService
{
    public function buildCurrentUserView(User $user, array $overrides = []): array
    {
        $base = [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'is_admin' => $user->isAdmin(),
            'is_tutor' => $user->isTutor(),
            'is_student' => $user->role === User::ROLE_STUDENT,
            'study_streak_days' => (int) ($user->study_streak_days ?? 0),
            'profile_image_url' => $user->getProfileImageUrl(),
            'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
            'profile_initial' => strtoupper(substr($user->name, 0, 1)),
            'has_profile_picture' => !empty($user->profile_picture_url),
            'xp' => (int) ($user->xp ?? 0),
            'level' => method_exists($user, 'getCurrentLevel')
                ? (int) $user->getCurrentLevel()
                : ((int) floor(((int) ($user->xp ?? 0)) / 1000) + 1),
            'total_correct_answers' => method_exists($user, 'getTotalCorrectAnswers')
                ? (int) $user->getTotalCorrectAnswers()
                : (int) ($user->correct_answers_count ?? 0),
            'free_courses_unlocked' => method_exists($user, 'hasUnlockedFreeCourses')
                ? (bool) $user->hasUnlockedFreeCourses()
                : false,
        ];

        return array_merge($base, $overrides);
    }
}
