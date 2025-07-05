<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // TODO: Load user settings from database when user authentication is implemented
        return view('settings');
    }

    /**
     * Update user settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'dark_mode' => 'boolean',
            'daily_reminder' => 'boolean',
            'weekly_reminder' => 'boolean',
            'study_duration' => 'integer|min:15|max:60',
        ]);

        // TODO: Save settings to user preferences in database when user authentication is implemented
        // For now, we'll just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
} 