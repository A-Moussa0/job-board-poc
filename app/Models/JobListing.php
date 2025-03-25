<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'company_name',
        'salary_min',
        'salary_max',
        'is_remote',
        'job_type',
        'status',
        'published_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_remote' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'published_at' => 'datetime',
    ];
    
    /**
     * Get the languages required for this job.
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'job_language');
    }
    
    /**
     * Get the locations for this job.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'job_location');
    }
    
    /**
     * Get the categories for this job.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'job_category');
    }
    
    /**
     * Get the attribute values for this job.
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }
    
    /**
     * Scope a query to only include published jobs.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
    
    /**
     * Scope a query to only include remote jobs.
     */
    public function scopeRemote($query)
    {
        return $query->where('is_remote', true);
    }
    
    /**
     * Scope a query to only include jobs of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('job_type', $type);
    }
}
