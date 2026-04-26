<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Admin\LedgerEntry;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $userId = $request->header('X-User-ID');
            $data = LedgerEntry::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('ledger_entry_id', 'desc')
                ->cursorPaginate(10);
            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found.',
                    'data' => []
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Records fetched successfully.',
                'data' => ReportResource::collection($data),
                'pagination' => [
                    'per_page' => $data->perPage(),
                    'next_cursor' => optional($data->nextCursor())->encode(),
                    'prev_cursor' => optional($data->previousCursor())->encode(),
                    'has_more' => $data->hasMorePages(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


}
