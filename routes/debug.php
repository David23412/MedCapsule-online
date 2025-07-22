<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mistake;

Route::get('/debug-skipped', function () {
    $userId = auth()->id();
    
    if (!$userId) {
        return "Please log in first";
    }
    
    $mistakes = Mistake::where('user_id', $userId)
        ->where('mastered', false)
        ->with(['question.topic.course'])
        ->get();
    
    $html = "<h1>Debug Skipped Badges for User ID: {$userId}</h1>";
    $html .= "<p>Found " . $mistakes->count() . " mistakes</p>";
    
    foreach ($mistakes as $mistake) {
        $html .= "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        $html .= "<h3>Question ID: {$mistake->question_id}</h3>";
        $html .= "<p>Skipped: " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")</p>";
        
        if ($mistake->skipped) {
            $html .= "<div style='background: rgba(135, 206, 235, 0.2); border: 2px solid rgba(135, 206, 235, 0.6); padding: 5px; display: inline-block;'>";
            $html .= "<i class='fas fa-forward' style='color: #4a90e2; margin-right: 4px;'></i>";
            $html .= "<span style='color: #2c5aa0; font-weight: 600;'>SKIPPED</span>";
            $html .= "</div>";
            $html .= "<p style='color: green;'>✅ Badge should be visible</p>";
        } else {
            $html .= "<p style='color: red;'>❌ No badge (not skipped)</p>";
        }
        
        $html .= "</div>";
    }
    
    return $html;
});