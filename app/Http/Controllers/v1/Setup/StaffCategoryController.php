<?php

namespace App\Http\Controllers\v1\Setup;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\StaffCategoryResource;
use App\Models\Setup\StaffCategory;
use Illuminate\Support\Facades\Cache;


class StaffCategoryController extends Controller
{
    public function index()
    {
       try {

            $cacheKey = "staff_category_list";
            $staffcategory = Cache::tags('staff_category_list')->rememberForever($cacheKey, function () {
                return StaffCategory::orderBy('staff_category_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Staff category fetched successfully.',
                'data' => StaffCategoryResource::collection($staffcategory)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching Staff category: ' . $e->getMessage(),
            ], 500);
        }
    }
}
