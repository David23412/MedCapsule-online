<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSchedulerCalibrationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mode',
        'total_reviews',
        'calibration_reviews',
        'confidence_sensitivity',
        'overconfidence_penalty',
        'confidence_reliability',
        'high_confidence_accuracy',
        'low_confidence_accuracy',
        'confidence_alignment',
        'last_calibrated_at',
        'calibration_meta',
    ];

    protected $casts = [
        'total_reviews' => 'integer',
        'calibration_reviews' => 'integer',
        'confidence_sensitivity' => 'float',
        'overconfidence_penalty' => 'float',
        'confidence_reliability' => 'float',
        'high_confidence_accuracy' => 'float',
        'low_confidence_accuracy' => 'float',
        'confidence_alignment' => 'float',
        'last_calibrated_at' => 'datetime',
        'calibration_meta' => 'array',
    ];
}
