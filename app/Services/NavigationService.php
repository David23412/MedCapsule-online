<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class NavigationService
{
    /**
     * Get navigation routes based on user role
     */
    public static function getNavigationRoutes()
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $routes = [
            'core' => [
                [
                    'route' => 'courses.index',
                    'icon' => 'fas fa-book',
                    'label' => 'My Courses',
                    'roles' => ['student', 'tutor', 'admin']
                ],
                [
                    'route' => 'review.index',
                    'icon' => 'fas fa-exclamation-circle',
                    'label' => 'My Mistakes',
                    'roles' => ['student', 'tutor', 'admin']
                ]
            ]
        ];

        // Admin-specific routes
        if ($user->role === 'admin') {
            $routes['admin'] = [
                'header' => [
                    'icon' => 'fas fa-crown',
                    'label' => 'Admin Controls',
                    'class' => 'text-warning'
                ],
                'items' => [
                    [
                        'route' => 'assignments.index',
                        'icon' => 'fas fa-clipboard-list',
                        'label' => 'Assignment Management',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'assignments.create',
                        'icon' => 'fas fa-plus',
                        'label' => 'Create Assignment',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'topic-locking.index',
                        'icon' => 'fas fa-lock',
                        'label' => 'Topic Management',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'admin.user-course',
                        'icon' => 'fas fa-users-cog',
                        'label' => 'Manage Access',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'add-topic.form',
                        'icon' => 'fas fa-plus-circle',
                        'label' => 'Add Topic',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'add-question.form',
                        'icon' => 'fas fa-question-circle',
                        'label' => 'Add Question',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'add-course.form',
                        'icon' => 'fas fa-graduation-cap',
                        'label' => 'Add Course',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'admin.payment-history',
                        'icon' => 'fas fa-money-bill',
                        'label' => 'Payments',
                        'roles' => ['admin']
                    ],
                    [
                        'route' => 'admin.user-course',
                        'params' => ['tab' => 'payment-settings'],
                        'icon' => 'fas fa-cog',
                        'label' => 'Payment Settings',
                        'roles' => ['admin']
                    ]
                ]
            ];
        }

        // Tutor-specific routes
        if ($user->isAdmin() || $user->isTutor()) {
            $routes['tutor'] = [
                'header' => [
                    'icon' => 'fas fa-chalkboard-teacher',
                    'label' => 'Tutor Tools',
                    'class' => 'text-info'
                ],
                'items' => [
                    [
                        'route' => 'tutor.dashboard',
                        'icon' => 'fas fa-chalkboard-teacher',
                        'label' => 'Tutor Dashboard',
                        'roles' => ['tutor', 'admin']
                    ],
                    [
                        'route' => 'tutor.my-analytics',
                        'icon' => 'fas fa-chart-bar',
                        'label' => 'My Analytics',
                        'roles' => ['tutor', 'admin']
                    ]
                ]
            ];
        }

        // Profile and logout (always present for authenticated users)
        $routes['profile'] = [
            [
                'route' => 'profile',
                'icon' => 'fas fa-user',
                'label' => 'Profile',
                'roles' => ['student', 'tutor', 'admin']
            ]
        ];

        return $routes;
    }

    /**
     * Check if user has access to a specific route
     */
    public static function hasAccess($roles)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return in_array($user->role, $roles) || 
               ($user->isAdmin() && in_array('admin', $roles)) ||
               ($user->isTutor() && in_array('tutor', $roles));
    }

    /**
     * Get user role badge
     */
    public static function getUserRoleBadge()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($user->role === 'admin') {
            return [
                'text' => '👑 Admin',
                'class' => 'bg-warning'
            ];
        } elseif ($user->role === 'tutor') {
            return [
                'text' => '📚 Tutor',
                'class' => 'bg-info'
            ];
        }

        return null;
    }

    /**
     * Generate route URL with parameters
     */
    public static function getRouteUrl($route, $params = [])
    {
        try {
            if (empty($params)) {
                return route($route);
            }
            return route($route, $params);
        } catch (\Exception $e) {
            return '#';
        }
    }
}