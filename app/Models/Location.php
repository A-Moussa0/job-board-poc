<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city',
        'state',
        'country',
    ];
    
    /**
     * Get the jobs located at this location.
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(JobListing::class, 'job_location');
    }
    
    /**
     * Get the full address representation.
     */
    public function getFullAddressAttribute(): string
    {
        if ($this->state) {
            return "{$this->city}, {$this->state}, {$this->country}";
        }
        
        return "{$this->city}, {$this->country}";
    }
}
