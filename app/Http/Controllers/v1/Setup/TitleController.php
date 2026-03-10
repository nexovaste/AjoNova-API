<?php

namespace App\Http\Controllers\v1\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\TitleResource;
use App\Models\Setup\SetupTitle;
use Illuminate\Support\Facades\Cache;

class TitleController extends Controller
{
    public function index(Request $request)
    {
        try {

            $cacheKey = "title_list";
            $titles = Cache::tags('title_list')->rememberForever($cacheKey, function () {
                return SetupTitle::orderBy('title_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Titles fetched successfully.',
                'data' => TitleResource::collection($titles)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching titles: ' . $e->getMessage(),
            ], 500);
        }
    }
}
