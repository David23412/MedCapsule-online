<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UserMistakeMastery extends Model
{
    use HasFactory;

    protected static array $tableExistenceCache = [];

    protected $table = 'user_mistake_mastery';

    protected $fillable = [
        'user_id',
        'question_id',
        'course_id',
        'consecutive_correct',
        'graduation_mode',
        'graduated_at',
        'last_attempt_at',
        'last_attempt_result',
        'total_attempts',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'question_id' => 'integer',
        'course_id' => 'integer',
        'consecutive_correct' => 'integer',
        'graduated_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'total_attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public static function hasTable(): bool
    {
        $instance = new static();
        $connection = $instance->getConnectionName();
        $cacheKey = ($connection ?: config('database.default')) . ':' . $instance->getTable();

        if (!array_key_exists($cacheKey, static::$tableExistenceCache)) {
            static::$tableExistenceCache[$cacheKey] = $connection
                ? Schema::connection($connection)->hasTable($instance->getTable())
                : Schema::hasTable($instance->getTable());
        }

        return static::$tableExistenceCache[$cacheKey];
    }

    public static function clearTableExistenceCache(): void
    {
        static::$tableExistenceCache = [];
    }
}
