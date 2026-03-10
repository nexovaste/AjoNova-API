<?php

namespace App\Http\Controllers\v1\Setup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\CountryResource;
use App\Models\Setup\SetupCountry;
use Illuminate\Support\Facades\Cache;


class CountryController extends Controller
{
    public function index()
    {
        try {

            $cacheKey = "country_list";
            $countries = Cache::tags('country_list')->rememberForever($cacheKey, function () {
                return SetupCountry::orderBy('country_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Countries fetched successfully.',
                'data' => CountryResource::collection($countries)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching countries: ' . $e->getMessage(),
            ], 500);
        }
    }
}
