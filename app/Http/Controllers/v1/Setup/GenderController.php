<?php

namespace App\Http\Controllers\v1\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\GenderResource;
use App\Models\Setup\SetupGender;
use App\Services\Cache\TagCache;

class GenderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cacheKey = "gender_list";
            $gender = TagCache::rememberForever('gender_list', $cacheKey, function () {
                return SetupGender::orderBy('gender_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Gender records fetched successfully.',
                'data' => GenderResource::collection($gender),
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching gender: ' . $e->getMessage(),
            ], 500);
        }
    }
}
