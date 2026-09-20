<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->user_id,
            'membershipNumber' => $this->membership_number,
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'lastName' => $this->last_name,
            'emailAddress' => $this->email,
            'mobileNumber' => $this->mobile_number,
            'homeAddress' => $this->home_address,
            'dateOfBirth' => $this->date_of_birth,
            'nin' => $this->nin,
            'monthlySalary' => $this->monthly_salary,
            'dateJoined' => $this->date_joined? Carbon::parse($this->date_joined)->format('d M Y'): null,
            'dateExited' => $this->date_exited? Carbon::parse($this->date_exited)->format('d M Y'): null,
            'lastLoginAt' => Carbon::parse($this->last_login_at)->diffForHumans(),
            'createdAt' => Carbon::parse($this->created_at)->toDayDateTimeString(),
            'updatedAt' => Carbon::parse($this->updated_at)->toDayDateTimeString(),
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'title' => [
                'titleId' => $this->title_id ?? null,
                'titleName' => $this->relationLoaded('title') ? $this->title?->title_name : (\Illuminate\Support\Facades\DB::table('titles')->where('title_id', $this->title_id)->value('title_name') ?? 'Mr'),
            ],
            'staffCategory' => [
                'staffCategoryId' => $this->staff_category_id ?? null,
                'staffCategoryName' => $this->relationLoaded('staffCategory') ? $this->staffCategory?->staff_category_name : (\Illuminate\Support\Facades\DB::table('staff_categories')->where('staff_category_id', $this->staff_category_id)->value('staff_category_name') ?? 'Staff'),
            ],
            'membershipType' => [
                'membershipTypeId' => $this->membership_type_id ?? null,
                'membershipTypeName' => $this->relationLoaded('membershipType') ? $this->membershipType?->membership_type_name : (\Illuminate\Support\Facades\DB::table('membership_types')->where('membership_type_id', $this->membership_type_id)->value('membership_type_name') ?? 'Member'),
            ],
            'gender' => [
                'genderId' => $this->gender_id ?? null,
                'genderName' => $this->relationLoaded('gender') ? $this->gender?->gender_name : (\Illuminate\Support\Facades\DB::table('genders')->where('gender_id', $this->gender_id)->value('gender_name') ?? 'Male'),
            ],
            'status' => [
                'statusId' => $this->status_id ?? null,
                'statusName' => $this->relationLoaded('status') ? $this->status?->status_name : (\Illuminate\Support\Facades\DB::table('statuses')->where('status_id', $this->status_id)->value('status_name') ?? 'Active'),
            ],
            'location' => [
                'lgaId' => $this->lga_id ?? null,
                'lgaName' => $this->relationLoaded('lga') ? $this->lga?->lga_name : null,
                'stateId' => $this->relationLoaded('lga') ? $this->lga?->state_id : null,
                'stateName' => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state')) ? $this->lga?->state?->state_name : null,
                'countryId' => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state')) ? $this->lga?->state?->country_id : null,
                'countryName' => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state') && $this->lga?->state?->relationLoaded('country')) ? $this->lga?->state?->country?->country_name : null,
            ],
            'passport' => [
                'passportUrl' => $this->passport ? Storage::url("passports/userPictures/{$this->passport}") : null
            ],
            'wallet' => [
                'totalContributions' => (float) (
                    max(
                        (float) ($this->relationLoaded('wallet')
                            ? ($this->wallet?->total_contributions ?? 0)
                            : (\Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $this->user_id)->value('total_contributions') ?? 0)),
                        (float) (\Illuminate\Support\Facades\DB::table('member_contributions')->where('user_id', $this->user_id)->sum('contribution_amount') ?? 0)
                    )
                ),
                'totalSavingAmount' => $totalSavingAmount = (float) (
                    $this->relationLoaded('wallet')
                        ? ($this->wallet?->total_saving_amount ?? 0)
                        : (\Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $this->user_id)->value('total_saving_amount') ?? 0)
                ),
                'lockedBalance' => $lockedBalance = (float) (
                    $this->relationLoaded('wallet')
                        ? ($this->wallet?->locked_balance ?? 0)
                        : (\Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $this->user_id)->value('locked_balance') ?? 0)
                ),
                'availableBalance' => (float) max(0, $totalSavingAmount - $lockedBalance),
                'totalTargetAmount' => (float) (
                    $this->relationLoaded('wallet')
                        ? ($this->wallet?->total_target_amount ?? 0)
                        : (\Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $this->user_id)->value('total_target_amount') ?? 0)
                ),
                'outstandingLoanBalance' => (float) (
                    $this->relationLoaded('wallet')
                        ? ($this->wallet?->outstanding_loan_balance ?? 0)
                        : (\Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $this->user_id)->value('outstanding_loan_balance') ?? 0)
                ),
            ],
        ];
    }
}
