<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Job",
 *     title="Job Listing",
 *     description="Job listing model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="title", type="string", example="Senior PHP Developer"),
 *     @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
 *     @OA\Property(property="company_name", type="string", example="Acme Inc"),
 *     @OA\Property(property="salary_min", type="number", format="float", example=60000),
 *     @OA\Property(property="salary_max", type="number", format="float", example=100000),
 *     @OA\Property(property="is_remote", type="boolean", example=true),
 *     @OA\Property(property="job_type", type="string", enum={"full-time", "part-time", "contract", "freelance"}, example="full-time"),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2023-05-15T12:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-05-10T09:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-05-10T09:00:00Z"),
 *     @OA\Property(
 *         property="languages",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="PHP")
 *         )
 *     ),
 *     @OA\Property(
 *         property="locations",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="city", type="string", example="New York"),
 *             @OA\Property(property="state", type="string", example="NY"),
 *             @OA\Property(property="country", type="string", example="USA")
 *         )
 *     ),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Web Development")
 *         )
 *     ),
 *     @OA\Property(
 *         property="attributes",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Years of Experience"),
 *             @OA\Property(property="type", type="string", enum={"text", "number", "boolean", "date", "select"}, example="number"),
 *             @OA\Property(property="value", type="mixed", example=5),
 *             @OA\Property(property="raw_value", type="string", example="5")
 *         )
 *     ),
 * )
 */
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
