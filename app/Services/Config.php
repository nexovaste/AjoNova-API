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
        $ip = request()->ip();
        $isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost']) || str_starts_with($ip ?? '', '192.168.') || str_starts_with($ip ?? '', '10.') || str_starts_with($ip ?? '', '172.');
        $location = !$isLocal ? Location::get($ip) : null;
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
