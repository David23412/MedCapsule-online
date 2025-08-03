<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'title', 'description', 'user_id', 'course_id', 'topic_id'];

    public function getUserProgress($userId)
    {
        return [
            'percentage' => rand(0, 100),
            'completed' => rand(0, 1) === 1,
            'attempts_count' => rand(1, 5),
            'best_score' => rand(60, 100)
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}