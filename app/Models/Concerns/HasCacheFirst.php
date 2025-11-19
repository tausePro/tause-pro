<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasCacheFirst
{
    public static function getCache()
    {
        // Verificar si estamos en modo instalación ANTES de cualquier consulta
        try {
            $isInstallationRoute = request()->is('install*') || request()->is('upgrade*') || request()->is('update*');
            if ($isInstallationRoute) {
                return null; // Durante instalación, siempre retornar null
            }
        } catch (\Exception $e) {
            // Si request() no está disponible, asumir instalación
            return null;
        }

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
