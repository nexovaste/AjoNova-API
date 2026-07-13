<?php

namespace App\Http\Controllers\v1\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\StatusResource;
use App\Models\Setup\SetupStatus;
use Illuminate\Support\Facades\Cache;

class StatusController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status_id' => 'required|array|min:1',
            'status_id.*' => 'required|exists:setup_statuses,status_id',
        ]);

        try {

            $statusIds = $request->status_id;
            $cacheKey = 'status_' . implode('_', $statusIds);
            $statuses = Cache::tags('setup_statuses')->rememberForever($cacheKey, function () use ($statusIds) {
                return SetupStatus::whereIn('status_id', $statusIds)->get();
            });

            if ($statuses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No status found for these ids.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statuses fetched successfully.',
                'data' => StatusResource::collection($statuses),
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching statuses: ' . $e->getMessage(),
            ], 500);
        }
    }
}
