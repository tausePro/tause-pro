<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasCacheFirst
{
    public static function getCache()
    {
        try {
            return Cache::remember(self::$cacheKey, self::$cacheTtl, static function () {
                return self::query()->first();
            });
        } catch (\Exception $e) {
            // Si la BD no está disponible o hay error de conexión, retornar null
            return null;
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::$cacheKey);
    }
}
