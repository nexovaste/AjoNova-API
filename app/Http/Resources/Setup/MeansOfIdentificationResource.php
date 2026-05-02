<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeansOfIdentificationResource  extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'meansOfIdentificationId' => $this->means_of_identification_id,
            'meansOfIdentificationName' => $this->means_of_identification_name,
        ];
    }
}
