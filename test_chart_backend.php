<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$days = 30;
$endDate = Carbon::now();
$startDate = Carbon::now()->subDays($days - 1)->startOfDay();

echo "Fetching chart metrics from {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}\n";

// Revenue sources: MemberSavings deposits, MemberContributions deposits, TargetSavings deposits, LoanRepayments
$savingsInflows = DB::table('member_savings')
    ->where('created_at', '>=', $startDate)
    ->sum('amount');

$contributionInflows = DB::table('member_contributions')
    ->where('created_at', '>=', $startDate)
    ->sum('amount');

$targetSavingsInflows = DB::table('member_target_savings')
    ->where('created_at', '>=', $startDate)
    ->sum('amount');

$totalRevenue = $savingsInflows + $contributionInflows + $targetSavingsInflows;

// Expense sources: Approved withdrawal requests, Approved loans
$approvedWithdrawals = DB::table('withdrawal_requests')
    ->where('status_id', 6)
    ->where('withdraw_at', '>=', $startDate)
    ->sum('amount');

$approvedLoans = DB::table('loans')
    ->where('status_id', 6)
    ->where('created_at', '>=', $startDate)
    ->sum('amount');

$totalExpenses = $approvedWithdrawals + $approvedLoans;

echo "Total Revenue (Inflows): ₦" . number_format($totalRevenue, 2) . "\n";
echo "Total Expenses (Outflows): ₦" . number_format($totalExpenses, 2) . "\n";
