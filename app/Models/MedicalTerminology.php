<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalTerminology extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'medical_terminology';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'subdomain_id',
        'term',
        'description',
        'importance_factor',
        'is_critical',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'importance_factor' => 'float',
        'is_critical' => 'boolean',
    ];

    /**
     * Get the domain that this terminology belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(MedicalDomain::class, 'domain_id');
    }

    /**
     * Get the subdomain that this terminology belongs to.
     */
    public function subdomain()
    {
        return $this->belongsTo(MedicalSubdomain::class, 'subdomain_id');
    }

    /**
     * Get synonyms for this term.
     */
    public function synonyms()
    {
        return $this->hasMany(MedicalSynonym::class, 'term', 'term');
    }

    /**
     * Scope a query to only include critical terms.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope a query to filter by domain.
     */
    public function scopeInDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Scope a query to filter by subdomain.
     */
    public function scopeInSubdomain($query, $subdomainId)
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * Scope a query to order by importance factor.
     */
    public function scopeByImportance($query, $direction = 'desc')
    {
        return $query->orderBy('importance_factor', $direction);
    }
} 