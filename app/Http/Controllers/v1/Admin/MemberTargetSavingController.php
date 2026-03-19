<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\MemberTargetSavingSetting;
use App\Models\User\User;
use App\Services\Finance\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberTargetSavingController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        //
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        try {

            return DB::transaction(function () use ($request) {

                $userId = $request->header('X-User-ID');

                $targetSettings = MemberTargetSavingSetting::where('user_id', $userId)->first();
                $user = User::where('user_id', $userId)->first();

                $ledgerEntry = WalletService::deposit(
                    $userId,
                    $targetSettings->monthly_amount,
                    null,
                    'Monthly target deposit for user ' . $user->first_name . ' ' . $user->last_name . ' for ' . now()->format('F Y')
                );

                $startDate = Carbon::parse($targetSettings->start_date);
                $endDate = Carbon::parse($targetSettings->end_date);
                $now = now();

                if ($now->lt($startDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. Your target saving start date is ' . $targetSettings->start_date->format('Y-m-d') . '. Please wait until the start date to make your first target saving.'
                    ], 400);
                } elseif ($now->gt($endDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. Your target saving end date is ' . $targetSettings->end_date->format('Y-m-d') . '. Your target saving period has ended.'
                    ], 400);
                } else {
                    MemberTargetSaving::create([
                        'user_id' => $userId,
                        'member_target_saving_setting_id' => $targetSettings->member_target_saving_setting_id,
                        'target_amount' => $targetSettings->monthly_amount,
                        'monthly_amount' => $targetSettings->monthly_amount,
                        'current_amount' => $targetSettings->current_amount + $ledgerEntry->amount,
                        'saving_date' => now(),
                        'status_id' => 21,
                        'ledger_entry_id' => $ledgerEntry->ledger_entry_id,
                        'reference' => $ledgerEntry->reference,
                        'processed_by' => $ledgerEntry->created_by,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Target saving processed successfully'
                ], 201);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You have already made a target saving for this month. Payment cannot be made twice.'
            ], 409);
        }
    }

    // Display the specified resource.
    public function show(string $id)
    {
        //
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        //
    }

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {
        //
    }
}
