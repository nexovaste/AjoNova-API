<?php

namespace App\Http\Controllers\v1\Setup;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\MembershipTypeResource;
use App\Models\Setup\MembershipType;
use Illuminate\Support\Facades\Cache;


class MembershipTypeController extends Controller
{
    public function index()
    {
       try {

            $cacheKey = "membership_list";
            $membershiptype = Cache::tags('membership_list')->rememberForever($cacheKey, function () {
                return MembershipType::orderBy('membership_type_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Membership fetched successfully.',
                'data' => MembershipTypeResource::collection($membershiptype)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching membership: ' . $e->getMessage(),
            ], 500);
        }
    }
}
