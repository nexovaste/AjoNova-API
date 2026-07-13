<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'countryId' => $this->country_id,
            'countryName' => $this->country_name,
            'countryCode' => $this->country_code,
        ];
    }
}
