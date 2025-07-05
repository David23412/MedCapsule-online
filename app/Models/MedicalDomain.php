<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalDomain extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the subdomains for this medical domain.
     */
    public function subdomains()
    {
        return $this->hasMany(MedicalSubdomain::class, 'domain_id');
    }

    /**
     * Get the terminology associated with this domain.
     */
    public function terminology()
    {
        return $this->hasMany(MedicalTerminology::class, 'domain_id');
    }

    /**
     * Get the abbreviations associated with this domain.
     */
    public function abbreviations()
    {
        return $this->hasMany(MedicalAbbreviation::class, 'domain_id');
    }

    /**
     * Get the synonyms associated with this domain.
     */
    public function synonyms()
    {
        return $this->hasMany(MedicalSynonym::class, 'domain_id');
    }

    /**
     * Get the patterns associated with this domain.
     */
    public function patterns()
    {
        return $this->hasMany(MedicalPattern::class, 'domain_id');
    }

    /**
     * Get the contradictions associated with this domain.
     */
    public function contradictions()
    {
        return $this->hasMany(MedicalContradiction::class, 'domain_id');
    }

    /**
     * Get the process-location associations for this domain.
     */
    public function processLocations()
    {
        return $this->hasMany(MedicalProcessLocation::class, 'domain_id');
    }

    /**
     * Scope a query to only include active domains.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order domains by their display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
} 