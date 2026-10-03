<?php

namespace Tests\Feature;

use App\Models\Admin\Loan;
use App\Models\Admin\LoanPolicy;
use App\Models\Admin\MemberContribution;
use App\Models\Admin\MemberContributionSaving;
use App\Models\Admin\MemberSaving;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\Staff;
use App\Models\Admin\Wallet;
use App\Models\Admin\WithdrawalRequest;
use App\Models\Setup\SetupCounter;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EndToEndSystemTest extends TestCase
{
    protected static $testStaff;
    protected static $testMember;
    protected static $testNonMember;
    protected static $staffToken;
    protected static $memberToken;
    protected static $deviceId = 'e2e-device-test-uuid-9999';

    protected function getHeaders(?string $token = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'x-api-key' => config('app.key'),
            'X-Device-ID' => self::$deviceId,
        ];

        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    public function test_01_public_setup_endpoints()
    {
        $stateId = DB::table('setup_states')->value('state_id') ?? 1;
        $countryId = DB::table('setup_countries')->value('country_id') ?? 1;
        $statusId = DB::table('setup_statuses')->value('status_id') ?? 1;

        $endpoints = [
            'country' => [],
            'state' => ['country_id' => $countryId],
            'lga' => ['state_id' => $stateId],
            'gender' => [],
            'title' => [],
            'status' => ['status_id' => [$statusId]],
            'membership-types' => [],
            'payment-channel-types' => [],
            'staff-category' => [],
            'means-of-identification' => [],
        ];

        foreach ($endpoints as $ep => $query) {
            $url = "/api/v1/setup/{$ep}" . (!empty($query) ? '?' . http_build_query($query) : '');
            $response = $this->withHeaders($this->getHeaders())
                ->getJson($url);

            $this->assertContains($response->getStatusCode(), [200, 201], "Setup endpoint {$ep} failed with status " . $response->getStatusCode());
            $this->assertTrue(is_array($response->json()), "Setup endpoint {$ep} did not return valid JSON array");
        }
    }

    public function test_02_admin_and_member_setup_and_auth()
    {
        // 1. Prepare Admin Staff fixture
        $staffEmail = 'e2e_admin_' . time() . '@ajonova.test';
        $staffId = SetupCounter::generateCustomId('STFF');
        
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $allPermissions = Permission::where('guard_name', 'admin')->get();
        if ($allPermissions->isNotEmpty()) {
            $role->syncPermissions($allPermissions);
        }

        self::$testStaff = Staff::create([
            'staff_id' => $staffId,
            'title_id' => 1,
            'first_name' => 'E2E_ADMIN',
            'last_name' => 'TESTER',
            'gender_id' => 1,
            'email' => $staffEmail,
            'mobile_number' => '080' . rand(10000000, 99999999),
            'home_address' => 'E2E HQ Lagos',
            'date_of_birth' => '1990-01-01',
            'lga_id' => 1,
            'nin' => (string) rand(10000000000, 99999999999),
            'status_id' => 1, // Active
            'password' => Hash::make('password123'),
        ]);
        self::$testStaff->assignRole($role);

        // Test Admin Login -> OTP Generation
        $loginRes = $this->withHeaders($this->getHeaders())
            ->postJson('/api/v1/admin/auth/login', [
                'emailAddress' => $staffEmail,
                'password' => 'password123',
            ]);
        $loginRes->assertStatus(200);

        // Fetch OTP and verify
        $otp = '123456';
        DB::table('otps')->updateOrInsert(
            ['user_id' => $staffId],
            [
                'otp_code' => Hash::make($otp),
                'expires_at' => Carbon::now()->addMinutes(10),
                'created_at' => now(),
            ]
        );

        $verifyRes = $this->withHeaders($this->getHeaders())
            ->postJson('/api/v1/admin/auth/verify-login-otp', [
                'emailAddress' => $staffEmail,
                'otpCode' => $otp,
            ]);
        $verifyRes->assertStatus(200);
        self::$staffToken = $verifyRes->json('accessToken');
        $this->assertNotEmpty(self::$staffToken, 'Admin access token was empty');

        // Test Member Registration: Member (membership_type_id = 1)
        $memberEmail = 'e2e_member_' . time() . '@ajonova.test';
        $memberRegRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->postJson('/api/v1/admin/users', [
                'titleId' => 1,
                'staffCategoryId' => 1,
                'membershipTypeId' => 1,
                'firstName' => 'Adebayo',
                'lastName' => 'Olatunji',
                'genderId' => 1,
                'emailAddress' => $memberEmail,
                'mobileNumber' => '081' . rand(10000000, 99999999),
                'homeAddress' => '42 Test Street, Ikeja',
                'monthlySalary' => 350000,
                'contributionAmount' => 25000,
                'savingAmount' => 10000,
                'targetName' => 'New Laptop',
                'targetAmount' => 120000,
                'startDate' => now()->format('Y-m-d'),
                'durationMonths' => 6,
            ]);
        $this->assertContains($memberRegRes->getStatusCode(), [200, 201]);

        self::$testMember = User::where('email', $memberEmail)->first();
        $this->assertNotNull(self::$testMember);
        $this->assertEquals(1, self::$testMember->status_id);

        // Test Non-Member Registration: Non-Member (membership_type_id = 2) without monthly contribution
        $nonMemberEmail = 'e2e_nonmember_' . time() . '@ajonova.test';
        $nonMemberRegRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->postJson('/api/v1/admin/users', [
                'titleId' => 2,
                'staffCategoryId' => 2,
                'membershipTypeId' => 2,
                'firstName' => 'Chinonso',
                'lastName' => 'Eze',
                'genderId' => 1,
                'emailAddress' => $nonMemberEmail,
                'mobileNumber' => '080' . rand(10000000, 99999999),
                'homeAddress' => '15 Victoria Island, Lagos',
                'monthlySalary' => 200000,
                'savingAmount' => 15000,
            ]);
        $this->assertContains($nonMemberRegRes->getStatusCode(), [200, 201]);
        self::$testNonMember = User::where('email', $nonMemberEmail)->first();
        $this->assertNotNull(self::$testNonMember);

        // Verify Member Login & OTP
        $memberLoginRes = $this->withHeaders($this->getHeaders())
            ->postJson('/api/v1/user/auth/login', [
                'emailAddress' => $memberEmail,
                'password' => 'Olatunji123',
            ]);
        $memberLoginRes->assertStatus(200);

        DB::table('otps')->updateOrInsert(
            ['user_id' => self::$testMember->user_id],
            [
                'otp_code' => Hash::make($otp),
                'expires_at' => Carbon::now()->addMinutes(10),
                'created_at' => now(),
            ]
        );

        $memberVerifyRes = $this->withHeaders($this->getHeaders())
            ->postJson('/api/v1/user/auth/verify-login-otp', [
                'emailAddress' => $memberEmail,
                'otpCode' => $otp,
            ]);
        $memberVerifyRes->assertStatus(200);
        self::$memberToken = $memberVerifyRes->json('accessToken');
        $this->assertNotEmpty(self::$memberToken, 'Member access token was empty');
    }

    public function test_03_member_profile_and_admin_metrics()
    {
        // Member fetches own profile
        $profileRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->getJson('/api/v1/user/user-profile');
        $profileRes->assertStatus(200);
        $this->assertEquals(self::$testMember->email, $profileRes->json('data.emailAddress'));

        // Admin fetches profile
        $adminProfRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/fetch-profile');
        $adminProfRes->assertStatus(200);

        // Admin dashboard metrics & charts
        $metricsRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/dashboard-metrics');
        $metricsRes->assertStatus(200);

        $chartRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/dashboard-chart');
        $chartRes->assertStatus(200);

        // Admin user management list
        $userListRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/users');
        $userListRes->assertStatus(200);
        $this->assertNotEmpty($userListRes->json('data'));

        // Admin single user view
        $singleUserRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/users/' . self::$testMember->user_id);
        $singleUserRes->assertStatus(200);
    }

    public function test_04_contributions_and_liquidation_flow()
    {
        // Admin deposits monthly contribution for member
        $depositRes = $this->withHeaders(array_merge($this->getHeaders(self::$staffToken), ['X-User-ID' => self::$testMember->user_id]))
            ->postJson('/api/v1/admin/deposit-contribution', [
                'userId' => self::$testMember->user_id,
                'paymentChannelTypeId' => 1,
            ]);
        $this->assertContains($depositRes->getStatusCode(), [200, 201]);

        // Member checks contributions
        $memberContribRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->getJson('/api/v1/user/member-contributions');
        $memberContribRes->assertStatus(200);

        // Check wallet total contributions
        $wallet = Wallet::where('user_id', self::$testMember->user_id)->first();
        $this->assertGreaterThan(0, (float)$wallet->total_contributions);

        // Member requests contribution withdrawal (Full Liquidation)
        $withdrawRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->postJson('/api/v1/user/withdraw-contribution', [
                'amount' => (float)$wallet->total_contributions,
                'reason' => 'Relocation full liquidation request',
            ]);
        $withdrawRes->assertStatus(200);

        // Admin views pending withdrawal requests
        $reqListRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/withdrawal-requests');
        $reqListRes->assertStatus(200);

        $latestReq = WithdrawalRequest::where('user_id', self::$testMember->user_id)->orderBy('withdrawal_request_id', 'desc')->first();
        $this->assertNotNull($latestReq);

        // Admin approves withdrawal request (status_id = 6 for approved)
        $approvalRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->postJson('/api/v1/admin/contribution-withdrawal-approval/' . $latestReq->withdrawal_request_id, [
                'statusId' => 6,
            ]);
        $this->assertContains($approvalRes->getStatusCode(), [200, 201]);
    }

    public function test_05_savings_and_target_savings_flow()
    {
        // Deposit voluntary savings
        $saveDepositRes = $this->withHeaders(array_merge($this->getHeaders(self::$staffToken), ['X-User-ID' => self::$testMember->user_id]))
            ->postJson('/api/v1/admin/deposit-savings', [
                'userId' => self::$testMember->user_id,
                'paymentChannelTypeId' => 1,
            ]);
        $this->assertContains($saveDepositRes->getStatusCode(), [200, 201]);

        // Member checks savings
        $memberSavingsRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->getJson('/api/v1/user/member-savings');
        $memberSavingsRes->assertStatus(200);

        // Deposit target savings
        $targetDepositRes = $this->withHeaders(array_merge($this->getHeaders(self::$staffToken), ['X-User-ID' => self::$testMember->user_id]))
            ->postJson('/api/v1/admin/deposit-target-savings', [
                'userId' => self::$testMember->user_id,
                'paymentChannelTypeId' => 1,
            ]);
        $this->assertContains($targetDepositRes->getStatusCode(), [200, 201]);

        // Member checks target savings
        $memberTargetRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->getJson('/api/v1/user/member-target-savings');
        $memberTargetRes->assertStatus(200);
    }

    public function test_06_loan_lifecycle_and_repayment()
    {
        // Admin checks loan policies
        $policiesRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/loan-policies');
        $policiesRes->assertStatus(200);

        // Ensure at least one active loan policy exists
        $policy = LoanPolicy::firstOrCreate(
            ['loan_policy_id' => 1],
            [
                'loan_multiplier' => 10,
                'minimum_amount' => 1000,
                'maximum_amount' => 1000000,
                'min_duration_months' => 1,
                'max_duration_months' => 12,
                'interest_rate' => 5,
                'processing_fee' => 1,
                'penalty_rate' => 2,
                'eligibility_months' => 0,
                'allow_multiple_loans' => true,
                'status_id' => 1,
            ]
        );
        $policy->update([
            'loan_multiplier' => 10,
            'minimum_amount' => 1000,
            'maximum_amount' => 1000000,
            'min_duration_months' => 1,
            'max_duration_months' => 12,
            'eligibility_months' => 0,
            'allow_multiple_loans' => true,
            'status_id' => 1,
        ]);

        // Ensure user contribution and savings are sufficient for loan
        Wallet::where('user_id', self::$testMember->user_id)->update([
            'total_saving_amount' => 100000,
            'total_contributions' => 50000,
            'outstanding_loan_balance' => 0,
        ]);
        MemberContributionSaving::where('user_id', self::$testMember->user_id)->update([
            'contribution_amount' => 25000,
            'saving_amount' => 10000,
        ]);

        // Member applies for loan
        $loanApplyRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->postJson('/api/v1/user/apply-loan', [
                'principalAmount' => 50000,
                'titleId' => 1,
                'genderId' => 1,
                'firstName' => 'Tunde',
                'lastName' => 'Bakare',
                'middleName' => 'Ade',
                'phoneNumber' => '08033334444',
                'email' => 'tunde.bakare@ajonova.test',
                'address' => '12 Marina Road, Lagos',
                'occupation' => 'Architect',
                'meansOfIdentificationId' => 1,
                'identificationNumber' => 'NIN12345678901',
                'relationshipToBorrower' => 'Colleague',
                'guaranteedAmount' => 50000,
            ]);
        $this->assertContains($loanApplyRes->getStatusCode(), [200, 201]);

        // Admin views loans
        $allLoansRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/all-loans');
        $allLoansRes->assertStatus(200);

        $latestLoan = Loan::where('user_id', self::$testMember->user_id)->latest()->first();
        $this->assertNotNull($latestLoan);

        // Admin approves loan (status_id = 6 for Approved)
        $approveLoanRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->postJson('/api/v1/admin/approve-loan/' . $latestLoan->loan_id, [
                'statusId' => 6,
            ]);
        $this->assertContains($approveLoanRes->getStatusCode(), [200, 201]);

        // Verify repayment schedules generated
        $scheduleRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/loan-repayment-schedule?loan_id=' . $latestLoan->loan_id);
        $scheduleRes->assertStatus(200);

        // Process a loan repayment
        $repayRes = $this->withHeaders(array_merge($this->getHeaders(self::$staffToken), [
            'X-Loan-ID' => (string)$latestLoan->loan_id,
            'X-User-ID' => self::$testMember->user_id,
            'X-Installment-Number' => '1',
            'X-Amount' => '10000',
        ]))->postJson('/api/v1/admin/loan-repayment', [
            'loanId' => $latestLoan->loan_id,
            'userId' => self::$testMember->user_id,
            'installmentNumber' => 1,
            'amount' => 10000,
            'paymentChannelTypeId' => 1,
        ]);
        $this->assertContains($repayRes->getStatusCode(), [200, 201]);
    }

    public function test_07_reports_and_activity_logs()
    {
        // Member financial report
        $memberReportRes = $this->withHeaders($this->getHeaders(self::$memberToken))
            ->getJson('/api/v1/user/report');
        $memberReportRes->assertStatus(200);

        // Admin report
        $adminReportRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/report');
        $adminReportRes->assertStatus(200);

        // Activity logs
        $logsRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/activity-logs');
        $logsRes->assertStatus(200);

        $unreadRes = $this->withHeaders($this->getHeaders(self::$staffToken))
            ->getJson('/api/v1/admin/activity-logs/unread-count');
        $unreadRes->assertStatus(200);

        // Clean up test data safely inside the test
        if (self::$testMember) {
            $userId = self::$testMember->user_id;
            DB::table('personal_access_tokens')->where('tokenable_id', $userId)->delete();
            DB::table('otps')->where('user_id', $userId)->delete();
            DB::table('user_devices')->where('user_id', $userId)->delete();
            DB::table('member_contributions')->where('user_id', $userId)->delete();
            DB::table('member_contribution_savings')->where('user_id', $userId)->delete();
            DB::table('member_savings')->where('user_id', $userId)->delete();
            DB::table('member_target_savings')->where('user_id', $userId)->delete();
            DB::table('member_target_saving_settings')->where('user_id', $userId)->delete();
            DB::table('withdrawal_requests')->where('user_id', $userId)->delete();
            DB::table('loan_repayment_schedules')->whereIn('loan_id', function ($query) use ($userId) {
                $query->select('loan_id')->from('loans')->where('user_id', $userId);
            })->delete();
            DB::table('guarantors')->whereIn('loan_id', function ($query) use ($userId) {
                $query->select('loan_id')->from('loans')->where('user_id', $userId);
            })->delete();
            DB::table('loans')->where('user_id', $userId)->delete();
            DB::table('ledger_entries')->where('user_id', $userId)->delete();
            DB::table('wallets')->where('user_id', $userId)->delete();
            DB::table('users')->where('user_id', $userId)->delete();
        }

        if (self::$testNonMember) {
            $nonUserId = self::$testNonMember->user_id;
            DB::table('personal_access_tokens')->where('tokenable_id', $nonUserId)->delete();
            DB::table('otps')->where('user_id', $nonUserId)->delete();
            DB::table('user_devices')->where('user_id', $nonUserId)->delete();
            DB::table('member_contribution_savings')->where('user_id', $nonUserId)->delete();
            DB::table('wallets')->where('user_id', $nonUserId)->delete();
            DB::table('users')->where('user_id', $nonUserId)->delete();
        }

        if (self::$testStaff) {
            $staffId = self::$testStaff->staff_id;
            DB::table('personal_access_tokens')->where('tokenable_id', $staffId)->delete();
            DB::table('otps')->where('user_id', $staffId)->delete();
            DB::table('user_devices')->where('user_id', $staffId)->delete();
            self::$testStaff->delete();
        }
    }
}
