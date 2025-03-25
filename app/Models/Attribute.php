<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'options',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
    ];
    
    /**
     * Get the attribute values for this attribute.
     */
    public function values(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }
    
    /**
     * Determine if this attribute is of type 'select'.
     */
    public function isSelectType(): bool
    {
        return $this->type === 'select';
    }
    
    /**
     * Determine if this attribute is of type 'boolean'.
     */
    public function isBooleanType(): bool
    {
        return $this->type === 'boolean';
    }
    
    /**
     * Determine if this attribute is of type 'number'.
     */
    public function isNumberType(): bool
    {
        return $this->type === 'number';
    }
    
    /**
     * Determine if this attribute is of type 'date'.
     */
    public function isDateType(): bool
    {
        return $this->type === 'date';
    }
    
    /**
     * Determine if this attribute is of type 'text'.
     */
    public function isTextType(): bool
    {
        return $this->type === 'text';
    }
}
