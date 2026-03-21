<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulerCalibrationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'mode',
        'total_reviews',
        'calibration_reviews',
        'observed_recall_rate',
        'mean_prediction_error',
        'recall_bias',
        'stability_scale',
        'difficulty_scale',
        'response_sensitivity',
        'confidence_sensitivity',
        'overconfidence_penalty',
        'hesitation_sensitivity',
        'relearn_scale',
        'decay_base',
        'last_calibrated_at',
        'calibration_meta',
    ];

    protected $casts = [
        'total_reviews' => 'integer',
        'calibration_reviews' => 'integer',
        'observed_recall_rate' => 'float',
        'mean_prediction_error' => 'float',
        'recall_bias' => 'float',
        'stability_scale' => 'float',
        'difficulty_scale' => 'float',
        'response_sensitivity' => 'float',
        'confidence_sensitivity' => 'float',
        'overconfidence_penalty' => 'float',
        'hesitation_sensitivity' => 'float',
        'relearn_scale' => 'float',
        'decay_base' => 'float',
        'last_calibrated_at' => 'datetime',
        'calibration_meta' => 'array',
    ];
}
