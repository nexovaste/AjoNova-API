<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title_id' => $this->title_id,
            'title_name' => $this->title_name,
        ];
    }
}
