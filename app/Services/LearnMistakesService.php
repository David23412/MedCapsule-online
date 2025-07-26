<?php

namespace App\Services;

use App\Models\User;
use App\Models\Question;
use App\Models\Mistake;
use Illuminate\Support\Collection;

class LearnMistakesService
{
    /**
     * Skip a mistake for a user
     */
    public function skipMistake(User $user, Question $question): bool
    {
        $mistake = Mistake::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
            
        if ($mistake) {
            $mistake->update(['skipped' => true]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove a mistake for a user
     */
    public function removeMistake(User $user, Question $question): bool
    {
        return Mistake::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->delete() > 0;
    }
    
    /**
     * Get skipped mistakes for a user
     */
    public function getSkippedMistakes(User $user): Collection
    {
        return Mistake::where('user_id', $user->id)
            ->where('skipped', true)
            ->with('question')
            ->get();
    }
    
    /**
     * Get study mistakes for a user
     */
    public function getStudyMistakes(User $user): Collection
    {
        return Mistake::where('user_id', $user->id)
            ->where('mastered', false)
            ->where('skipped', false)
            ->with('question')
            ->get();
    }
    
    /**
     * Get questions for mistakes
     */
    public function getQuestionsForMistakes(User $user): Collection
    {
        return Question::whereIn('id', function($query) use ($user) {
            $query->select('question_id')
                ->from('mistakes')
                ->where('user_id', $user->id)
                ->where('mastered', false)
                ->where('skipped', false);
        })->get();
    }
    
    /**
     * Get all mistakes for a user
     */
    public function getAllMistakes(User $user): Collection
    {
        return Mistake::where('user_id', $user->id)
            ->with('question')
            ->get();
    }
    
    /**
     * Check if user has mistakes
     */
    public function hasMistakes(User $user): bool
    {
        return Mistake::where('user_id', $user->id)
            ->where('mastered', false)
            ->exists();
    }
    
    /**
     * Clear all mistakes for a user
     */
    public function clearAllMistakes(User $user): bool
    {
        return Mistake::where('user_id', $user->id)->delete() > 0;
    }
    
    /**
     * Clear mastered mistakes for a user
     */
    public function clearMasteredMistakes(User $user): bool
    {
        return Mistake::where('user_id', $user->id)
            ->where('mastered', true)
            ->delete() > 0;
    }
    
    /**
     * Get study statistics for a user
     */
    public function getStudyStatistics(User $user): array
    {
        $totalMistakes = Mistake::where('user_id', $user->id)->count();
        $masteredMistakes = Mistake::where('user_id', $user->id)
            ->where('mastered', true)
            ->count();
        $skippedMistakes = Mistake::where('user_id', $user->id)
            ->where('skipped', true)
            ->count();
            
        return [
            'total' => $totalMistakes,
            'mastered' => $masteredMistakes,
            'skipped' => $skippedMistakes,
            'remaining' => $totalMistakes - $masteredMistakes - $skippedMistakes
        ];
    }
    
    /**
     * Get questions for review
     */
    public function getQuestionsForReview(User $user, int $limit = 10): Collection
    {
        return Question::whereIn('id', function($query) use ($user) {
            $query->select('question_id')
                ->from('mistakes')
                ->where('user_id', $user->id)
                ->where('mastered', false)
                ->where('skipped', false);
        })->limit($limit)->get();
    }
    
    /**
     * Reset mistake progress
     */
    public function resetMistakeProgress(User $user, Question $question): bool
    {
        $mistake = Mistake::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
            
        if ($mistake) {
            $mistake->update([
                'mastered' => false,
                'skipped' => false,
                'correct_streak' => 0
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get recent mistakes
     */
    public function getRecentMistakes(User $user, int $days = 7): Collection
    {
        return Mistake::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('question')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}