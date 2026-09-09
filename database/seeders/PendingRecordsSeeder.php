<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User\User;
use App\Models\Admin\Loan;
use App\Models\Admin\Guarantor;
use App\Models\Admin\MemberSaving;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\WithdrawalRequest;

class PendingRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            echo "No users found in database to attach pending records to.\n";
            return;
        }

        // 1. Seed 3 Pending Loans with Guarantors
        $loanSamples = [
            [
                'loan_id' => 'LN-' . rand(100000, 999999),
                'principal' => 150000.00,
                'interest' => 7500.00,
                'duration' => 6,
                'guarantors' => [
                    [
                        'first_name' => 'Adewale',
                        'last_name' => 'Ogunlesi',
                        'middle_name' => 'Samuel',
                        'phone' => '08023456789',
                        'email' => 'adewale.o@gmail.com',
                        'address' => '12 Commercial Avenue, Yaba, Lagos',
                        'occupation' => 'Civil Servant',
                        'id_num' => 'NIN-98765432101',
                        'rel' => 'Colleague / Friend',
                        'amount' => 75000.00
                    ],
                    [
                        'first_name' => 'Bisi',
                        'last_name' => 'Akande',
                        'middle_name' => 'Mary',
                        'phone' => '08034567890',
                        'email' => 'bisi.akande@yahoo.com',
                        'address' => '45 Allen Avenue, Ikeja, Lagos',
                        'occupation' => 'Senior Accountant',
                        'id_num' => 'DL-98765432',
                        'rel' => 'Family Relation',
                        'amount' => 75000.00
                    ]
                ]
            ],
            [
                'loan_id' => 'LN-' . rand(100000, 999999),
                'principal' => 300000.00,
                'interest' => 15000.00,
                'duration' => 12,
                'guarantors' => [
                    [
                        'first_name' => 'Chidi',
                        'last_name' => 'Okonkwo',
                        'middle_name' => 'Emmanuel',
                        'phone' => '08045678901',
                        'email' => 'chidi.okonkwo@outlook.com',
                        'address' => '8 Marina Street, Lagos Island',
                        'occupation' => 'Lecturer',
                        'id_num' => 'NIN-87654321092',
                        'rel' => 'Departmental Staff',
                        'amount' => 150000.00
                    ],
                    [
                        'first_name' => 'Folake',
                        'last_name' => 'Adeyemi',
                        'middle_name' => 'Grace',
                        'phone' => '08056789012',
                        'email' => 'folake.a@gmail.com',
                        'address' => '22 Broad Street, Lagos',
                        'occupation' => 'Bank Officer',
                        'id_num' => 'PASSPORT-A09876543',
                        'rel' => 'Business Partner',
                        'amount' => 150000.00
                    ]
                ]
            ],
            [
                'loan_id' => 'LN-' . rand(100000, 999999),
                'principal' => 500000.00,
                'interest' => 25000.00,
                'duration' => 18,
                'guarantors' => [
                    [
                        'first_name' => 'Ibrahim',
                        'last_name' => 'Musa',
                        'middle_name' => 'Bello',
                        'phone' => '08067890123',
                        'email' => 'ibrahim.musa@gmail.com',
                        'address' => '15 Victoria Island Way, Lagos',
                        'occupation' => 'IT Consultant',
                        'id_num' => 'NIN-76543210983',
                        'rel' => 'Cooperative Member',
                        'amount' => 250000.00
                    ],
                    [
                        'first_name' => 'Kemi',
                        'last_name' => 'Salami',
                        'middle_name' => 'Elizabeth',
                        'phone' => '08078901234',
                        'email' => 'kemi.salami@yahoo.com',
                        'address' => '30 Lekki Phase 1, Lagos',
                        'occupation' => 'Medical Practitioner',
                        'id_num' => 'DL-87654321',
                        'rel' => 'Sister / Relative',
                        'amount' => 250000.00
                    ]
                ]
            ]
        ];

        foreach ($loanSamples as $index => $item) {
            try {
                $user = $users[$index % count($users)];
                $loan = Loan::create([
                    'loan_id' => $item['loan_id'],
                    'user_id' => $user->user_id,
                    'duration_months' => $item['duration'],
                    'principal_amount' => $item['principal'],
                    'interest_amount' => $item['interest'],
                    'loan_reference' => 'REF-' . $item['loan_id'],
                    'requested_at' => now()->subDays($index * 2),
                    'status_id' => 5, // PENDING
                    'is_active_loan' => false
                ]);

                foreach ($item['guarantors'] as $g) {
                    Guarantor::create([
                        'loan_id' => $loan->loan_id,
                        'title_id' => 1,
                        'gender_id' => 1,
                        'first_name' => $g['first_name'],
                        'last_name' => $g['last_name'],
                        'middle_name' => $g['middle_name'],
                        'phone_number' => $g['phone'],
                        'email' => $g['email'],
                        'address' => $g['address'],
                        'occupation' => $g['occupation'],
                        'means_of_identification_id' => 1,
                        'id_number' => $g['id_num'],
                        'relationship_to_borrower' => $g['rel'],
                        'guaranteed_amount' => $g['amount'],
                        'status_id' => 5 // PENDING
                    ]);
                }
            } catch (\Exception $e) {
                // Ignore exception
            }
        }

        // 2. Seed 3 Pending Compulsory Savings
        $compulsoryAmounts = [25000.00, 40000.00, 60000.00];
        foreach ($compulsoryAmounts as $index => $amt) {
            try {
                $user = $users[$index % count($users)];
                MemberSaving::create([
                    'user_id' => $user->user_id,
                    'saving_amount' => $amt,
                    'saving_date' => now()->subMonths($index + 2),
                    'payment_channel_type_id' => 1,
                    'reference' => 'SAV-' . rand(100000, 999999),
                    'status_id' => 5, // PENDING
                ]);
            } catch (\Exception $e) {
                // Ignore duplicate period exception
            }
        }

        // 3. Seed 3 Pending Target Savings
        $targetAmounts = [50000.00, 100000.00, 150000.00];
        foreach ($targetAmounts as $index => $amt) {
            try {
                $user = $users[$index % count($users)];
                MemberTargetSaving::create([
                    'user_id' => $user->user_id,
                    'member_target_saving_setting_id' => 1,
                    'monthly_amount' => $amt / 5,
                    'current_amount' => $amt,
                    'payment_channel_type_id' => 1,
                    'reference' => 'TGT-' . rand(100000, 999999),
                    'status_id' => 5, // PENDING
                ]);
            } catch (\Exception $e) {
                // Ignore duplicate active target saving for same user/setting
            }
        }

        // 4. Seed 3 Pending Withdrawal Requests
        $withdrawalTypes = ['CONTRIBUTION_WITHDRAWAL', 'SAVINGS_WITHDRAWAL', 'TARGET_SAVINGS_WITHDRAWAL'];
        $withdrawalAmounts = [85000.00, 120000.00, 200000.00];
        $banks = ['Access Bank', 'GTBank', 'Zenith Bank'];
        $accounts = ['2213405762', '0123456789', '5678901234'];

        foreach ($withdrawalAmounts as $index => $amt) {
            try {
                $user = $users[$index % count($users)];
                $userName = $user->first_name . ' ' . $user->last_name;
                $bank = $banks[$index % count($banks)];
                $accNum = $accounts[$index % count($accounts)];

                $bankHeader = "<div style=\"background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:16px;\"><h5 style=\"margin:0 0 6px 0; font-size:11px; font-weight:bold; color:#023a68; text-transform:uppercase; letter-spacing:0.5px;\">BANK DETAILS FOR PAYOUT</h5><p style=\"margin:0; font-size:13px; color:#1e293b;\"><strong>Account Name:</strong> {$userName} &nbsp;|&nbsp; <strong>Bank:</strong> {$bank} &nbsp;|&nbsp; <strong>Account Number:</strong> <span style=\"font-family:monospace; font-weight:bold;\">{$accNum}</span></p></div>";

                $reasonLetter = $bankHeader . "<h4 style=\"margin-bottom:6px; font-weight:bold; color:#023a68;\">OFFICIAL APPLICATION FOR WITHDRAWAL</h4><p style=\"margin-bottom:8px; font-size:12px; color:#555;\"><strong>Date:</strong> " . now()->format('d F Y') . "</p><p style=\"margin-bottom:8px;\"><strong>To:</strong> The Executive Board / Management Committee</p><p style=\"margin-bottom:10px;\">Dear Sir/Madam,</p><p style=\"margin-bottom:10px;\">I am writing to formally request the withdrawal and payout of my accumulated cooperative balance for emergency personal financial commitments.</p><p style=\"margin-bottom:4px;\">Thank you for your prompt assistance.</p><p><em>Sincerely,<br><strong>{$userName}</strong></em></p>";

                WithdrawalRequest::create([
                    'user_id' => $user->user_id,
                    'withdrawal_type' => $withdrawalTypes[$index % count($withdrawalTypes)],
                    'amount' => $amt,
                    'status_id' => 5, // PENDING
                    'reason' => $reasonLetter,
                    'withdraw_at' => now()->subDays($index * 2),
                ]);
            } catch (\Exception $e) {
                // Ignore exception
            }
        }

        echo "Pending records successfully seeded!\n";
    }
}
