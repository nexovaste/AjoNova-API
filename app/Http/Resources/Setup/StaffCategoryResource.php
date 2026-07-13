<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffCategoryResource  extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'staffCategoryId' => $this->staff_category_id,
            'staffCategoryName' => $this->staff_category_name,
        ];
    }
}
