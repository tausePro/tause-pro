<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class TableSchema
{
    public static function hasTable(string $table, array $tables): bool
    {
        return in_array($table, $tables, true);
    }

    public function allTables(): array
    {
        // Verificar si estamos en modo instalación ANTES de consultar BD
        try {
            $isInstallationRoute = request()->is('install*') || request()->is('upgrade*') || request()->is('update*');
            if ($isInstallationRoute) {
                return []; // Durante instalación, retornar array vacío
            }
        } catch (\Exception $e) {
            // Si request() no está disponible, asumir instalación
            return [];
        }

        // Verificar si BD está disponible ANTES de consultar
        try {
            if (!\App\Helpers\Classes\Helper::dbConnectionStatus()) {
                return []; // BD no disponible, retornar array vacío
            }
        } catch (\Exception $e) {
            return []; // Si falla la verificación, retornar array vacío
        }

        try {
            return once(static function () {
                return Arr::pluck(Schema::getTables(), 'name');
            });
        } catch (\Exception $e) {
            // Si falla la consulta, retornar array vacío
            return [];
        }
    }
}
