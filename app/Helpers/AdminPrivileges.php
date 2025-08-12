<?php

/**
 * 👑 ADMIN PRIVILEGE HELPER FUNCTIONS
 * 
 * These functions help verify and enforce admin superior privileges
 */

if (!function_exists('isAdmin')) {
    /**
     * Check if current user is an admin
     */
    function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }
}

if (!function_exists('isTutorOrAdmin')) {
    /**
     * Check if current user is tutor or admin
     */
    function isTutorOrAdmin(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'tutor']);
    }
}

if (!function_exists('requireAdmin')) {
    /**
     * Require admin privileges or throw exception
     */
    function requireAdmin(): void
    {
        if (!isAdmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Administrator privileges required.');
        }
    }
}

if (!function_exists('requireTutorOrAdmin')) {
    /**
     * Require tutor or admin privileges or throw exception
     */
    function requireTutorOrAdmin(): void
    {
        if (!isTutorOrAdmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Tutor or Administrator privileges required.');
        }
    }
}

if (!function_exists('canDeleteAssignment')) {
    /**
     * Check if user can delete assignments
     * 👑 Admins can delete ANY assignment
     * 🎓 Tutors can delete assignments (with potential restrictions)
     */
    function canDeleteAssignment($assignment = null): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        $user = auth()->user();
        
        // 👑 ADMIN SUPERIOR PRIVILEGES - Can delete ANY assignment
        if ($user->role === 'admin') {
            return true;
        }
        
        // 🎓 TUTOR ACCESS - Can delete assignments
        if ($user->role === 'tutor') {
            return true;
        }
        
        return false;
    }
}