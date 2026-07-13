<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class ClearCacheService
{
    // Flush all cached cursor pages for staff list.
    public static function clearListCache($key): void
    {
        Cache::tags($key)->flush();
    }
}
    