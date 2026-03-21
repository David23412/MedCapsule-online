<?php

namespace App\Services;

use App\Models\User;

class NavigationViewDataService
{
    public function getSharedData(?User $user): array
    {
        if (!$user) {
            return [
                'navigationUser' => null,
                'navigationProfileImageUrl' => null,
                'navigationUnreadCount' => 0,
            ];
        }

        return [
            'navigationUser' => $user,
            'navigationProfileImageUrl' => $user->getProfileImageUrl(),
            'navigationUnreadCount' => min(UniversalNotificationService::getUnreadCount($user), 5),
        ];
    }
}
