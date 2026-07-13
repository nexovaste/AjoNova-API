<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuarantorResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
            'relationship' => $this->relationship_to_borrower,
            'guaranteedAmount' => $this->guaranteed_amount,
            'identificationNumber' => $this->id_number,
            'title' => [
                'titleId' => $this->title_id ?? null,
                'titleName' => $this->title->title_name ?? null,
            ],
            'gender' => [
                'genderId' => $this->gender_id ?? null,
                'genderName' => $this->gender->gender_name ?? null,
            ],
              'meansOfIdentification' => [
                'meansOfIdentificationId' => $this->means_of_identification_id ?? null,
                'meansOfIdentificationName' => $this->meansOfIdentification->means_of_identification_name ?? null,
            ]
        ];
    }
}
