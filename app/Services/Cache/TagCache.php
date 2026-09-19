<?php

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

class TagCache
{
    protected static function makeKey($tags, string $key): string
    {
        $prefix = is_array($tags) ? implode("_", $tags) : (string) $tags;
        return "{$prefix}_{$key}";
    }

    public static function rememberForever($tags, string $key, Closure $callback)
    {
        if (Cache::supportsTags()) {
            return Cache::tags($tags)->rememberForever($key, $callback);
        }

        return Cache::rememberForever(self::makeKey($tags, $key), $callback);
    }

    public static function remember($tags, string $key, $ttl, Closure $callback)
    {
        if (Cache::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember(self::makeKey($tags, $key), $ttl, $callback);
    }

    public static function flexible($tags, string $key, $ttl, Closure $callback)
    {
        if (Cache::supportsTags()) {
            return Cache::tags($tags)->flexible($key, $ttl, $callback);
        }

        $seconds = is_array($ttl) ? ($ttl[0] ?? 3600) : $ttl;
        return Cache::remember(self::makeKey($tags, $key), $seconds, $callback);
    }

    public static function flush($tags): void
    {
        if (Cache::supportsTags()) {
            Cache::tags($tags)->flush();
            return;
        }

        $tagList = is_array($tags) ? $tags : [$tags];
        foreach ($tagList as $tag) {
            Cache::forget($tag);
        }
    }

    public static function forget($tags, string $key): void
    {
        if (Cache::supportsTags()) {
            Cache::tags($tags)->forget($key);
            return;
        }

        Cache::forget(self::makeKey($tags, $key));
    }
}

