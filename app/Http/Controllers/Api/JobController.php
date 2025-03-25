<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Services\JobFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Info(
 *     title="Job Board API",
 *     version="1.0.0",
 *     description="API for job listings with advanced filtering capabilities",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */
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
     * @OA\Get(
     *     path="/api/jobs",
     *     summary="Get filtered job listings",
     *     description="Returns a paginated list of job listings based on filter criteria",
     *     operationId="getJobs",
     *     tags={"Jobs"},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter expression (e.g. job_type = full-time AND languages HAS_ANY (PHP, JavaScript))",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Job")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid filter syntax",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Filter Error"),
     *             @OA\Property(property="message", type="string", example="Invalid condition format")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/jobs/{id}",
     *     summary="Get job details",
     *     description="Returns the details of a specific job listing",
     *     operationId="getJobById",
     *     tags={"Jobs"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", ref="#/components/schemas/Job")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Not found.")
     *         )
     *     )
     * )
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
