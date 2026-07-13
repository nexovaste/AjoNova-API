<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LgaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'localGovernmentId' => $this->lga_id,
            'localGovernmentName' => $this->lga_name,
            'state' => [
                'stateId' => $this->state_id ?? null,
                'stateName' => $this->state->state_name ?? null,
            ]
        ];
    }
}
