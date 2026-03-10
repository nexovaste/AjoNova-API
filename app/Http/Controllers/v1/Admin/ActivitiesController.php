<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ActivitiesResource;
use App\Models\Admin\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


class ActivitiesController extends Controller
{

    // Display a listing of the resource.
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $cursor = $request->get('cursor', 'first_page');
        $cacheKey = "activities_list_{$cursor}";
        $activitiesData = Cache::tags('activities_list')->flexible($cacheKey, [now()->addMonth(), null], function () use ($admin) {
            return ActivityLog::orderBy('id', 'desc')
                ->cursorPaginate(30);
                
        });
        if ($activitiesData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No activity records found.',
                'data' => []
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Activity records fetched successfully.',
            'data' =>  ActivitiesResource::collection($activitiesData->items()),
            'pagination' => [
                'nextCursor' => $activitiesData->nextCursor()?->encode(),
                'previousCursor' => $activitiesData->previousCursor()?->encode(),
            ],
        ], 200);

      
    }

   
    // Display the specified resource.
    public function show(string $id)
    {

    $activitiesData = Cache::remember("activity_{$id}", now()->addMonth(), function () use ($id) {
        return new ActivitiesResource(
        ActivityLog::with(['staff', 'user'])->findOrFail($id)
        );
    });    

    return response()->json([
        'success' => true,
        'message' => 'Activity record fetched successfully.',
        'data' => $activitiesData
    ], 200);
    }
}
