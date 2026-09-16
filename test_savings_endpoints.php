<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$user = User::first();
Auth::guard('user')->setUser($user);

$savingsController = new App\Http\Controllers\v1\Admin\MemberSavingController();
$req = new Request();
$resSavings = $savingsController->index($req);

echo "MEMBER SAVINGS API STATUS: " . $resSavings->getStatusCode() . "\n";
echo json_encode(json_decode($resSavings->getContent()), JSON_PRETTY_PRINT) . "\n\n";

$targetController = new App\Http\Controllers\v1\Admin\MemberTargetSavingController();
$resTarget = $targetController->index($req);

echo "MEMBER TARGET SAVINGS API STATUS: " . $resTarget->getStatusCode() . "\n";
echo json_encode(json_decode($resTarget->getContent()), JSON_PRETTY_PRINT) . "\n";
