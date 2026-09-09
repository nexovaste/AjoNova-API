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
                $staff && method_exists($staff, 'can') && !$staff->can('manage activity logs') &&
                    $staff->can('view subordinates activity logs'),
                fn($q) =>
                $q->whereHas(
                    'performedByStaff',
                    fn($sq) =>
                    $sq->where('role_id', '>', $staff->role_id)
                )
            )
            ->when(
                $staff && method_exists($staff, 'can') && !$staff->can('manage activity logs') &&
                    !$staff->can('view subordinates activity logs'),
                fn($q) =>
                $q->where('performed_by', $staff?->staff_id)
            );
    }

    public function index(Request $request)
    {
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

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
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

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
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        try {
            $logIds = $this->visibleLogs($staff)
                ->whereDoesntHave('reads', fn($q) => $q->where('staff_id', $staff->staff_id))
                ->pluck('activity_log_id');

            if ($logIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'All logs are already read.',
                ], 200);
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
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

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
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
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

    public function dashboardMetrics(Request $request)
    {
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        try {
            $staffCount = \App\Models\Admin\Staff::count();
            $memberCount = \App\Models\User\User::count();
            $pendingWithdrawalsCount = \App\Models\Admin\WithdrawalRequest::where('status_id', 5)->count();
            $pendingLoansCount = \App\Models\Admin\Loan::where('status_id', 5)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'staffCount' => $staffCount,
                    'memberCount' => $memberCount,
                    'pendingWithdrawalsCount' => $pendingWithdrawalsCount,
                    'pendingLoansCount' => $pendingLoansCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboardChart(Request $request)
    {
        $staff = $request->user('admin') ?? \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? $request->user();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $days = (int) $request->input('days', 30);
        if ($days < 1) $days = 30;

        try {
            $endDate = \Carbon\Carbon::now();
            $startDate = \Carbon\Carbon::now()->subDays($days - 1)->startOfDay();

            $formattedStartDate = $startDate->format('F d Y');
            $formattedEndDate = $endDate->format('F d Y');

            $savingsSum = \Illuminate\Support\Facades\DB::table('member_savings')
                ->where('created_at', '>=', $startDate)
                ->sum('saving_amount');

            $contributionSum = \Illuminate\Support\Facades\DB::table('member_contributions')
                ->where('created_at', '>=', $startDate)
                ->sum('contribution_amount');

            $totalRevenue = (float) ($savingsSum + $contributionSum);

            $withdrawalSum = \Illuminate\Support\Facades\DB::table('withdrawal_requests')
                ->where('status_id', 6)
                ->where('attended_at', '>=', $startDate)
                ->sum('amount');

            $loanSum = \Illuminate\Support\Facades\DB::table('loans')
                ->where('status_id', 6)
                ->where('attended_at', '>=', $startDate)
                ->sum('principal_amount');

            $totalExpenses = (float) ($withdrawalSum + $loanSum);

            $categories = [];
            $revenueSeries = [];
            $expensesSeries = [];

            if ($days <= 1) {
                $categories = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM'];
                $revenueSeries = [$totalRevenue * 0.1, $totalRevenue * 0.2, $totalRevenue * 0.3, $totalRevenue * 0.2, $totalRevenue * 0.15, $totalRevenue * 0.05];
                $expensesSeries = [$totalExpenses * 0.05, $totalExpenses * 0.25, $totalExpenses * 0.4, $totalExpenses * 0.15, $totalExpenses * 0.1, $totalExpenses * 0.05];
            } elseif ($days <= 7) {
                for ($i = 6; $i >= 0; $i--) {
                    $dayDate = \Carbon\Carbon::now()->subDays($i);
                    $categories[] = $dayDate->format('D, M d');
                    
                    $rev = \Illuminate\Support\Facades\DB::table('member_savings')->whereDate('created_at', $dayDate->toDateString())->sum('saving_amount')
                          + \Illuminate\Support\Facades\DB::table('member_contributions')->whereDate('created_at', $dayDate->toDateString())->sum('contribution_amount');
                    $exp = \Illuminate\Support\Facades\DB::table('withdrawal_requests')->where('status_id', 6)->whereDate('attended_at', $dayDate->toDateString())->sum('amount')
                          + \Illuminate\Support\Facades\DB::table('loans')->where('status_id', 6)->whereDate('attended_at', $dayDate->toDateString())->sum('principal_amount');

                    $revenueSeries[] = (float) $rev;
                    $expensesSeries[] = (float) $exp;
                }
            } elseif ($days <= 90) {
                for ($i = 5; $i >= 0; $i--) {
                    $subStart = \Carbon\Carbon::now()->subDays(($i + 1) * floor($days / 6))->startOfDay();
                    $subEnd = \Carbon\Carbon::now()->subDays($i * floor($days / 6))->endOfDay();
                    $categories[] = $subStart->format('M d') . ' - ' . $subEnd->format('M d');

                    $rev = \Illuminate\Support\Facades\DB::table('member_savings')->whereBetween('created_at', [$subStart, $subEnd])->sum('saving_amount')
                          + \Illuminate\Support\Facades\DB::table('member_contributions')->whereBetween('created_at', [$subStart, $subEnd])->sum('contribution_amount');
                    $exp = \Illuminate\Support\Facades\DB::table('withdrawal_requests')->where('status_id', 6)->whereBetween('attended_at', [$subStart, $subEnd])->sum('amount')
                          + \Illuminate\Support\Facades\DB::table('loans')->where('status_id', 6)->whereBetween('attended_at', [$subStart, $subEnd])->sum('principal_amount');

                    $revenueSeries[] = (float) $rev;
                    $expensesSeries[] = (float) $exp;
                }
            } else {
                for ($i = 11; $i >= 0; $i--) {
                    $monthDate = \Carbon\Carbon::now()->subMonths($i);
                    $categories[] = $monthDate->format('M Y');

                    $rev = \Illuminate\Support\Facades\DB::table('member_savings')->whereYear('created_at', $monthDate->year)->whereMonth('created_at', $monthDate->month)->sum('saving_amount')
                          + \Illuminate\Support\Facades\DB::table('member_contributions')->whereYear('created_at', $monthDate->year)->whereMonth('created_at', $monthDate->month)->sum('contribution_amount');
                    $exp = \Illuminate\Support\Facades\DB::table('withdrawal_requests')->where('status_id', 6)->whereYear('attended_at', $monthDate->year)->whereMonth('attended_at', $monthDate->month)->sum('amount')
                          + \Illuminate\Support\Facades\DB::table('loans')->where('status_id', 6)->whereYear('attended_at', $monthDate->year)->whereMonth('attended_at', $monthDate->month)->sum('principal_amount');

                    $revenueSeries[] = (float) $rev;
                    $expensesSeries[] = (float) $exp;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'days' => $days,
                    'dateRangeText' => "{$formattedStartDate} - {$formattedEndDate}",
                    'totalSales' => number_format($totalRevenue, 2),
                    'totalExpenses' => number_format($totalExpenses, 2),
                    'totalSalesRaw' => $totalRevenue,
                    'totalExpensesRaw' => $totalExpenses,
                    'categories' => $categories,
                    'series' => [
                        [
                            'name' => 'Revenue',
                            'data' => $revenueSeries,
                        ],
                        [
                            'name' => 'Expenses',
                            'data' => $expensesSeries,
                        ],
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load chart metrics: ' . $e->getMessage()
            ], 500);
        }
    }
}
