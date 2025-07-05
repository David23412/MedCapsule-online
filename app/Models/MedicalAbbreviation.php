<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalAbbreviation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'abbreviation',
        'expansion',
        'domain_id',
        'context',
        'confidence',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confidence' => 'float',
    ];

    /**
     * Get the domain that this abbreviation belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(MedicalDomain::class, 'domain_id');
    }

    /**
     * Scope a query to filter by domain.
     */
    public function scopeInDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId)->orWhereNull('domain_id');
    }

    /**
     * Scope a query to filter by abbreviation.
     */
    public function scopeForAbbreviation($query, $abbreviation)
    {
        return $query->where('abbreviation', $abbreviation);
    }

    /**
     * Scope a query to order by confidence.
     */
    public function scopeByConfidence($query, $direction = 'desc')
    {
        return $query->orderBy('confidence', $direction);
    }

    /**
     * Scope a query to find possible abbreviations for a term.
     */
    public function scopePossibleAbbreviations($query, $term)
    {
        return $query->where('expansion', 'like', '%' . $term . '%');
    }

    /**
     * Get the most likely expansion for an abbreviation in a given domain.
     *
     * @param string $abbreviation
     * @param int|null $domainId
     * @return string|null
     */
    public static function getExpansion($abbreviation, $domainId = null)
    {
        $query = static::forAbbreviation($abbreviation)->byConfidence();
        
        if ($domainId) {
            $query->inDomain($domainId);
        }
        
        $abbr = $query->first();
        
        return $abbr ? $abbr->expansion : null;
    }

    /**
     * Find all possible abbreviations for a given term.
     *
     * @param string $term
     * @return array
     */
    public static function findAbbreviationsForTerm($term)
    {
        $abbreviations = static::possibleAbbreviations($term)->get();
        
        // Group by abbreviation and take the highest confidence one
        $result = [];
        foreach ($abbreviations as $abbr) {
            if (!isset($result[$abbr->abbreviation]) || 
                $result[$abbr->abbreviation]['confidence'] < $abbr->confidence) {
                $result[$abbr->abbreviation] = [
                    'abbreviation' => $abbr->abbreviation,
                    'expansion' => $abbr->expansion,
                    'confidence' => $abbr->confidence,
                    'domain_id' => $abbr->domain_id,
                ];
            }
        }
        
        return array_values($result);
    }
} 