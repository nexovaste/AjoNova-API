<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Admin\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $userId = $request->header('X-User-ID');
            $cursor = $request->query('cursor');
            $cacheKey = "ledger_entries_user_{$userId}_cursor_" . ($cursor ?? 'first_page');
            $ledgerEntry = Cache::tags("ledger_entries_user_{$userId}")->flexible($cacheKey, [now()->addMonth(), null], function () use ($userId, $cursor) {
                return LedgerEntry::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('ledger_entry_id', 'desc')
                ->cursorPaginate(30, ['*'], 'cursor', $cursor);
            }); 
            if ($ledgerEntry->isEmpty()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No records found.',
                    'data' => []
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Records fetched successfully.',
                'data' => ReportResource::collection($ledgerEntry),
                'pagination' => [
                    'per_page' => $ledgerEntry->perPage(),
                    'next_cursor' => optional($ledgerEntry->nextCursor())->encode(),
                    'prev_cursor' => optional($ledgerEntry->previousCursor())->encode(),
                    'has_more' => $ledgerEntry->hasMorePages(),
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
