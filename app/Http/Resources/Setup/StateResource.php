<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stateId' => $this->state_id,
            'stateName' => $this->state_name,
            'country' => [
                'countryId' => $this->country_id ?? null,
                'countryName' => $this->country->country_name ?? null,
            ],
        ];
    }
}
