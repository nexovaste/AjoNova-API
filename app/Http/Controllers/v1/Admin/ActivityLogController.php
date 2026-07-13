<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ActivityLogResource;
use App\Http\Resources\Admin\ReadByResource;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\ReadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class ActivityLogController extends Controller
{
    private function visibleLogs($staff)
    {
        return ActivityLog::query()
            ->when(
                !$staff->can('manage activity logs') &&
                    $staff->can('view subordinates activity logs'),
                fn($q) =>
                $q->whereHas(
                    'performedByStaff',
                    fn($sq) =>
                    $sq->where('role_id', '>', $staff->role_id)
                )
            )
            ->when(
                !$staff->can('manage activity logs') &&
                    !$staff->can('view subordinates activity logs'),
                fn($q) =>
                $q->where('performed_by', $staff->staff_id)
            );
    }

    public function index(Request $request)
    {
        $staff = $request->user();

        $request->validate([
            'search'    => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = $this->visibleLogs($staff);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('performed_by', 'like', "%{$search}%")
                        ->orWhereHas('performedByStaff', function ($sq) use ($search) {
                            $sq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            }
            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $logs = $query
                ->with([
                    'performedByStaff'
                ])
                ->withExists([
                    'reads as is_read' => fn($q) =>
                    $q->where('staff_id', $staff->staff_id)
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('activity_log_id')
                ->cursorPaginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => ActivityLogResource::collection($logs),
                'pagination' => [
                    'next_cursor' => $logs->nextCursor()?->encode(),
                    'prev_cursor' => $logs->previousCursor()?->encode(),
                    'per_page' => $logs->perPage(),
                    'has_more' => $logs->hasMorePages(),
                ],
                'filters' => [
                    'search' => $request->input('search'),
                    'date_from' => $request->input('date_from'),
                    'date_to' => $request->input('date_to'),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $activityLogId)
    {
        $staff = $request->user();

        try {
            $log = $this->visibleLogs($staff)
                ->where('activity_log_id', $activityLogId)
                ->firstOrFail();

            ReadActivity::firstOrCreate(
                [
                    'activity_log_id' => $log->activity_log_id,
                    'staff_id'        => $staff->staff_id,
                ],
                [
                    'read_at' => now(),
                ]
            );
            $log->is_read = true;

            return response()->json([
                'success' => true,
                'data' => new ActivityLogResource($log),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to show activity log: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        $staff = $request->user();

        try {
            $logIds = $this->visibleLogs($staff)
                ->whereDoesntHave('reads', fn($q) => $q->where('staff_id', $staff->staff_id))
                ->pluck('activity_log_id');

            if ($logIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No unread logs found.',
                ], 404);
            }

            $logs = $logIds->map(function ($logId) use ($staff) {
                return [
                    'activity_log_id' => $logId,
                    'staff_id' => $staff->staff_id,
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            DB::table('read_activities')->insertOrIgnore($logs->toArray());

            return response()->json([
                'success' => true,
                'message' => 'All logs marked as read.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all logs as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request, $activityLogId)
    {
        return $this->show($request, $activityLogId);
    }

    public function unreadCount(Request $request)
    {
        $staff = $request->user();

        try {
            $logCount = $this->visibleLogs($staff)->whereDoesntHave('reads', function ($count) use ($staff) {
                $count->where('staff_id', $staff->staff_id);
            })->count();

            return response()->json([
                'success' => true,
                'unreadCount' => $logCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count: ' . $e->getMessage()
            ], 500);
        }
    }

    public function readBy(Request $request, $logId)
    {
        $staff = $request->user();
        try {
            $this->visibleLogs($staff)
                ->where('activity_log_id', $logId)
                ->firstOrFail();

            $reads = ReadActivity::with('staff')
                ->where('activity_log_id', $logId)
                ->orderByDesc('read_at')
                ->cursorPaginate(5);

            return response()->json([
                'success' => true,
                'data' => ReadByResource::collection($reads),
                'pagination' => [
                    'next_cursor' => $reads->nextCursor()?->encode(),
                    'previous_cursor' => $reads->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load data: ' . $e->getMessage()
            ], 500);
        }
    }
}
