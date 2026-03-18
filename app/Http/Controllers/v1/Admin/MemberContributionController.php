<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MemberContribution;
use App\Models\Admin\MemberContributionSaving;
use App\Models\User\User;
use App\Services\Finance\WalletService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberContributionController extends Controller
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

                $contributionAmount = MemberContributionSaving::where('user_id', $userId)
                    ->value('contribution_amount');

                $user = User::where('user_id', $userId)->first();

                WalletService::deposit(
                    $userId,
                    $contributionAmount,
                    null,
                    'Monthly contribution deposit for user ' . $user->first_name . ' ' . $user->last_name . ' for ' . now()->format('F Y')
                );

                MemberContribution::create([
                    'user_id' => $userId,
                    'contribution_amount' => $contributionAmount,
                    'contribution_date' => now(),
                    'status_id' => 21,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Contribution processed successfully'
                ], 201);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You have already made a contribution for this month. Payment cannot be made twice.'
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
