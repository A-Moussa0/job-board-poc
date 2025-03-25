<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAttributeValue extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_listing_id',
        'attribute_id',
        'value',
    ];
    
    /**
     * Get the job that owns this attribute value.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
    
    /**
     * Get the attribute that owns this value.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
    
    /**
     * Get the typed value of this attribute.
     * Converts the stored text value to the appropriate type based on the attribute's type.
     */
    public function getTypedValueAttribute()
    {
        $attribute = $this->attribute;
        
        if (!$attribute) {
            return $this->value;
        }
        
        switch ($attribute->type) {
            case 'boolean':
                return (bool) $this->value;
            case 'number':
                return (float) $this->value;
            case 'date':
                return \Carbon\Carbon::parse($this->value);
            case 'select':
                // For select types, just return the string value
                return $this->value;
            case 'text':
            default:
                return $this->value;
        }
    }
}
