<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Admin\LedgerEntry;
use App\Services\Cache\TagCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $userId = auth('user')->id() 
                ?? $request->header('X-User-ID') 
                ?? auth('admin')->id();

            $query = LedgerEntry::where('user_id', $userId);

            if ($request->filled('month') && $request->filled('year')) {
                $query->whereMonth('created_at', $request->month)
                      ->whereYear('created_at', $request->year);
            } elseif ($request->filled('year') && !$request->filled('month')) {
                $query->whereYear('created_at', $request->year);
            } elseif ($request->filled('month') && !$request->filled('year')) {
                $query->whereMonth('created_at', $request->month);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $query->orderBy('created_at', 'desc')
                  ->orderBy('ledger_entry_id', 'desc');

            if ($request->boolean('all') || $request->query('per_page') === 'all') {
                $ledgerEntries = $query->get();
                return response()->json([
                    'success' => true,
                    'message' => 'Records fetched successfully.',
                    'data' => ReportResource::collection($ledgerEntries),
                ], 200);
            }

            $cursor = $request->query('cursor');
            $hasFilters = $request->filled('month') || $request->filled('year') || $request->filled('start_date') || $request->filled('end_date');

            if ($hasFilters) {
                $ledgerEntry = $query->get();
                return response()->json([
                    'success' => true,
                    'message' => 'Records fetched successfully.',
                    'data' => ReportResource::collection($ledgerEntry),
                ], 200);
            }

            $cacheKey = "ledger_entries_user_{$userId}_cursor_" . ($cursor ?? 'first_page');
            $ledgerEntry = TagCache::flexible("ledger_entries_user_{$userId}", $cacheKey, [now()->addMonth(), null], function () use ($userId, $cursor) {
                return LedgerEntry::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('ledger_entry_id', 'desc')
                    ->cursorPaginate(30, ['*'], 'cursor', $cursor);
            });

            if ($ledgerEntry->isEmpty()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'No records found.',
                    'data' => []
                ], 200);
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
