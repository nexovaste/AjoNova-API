<?php

namespace App\Services;

use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;
use Stevebauman\Location\Facades\Location;

class Config
{
    public static function requestDetails(): array
    {
        $agent = new Agent();
        $location = Location::get(request()->ip());
        return [
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'response_code' => http_response_code(),
            'ip_address' => request()->ip(),
            'device' => $agent->device() ?: 'Unknown',
            'browser' => $agent->browser() ?: 'Unknown',
            'platform' => $agent->platform() ?: 'Unknown',
            'is_mobile' => $agent->isMobile() ?: 'Unknown',
            'location' => $location ? $location->cityName . ', ' . $location->countryName : 'Unknown',
        ];
    }

    public static function getTitleNameById(int $titleId): string
    {
        return DB::table('setup_titles')->where('title_id', $titleId)->value('title_name');
    }

    
}
