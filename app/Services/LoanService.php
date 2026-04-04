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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{

    public static function applyLoan(
        $userId,
        $durationMonths,
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

        if ($loanPolicy->minimum_amount > $principalAmount) {
            throw new \Exception('Principal amount is less than the minimum allowed by the loan policy.');
        } else if ($loanPolicy->maximum_amount && $principalAmount > $loanPolicy->maximum_amount) {
            throw new \Exception('Principal amount exceeds the maximum allowed by the loan policy.');
        } else if ($durationMonths < $loanPolicy->min_duration_months || $durationMonths > $loanPolicy->max_duration_months) {
            throw new \Exception('Duration of the loan must be between ' . $loanPolicy->min_duration_months . ' and ' . $loanPolicy->max_duration_months . ' months.');
        } else if ($userPolicy->contribution_amount * $loanPolicy->loan_multiplier < $principalAmount) {
            throw new \Exception('Principal amount exceeds the maximum allowed based on your monthly contributions and the loan multiplier.');
        } else if (count($memberContributions) < $loanPolicy->eligibility_months) {
            throw new \Exception('You are not eligible to apply for a loan based on your contribution history and the loan policy requirements.');
        } else if (!$loanPolicy->allow_multiple_loans && Loan::where('user_id', $userId)->whereIn('status_id', [1])->exists()) {
            throw new \Exception('You already have an active or pending loan. Multiple loans are not allowed based on the current loan policy.');
        } else if (Loan::where('user_id', $userId)->whereIn('status_id', [5])->exists()) {
            throw new \Exception('You have a pending loan application. Please wait for it to be processed before applying for another loan.');
        } else if ($principalAmount != $guarateedAmount) {
            throw new \Exception('The guaranteed amount must be equal to the loan amount.');
        } else {

            $loanId = SetupCounter::generateCustomId('LN');
            $loanData = Loan::create([
                'loan_id' => $loanId,
                'user_id' => $userId,
                'duration_months' => $durationMonths,
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

    public static function loanApproval($loanId, $staffId, $statusId)
    {
        $loan = Loan::findOrFail($loanId);
        $wallet = Wallet::where('user_id', $loan->user_id)->firstOrFail();
        $guarantor = Guarantor::where('loan_id', $loanId)->firstOrFail();

        
        if ($loan->status_id != 5) {
            throw new \Exception('Only pending loans can be approved.');
        }

        if ($loan->status_id == 6 || $loan->status_id == 8) {
            throw new \Exception('This loan has already been finalized');
        }

        if ($statusId == 6) {
            DB::transaction(function () use ($loan, $wallet, $staffId, $guarantor) {
                $principalAmount = $loan->principal_amount;
                $interestAmount = $loan->interest_amount;
                $durationMonths = $loan->duration_months;
                $totalPayable = $principalAmount + $interestAmount;

                $monthlyRepayment = round($totalPayable / $durationMonths, 2);
                $monthlyPrincipal = round($principalAmount / $durationMonths, 2);
                $monthlyInterest = round($interestAmount / $durationMonths, 2);

                $startDate = Carbon::parse($loan->approved_at);

                for ($i = 1; $i <= $durationMonths; $i++) {
                    $dueDate = $startDate->copy()->addMonths($i);
                    LoanRepaymentSchedule::create([
                        'loan_id' => $loan->loan_id,
                        'installment_number' => $i,
                        'due_date' => $dueDate,
                        'principal_amount' => $monthlyPrincipal,
                        'interest_amount' => $monthlyInterest,
                        'total_due' => $monthlyRepayment,
                        'status_id' => 22 // UNPAID
                    ]);
                }

                $loan->update([
                    'attended_by' => $staffId,
                    'attended_at' => now(),
                    'status_id' => 6, // approved
                ]);

                $wallet->update([
                    'total_saving_amount' => $wallet->total_saving_amount + $loan->principal_amount,
                    'outstanding_loan_amount' => $wallet->outstanding_loan_amount + $totalPayable,
                ]);

                $guarantor->update([
                    'status_id' => 6, // Approved
                ]);

                LedgerEntry::create([
                    'user_id' => $loan->user_id,
                    'wallet_id' => $wallet->wallet_id,
                    'entry_type' => 'LOAN_DISBURSEMENT',
                    'amount' => $loan->principal_amount,
                    'balance_before' => $wallet->total_saving_amount,
                    'balance_after' => $wallet->total_saving_amount + $loan->principal_amount,
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
            ]);
        } else {
            throw new \Exception('Invalid status ID. Only 1 (approved) or 8 (rejected) are allowed.');
        }
    }
}
