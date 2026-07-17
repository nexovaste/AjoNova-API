<?php

namespace App\Http\Controllers\v1\Setup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\LgaResource;
use App\Models\Setup\SetupLga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LgaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:setup_states,state_id',
        ]);

        try {
            $stateId = $request->state_id;
            $cacheKey = "lga_list_state_{$stateId}";
            
            $lga = Cache::tags('lga_list')->rememberForever($cacheKey, function () use ($stateId) {
                // FIX: Eager load the 'state' relationship before saving to cache
                return SetupLga::with('state')
                    ->where('state_id', $stateId)
                    ->orderBy('lga_name', 'asc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Local governments fetched successfully.',
                'data' => LgaResource::collection($lga),
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching local governments: ' . $e->getMessage(),
            ], 500);
        }
    }
}