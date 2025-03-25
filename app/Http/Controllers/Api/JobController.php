<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Services\JobFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobController extends Controller
{
    /**
     * The job filter service instance.
     *
     * @var \App\Services\JobFilterService
     */
    protected $filterService;
    
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\JobFilterService $filterService
     * @return void
     */
    public function __construct(JobFilterService $filterService)
    {
        $this->filterService = $filterService;
    }
    
    /**
     * Display a listing of the jobs with optional filtering.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->filterService->applyFilters($request->input('filter'));
            
            // Add pagination
            $perPage = $request->input('per_page', 10);
            $jobs = $query->with(['languages', 'locations', 'categories', 'attributeValues.attribute'])
                         ->paginate($perPage);
            
            // Transform attribute values for better client-side usage
            $jobs->getCollection()->transform(function ($job) {
                // Add a transformed attributes array 
                $job->attributes = $job->attributeValues->map(function ($attributeValue) {
                    return [
                        'id' => $attributeValue->attribute->id,
                        'name' => $attributeValue->attribute->name,
                        'type' => $attributeValue->attribute->type,
                        'value' => $attributeValue->typed_value,
                        'raw_value' => $attributeValue->value,
                    ];
                });
                
                // Remove the original attributeValues for cleaner response
                unset($job->attributeValues);
                
                return $job;
            });
            
            return response()->json([
                'data' => $jobs->items(),
                'meta' => [
                    'current_page' => $jobs->currentPage(),
                    'from' => $jobs->firstItem(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'to' => $jobs->lastItem(),
                    'total' => $jobs->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Filter Error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Display the specified job.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        $job = JobListing::with(['languages', 'locations', 'categories', 'attributeValues.attribute'])
                        ->findOrFail($id);
        
        // Transform attribute values
        $job->attributes = $job->attributeValues->map(function ($attributeValue) {
            return [
                'id' => $attributeValue->attribute->id,
                'name' => $attributeValue->attribute->name,
                'type' => $attributeValue->attribute->type,
                'value' => $attributeValue->typed_value,
                'raw_value' => $attributeValue->value,
            ];
        });
        
        // Remove the original attributeValues for cleaner response
        unset($job->attributeValues);
        
        return response()->json(['data' => $job]);
    }
}
