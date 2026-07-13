<?php

namespace App\Http\Controllers\v1\Setup;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\MeansOfIdentificationResource;
use App\Models\Setup\MeansOfIdentification;
use Illuminate\Support\Facades\Cache;


class MeansOfIdentificationController extends Controller
{
    public function index()
    {
       try {

            $cacheKey = "identification_list";
            $meansofidentification = Cache::tags('identification_list')->rememberForever($cacheKey, function () {
                return MeansOfIdentification::orderBy('means_of_identification_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Means of identification fetched successfully.',
                'data' => MeansOfIdentificationResource::collection($meansofidentification)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching means of identification: ' . $e->getMessage(),
            ], 500);
        }
    }
}
