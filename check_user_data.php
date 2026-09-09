<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User\User;
use App\Models\Admin\Loan;
use App\Models\Admin\WithdrawalRequest;

$users = User::all();
foreach ($users as $u) {
    $loanCount = Loan::where('user_id', $u->user_id)->count();
    $wdrCount = WithdrawalRequest::where('user_id', $u->user_id)->count();
    echo "User ID: {$u->user_id} | Email: {$u->email} | Name: {$u->first_name} {$u->last_name} | Loans: {$loanCount} | Withdrawals: {$wdrCount}\n";
}
