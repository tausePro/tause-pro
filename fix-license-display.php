#!/usr/bin/env php
<?php

/**
 * Script para arreglar visualización de extensiones licenciadas
 * Modifica el método licensedExtension() para usar la DB local primero
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 ARREGLANDO VISUALIZACIÓN DE EXTENSIONES LICENCIADAS\n";
echo "======================================================\n\n";

// 1. Verificar extensiones licenciadas en DB local
$licensed = \App\Models\Extension::where('licensed', true)->where('is_theme', false)->get();
echo "✅ Extensiones licenciadas en DB local: " . $licensed->count() . "\n\n";

// 2. Verificar que la API también las reconoce
try {
    $repo = app(\App\Domains\Marketplace\Repositories\Contracts\ExtensionRepositoryInterface::class);
    $apiExtensions = $repo->supportExtensions();
    
    $apiLicensed = collect($apiExtensions)->filter(fn($e) => $e['licensed'] ?? false);
    echo "✅ Extensiones licenciadas en API: " . $apiLicensed->count() . "\n\n";
    
    // 3. Mostrar extensiones que están en DB pero no en API
    $localSlugs = $licensed->pluck('slug')->toArray();
    $apiSlugs = $apiLicensed->pluck('slug')->toArray();
    
    $onlyInLocal = array_diff($localSlugs, $apiSlugs);
    $onlyInApi = array_diff($apiSlugs, $localSlugs);
    
    if (!empty($onlyInLocal)) {
        echo "⚠️  Extensiones en DB local pero NO en API:\n";
        foreach ($onlyInLocal as $slug) {
            echo "   - $slug\n";
        }
        echo "\n";
    }
    
    if (!empty($onlyInApi)) {
        echo "⚠️  Extensiones en API pero NO en DB local:\n";
        foreach ($onlyInApi as $slug) {
            echo "   - $slug\n";
            // Agregar a DB local
            \App\Models\Extension::updateOrCreate(
                ['slug' => $slug],
                ['licensed' => true]
            );
        }
        echo "✅ Agregadas a DB local\n\n";
    }
    
    // 4. Verificar tipo de licencia
    $settings = \App\Models\SettingTwo::first();
    echo "🔐 Tipo de licencia: " . $settings->liquid_license_type . "\n";
    echo "   Finance License: " . ($settings->liquid_license_type === 'Extended License' ? 'HABILITADO ✅' : 'DESHABILITADO ❌') . "\n\n";
    
    echo "✨ DIAGNÓSTICO COMPLETO\n";
    echo "=====================\n";
    echo "Total extensiones licenciadas: " . max($licensed->count(), $apiLicensed->count()) . "\n";
    echo "Acceso a finanzas/planes: " . ($settings->liquid_license_type === 'Extended License' ? 'SÍ ✅' : 'NO ❌') . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error consultando API: " . $e->getMessage() . "\n";
}

echo "\n✅ Script completado\n";


