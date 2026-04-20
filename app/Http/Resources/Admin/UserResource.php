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
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'lastName' => $this->last_name,
            'emailAddress' => $this->email,
            'mobileNumber' => $this->mobile_number,
            'homeAddress' => $this->home_address,
            'dateOfBirth' => $this->date_of_birth,
            'nin' => $this->nin,
            'lastLoginAt' => Carbon::parse($this->last_login_at)->diffForHumans(),
            'createdAt' => Carbon::parse($this->created_at)->toDayDateTimeString(),
            'updatedAt' => Carbon::parse($this->updated_at)->toDayDateTimeString(),
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'title' => [
                'titleId' => $this->title_id ?? null,
                'titleName' => $this->title->title_name ?? null,
            ],
            'staffCategory' =>[
                'staffCategoryId' => $this->staff_category_id ?? null,
                'staffCategoryName' => $this->staffCategory->staff_category_name ?? null,
            ],
            'membershipType' => [
                'membershipTypeId' => $this->membership_type_id ?? null,
                'membershipTypeName' => $this->membershipType->membership_type_name ?? null,
            ],
            'gender' => [
                'genderId' => $this->gender_id ?? null,
                'genderName' => $this->gender->gender_name ?? null,
            ],
            'status' => [
                'statusId' => $this->status_id ?? null,
                'statusName' => $this->status->status_name ?? null,
            ],
            'location' => [
                'lgaId' => $this->lga_id ?? null,
                'lgaName' => $this->lga->lga_name ?? null,
                'stateId' => $this->lga->state_id ?? null,
                'stateName' => $this->lga->state->state_name ?? null,
                'countryId' => $this->lga->state->country_id ?? null,
                'countryName' => $this->lga->state->country->country_name ?? null,
            ],
            'passport' => [
                'passportUrl' => $this->passport ? Storage::url("passports/userPictures/{$this->passport}") : null
            ],
        ];
    }
}
