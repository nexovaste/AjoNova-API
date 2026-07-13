<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentChannelTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'paymentChannelTypeId' => $this->payment_channel_type_id,
            'paymentChannelTypeName' => $this->payment_channel_type_name,
        ];
    }
}
