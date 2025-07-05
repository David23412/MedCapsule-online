<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalContradiction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'type',
        'contradiction_rules',
        'description',
        'severity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contradiction_rules' => 'array',
        'severity' => 'float',
    ];

    /**
     * Get the domain that this contradiction belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(MedicalDomain::class, 'domain_id');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by domain.
     */
    public function scopeInDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Scope a query to order by severity.
     */
    public function scopeBySeverity($query, $direction = 'desc')
    {
        return $query->orderBy('severity', $direction);
    }

    /**
     * Check if a given pair of terms contradicts according to rules.
     *
     * @param string $term1
     * @param string $term2
     * @return bool
     */
    public function termsContradict($term1, $term2)
    {
        $rules = $this->contradiction_rules;
        
        foreach ($rules as $rule) {
            // Handle simple pair-based contradictions
            if (isset($rule['term1']) && isset($rule['term2'])) {
                if (($rule['term1'] === $term1 && $rule['term2'] === $term2) || 
                    ($rule['term1'] === $term2 && $rule['term2'] === $term1)) {
                    return true;
                }
            }
            
            // Handle directional contradictions
            if (isset($rule['positive']) && isset($rule['negative'])) {
                if (in_array($term1, $rule['positive']) && in_array($term2, $rule['negative'])) {
                    return true;
                }
                if (in_array($term2, $rule['positive']) && in_array($term1, $rule['negative'])) {
                    return true;
                }
            }
            
            // Handle location contradictions
            if (isset($rule['process']) && isset($rule['wrong_locations'])) {
                if ($rule['process'] === $term1 && in_array($term2, $rule['wrong_locations'])) {
                    return true;
                }
                if ($rule['process'] === $term2 && in_array($term1, $rule['wrong_locations'])) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get all term pairs that would contradict in this rule set.
     *
     * @return array Array of contradicting term pairs
     */
    public function getAllContradictingPairs()
    {
        $pairs = [];
        $rules = $this->contradiction_rules;
        
        foreach ($rules as $rule) {
            // Handle simple pair-based contradictions
            if (isset($rule['term1']) && isset($rule['term2'])) {
                $pairs[] = [$rule['term1'], $rule['term2']];
            }
            
            // Handle directional contradictions
            if (isset($rule['positive']) && isset($rule['negative'])) {
                foreach ($rule['positive'] as $posTerm) {
                    foreach ($rule['negative'] as $negTerm) {
                        $pairs[] = [$posTerm, $negTerm];
                    }
                }
            }
            
            // Handle location contradictions
            if (isset($rule['process']) && isset($rule['wrong_locations'])) {
                foreach ($rule['wrong_locations'] as $location) {
                    $pairs[] = [$rule['process'], $location];
                }
            }
        }
        
        return $pairs;
    }
} 