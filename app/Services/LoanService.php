<?php

namespace App\Services;

use App\Models\Admin\Guarantor;
use App\Models\Admin\LedgerEntry;
use App\Models\Admin\Loan;
use App\Models\Admin\LoanPolicy;
use App\Models\Admin\LoanRepaymentSchedule;
use App\Models\Admin\MemberContributionSaving;
use App\Models\Admin\Wallet;
use App\Models\Setup\SetupCounter;
use App\Services\Cache\ClearCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoanService
{

    public static function applyLoan(
        $userId,
        $principalAmount,
        $titleId,
        $genderId,
        $firstName,
        $lastName,
        $middleName,
        $phoneNumber,
        $email,
        $address,
        $occupation = null,
        $meansOfIdentificationId,
        $identificationNumber,
        $relationshipToBorrower,
        $guarateedAmount,
    ) {
        $loanPolicy = LoanPolicy::findOrFail(1);
        $userPolicy = MemberContributionSaving::where('user_id', $userId)->first();
        $memberContributions = MemberContributionSaving::where('user_id', $userId)->get();
        $wallet = Wallet::where('user_id', $userId)->first();

        if ($loanPolicy->minimum_amount > $principalAmount) {
            throw new \Exception('Principal amount is less than the minimum allowed by the loan policy.');
        } else if ($loanPolicy->maximum_amount && $principalAmount > $loanPolicy->maximum_amount) {
            throw new \Exception('Principal amount exceeds the maximum allowed by the loan policy.');
        } else if ($loanPolicy->max_duration_months < $loanPolicy->min_duration_months || $loanPolicy->max_duration_months > $loanPolicy->max_duration_months) {
            throw new \Exception('Duration of the loan must be between ' . $loanPolicy->min_duration_months . ' and ' . $loanPolicy->max_duration_months . ' months.');
        } else if ($userPolicy->contribution_amount * $loanPolicy->loan_multiplier < $principalAmount) {
            throw new \Exception('Principal amount exceeds the maximum allowed based on your monthly contributions and the loan multiplier.');
        } else if (count($memberContributions) < $loanPolicy->eligibility_months) {
            throw new \Exception('You are not eligible to apply for a loan based on your contribution history and the loan policy requirements.');
        } else if ($loanPolicy->allow_multiple_loans == false && Loan::where('user_id', $userId)->whereIn('is_active_loan', [true])->exists()) {
            throw new \Exception('You already have an active or pending loan. Multiple loans are not allowed based on the current loan policy.');
        } else if ($principalAmount + $wallet->outstanding_loan_balance > $loanPolicy->loan_multiplier * $userPolicy->contribution_amount) {
            throw new \Exception('You cannot take this loan because it will exceed the maximum outstanding loan balance allowed by the loan policy.');
        } else if (Loan::where('user_id', $userId)->whereIn('status_id', [5])->exists()) {
            throw new \Exception('You have a pending loan application. Please wait for it to be processed before applying for another loan.');
        } else if ($principalAmount != $guarateedAmount) {
            throw new \Exception('The guaranteed amount must be equal to the loan amount.');
        } else {

            $loanId = SetupCounter::generateCustomId('LN');
            $loanData = Loan::create([
                'loan_id' => $loanId,
                'user_id' => $userId,
                'duration_months' => $loanPolicy->max_duration_months,
                'principal_amount' => $principalAmount,
                'interest_amount' => $loanPolicy->interest_rate / 100 * $principalAmount,
                'loan_reference' => uniqid('loan_'),
                'requested_at' => now(),
            ]);

            Guarantor::create([
                'loan_id' => $loanId,
                'title_id' => $titleId,
                'gender_id' => $genderId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'address' => $address,
                'occupation' => $occupation,
                'means_of_identification_id' => $meansOfIdentificationId,
                'identification_number' => $identificationNumber,
                'relationship_to_borrower' => $relationshipToBorrower,
                'guaranteed_amount' => $guarateedAmount,
                'status_id' => 5, // Pending
            ]);

            return $loanData->loan_id;
        }
    }

    public static function loanApproval($loanId, $staffId, $statusId, $reason = null)
    {
        $loan = Loan::findOrFail($loanId);
        $wallet = Wallet::where('user_id', $loan->user_id)->firstOrFail();
        $guarantor = Guarantor::where('loan_id', $loanId)->firstOrFail();

        if ($loan->status_id == 6 || $loan->status_id == 8) {
            throw new \Exception('This loan has already been finalized');
        }

        if ($statusId == 6) {
            DB::transaction(function () use ($loan, $wallet, $staffId, $guarantor) {
                $principalAmount = $loan->principal_amount;
                $interestAmount = $loan->interest_amount;
                $durationMonths = $loan->duration_months;
                $totalPayable = $principalAmount;

                $monthlyPrincipal = round($principalAmount / $durationMonths, 2);
                $monthlyInterest = round($interestAmount / $durationMonths, 2);
                $repaymentAmount = $monthlyPrincipal - $monthlyInterest;
                $monthlyRepayment = $monthlyPrincipal;

                $remainingPrincipal = $principalAmount;
                $startDate = Carbon::parse($loan->approved_at);

                for ($i = 1; $i <= $durationMonths; $i++) {
                    $dueDate = $startDate->copy()->addMonths($i);
                    $remainingPrincipal -= $monthlyPrincipal;
                    LoanRepaymentSchedule::create([
                        'user_id' => $loan->user_id,
                        'loan_id' => $loan->loan_id,
                        'installment_number' => $i,
                        'due_date' => $dueDate,
                        'principal_amount' => $remainingPrincipal,
                        'repayment_amount' => $repaymentAmount,
                        'interest_amount' => $monthlyInterest,
                        'monthly_repayment' => $monthlyRepayment,
                        'status_id' => 22 // UNPAID
                    ]);
                }

                $loan->update([
                    'attended_by' => $staffId,
                    'attended_at' => now(),
                    'status_id' => 6, // approved
                    'disbursed_at' => now(),
                    'is_active_loan' => true,
                ]);

                $wallet->update([
                    'total_saving_amount' => $wallet->total_saving_amount + ($loan->principal_amount - $loan->interest_amount),
                    'outstanding_loan_balance' => $wallet->outstanding_loan_balance + $totalPayable,
                ]);

                $guarantor->update([
                    'status_id' => 6, // Approved
                ]);

                LedgerEntry::create([
                    'user_id' => $loan->user_id,
                    'wallet_id' => $wallet->wallet_id,
                    'entry_type' => 'LOAN_DISBURSEMENT',
                    'amount' => $loan->principal_amount - $loan->interest_amount,
                    'balance_before' => $wallet->total_saving_amount - ($loan->principal_amount - $loan->interest_amount),
                    'balance_after' => $wallet->total_saving_amount,
                    'reference' => $loan->loan_reference,
                    'description' => 'Disbursement of loan amount for loan ID: ' . $loan->loan_id,
                    'transaction_type' => 'CREDIT',
                    'created_by' => $staffId,
                ]);
            });
        } else if ($statusId == 8) {
            $loan->update([
                'attended_by' => $staffId,
                'attended_at' => now(),
                'status_id' => 8, // Rejected
                'rejection_reason' => $reason
            ]);
        } else {
            throw new \Exception('Invalid status ID. Only 1 (approved) or 8 (rejected) are allowed.');
        }
        ClearCacheService::clearListCache('ledger_entries_user_' . $loan->user_id);
    }


    public static function loanRepayment($loanId, $installmentNumber, $userId, $amount, $description = null, $entryType = 'LOAN_REPAYMENT')
    {
        $loan = Loan::findOrFail($loanId);
        $wallet = Wallet::where('user_id', $userId)->firstOrFail();
        $balanceBefore = $wallet->outstanding_loan_balance;
        $balanceAfter = $wallet->outstanding_loan_balance - $amount;
        $loanRepaymentSchedule = LoanRepaymentSchedule::where('loan_id', $loanId)->where('installment_number', $installmentNumber)->where('status_id', 22)->first();

        if ($entryType === 'LOAN_REPAYMENT') {
            if ($amount > $wallet->total_contributions) {
                throw new \Exception('Insufficient contributions balance for loan repayment.');
            }
        }

        if (!LoanRepaymentSchedule::where('loan_id', $loanId)->where('installment_number', $installmentNumber)->where('status_id', 22)->exists()) {
            throw new \Exception('This loan has already been finalized');
        }

        $loanRepaymentSchedule->update([
            'status_id' => 21, // PAID
            'amount_paid' => $amount,
            'processed_by' => Auth::guard('admin')->user()->staff_id,
            'paid_at' => now(),
        ]);

        $wallet->update([
            'outstanding_loan_balance' => $wallet->outstanding_loan_balance - $amount,
            'total_contributions' => $wallet->total_contributions - $amount,
        ]);

        LedgerEntry::create([
            'user_id' => $userId,
            'wallet_id' => $wallet->wallet_id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => uniqid('loan_repayment_'),
            'description' => $description ?? 'Loan repayment for loan ID: ' . $loanId . ', installment: ' . $installmentNumber,
            'transaction_type' => 'DEBIT',
            'created_by' => Auth::guard('admin')->user()->staff_id,
        ]);

        if (!LoanRepaymentSchedule::where('loan_id', $loanId)->where('status_id', 22)->exists()) {
            $loan->update([
                'is_active_loan' => false,
            ]);
        }
        ClearCacheService::clearListCache("ledger_entries_user_{$loan->user_id}");
        ClearCacheService::clearListCache("loan_repayment_schedule_user_{$loan->user_id}");
        // Cache::forget("loan_repayment_schedule_user_{$loan->user_id}");
    }
}
