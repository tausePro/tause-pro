<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Extensions\BrainBrand\System\Models\BrainBrand;

echo "=== BrainBrand Diagnostic Test ===\n\n";

// 1. Check if tables exist
echo "1. Checking database tables:\n";
$tableExists = Schema::hasTable('ext_brain_brands');
echo "   - ext_brain_brands table exists: " . ($tableExists ? "✓ YES" : "✗ NO") . "\n";

$embeddingTableExists = Schema::hasTable('ext_chatbot_embeddings');
echo "   - ext_chatbot_embeddings table exists: " . ($embeddingTableExists ? "✓ YES" : "✗ NO") . "\n";

// 2. Check if brain_brand_id column exists
if ($embeddingTableExists) {
    $columns = Schema::getColumnListing('ext_chatbot_embeddings');
    $hasBrainBrandId = in_array('brain_brand_id', $columns);
    echo "   - brain_brand_id column in embeddings: " . ($hasBrainBrandId ? "✓ YES" : "✗ NO") . "\n";
}

echo "\n";

// 3. Check service provider registration
echo "2. Checking service provider:\n";
$providers = config('app.providers');
$brainBrandProviderRegistered = in_array('App\Extensions\BrainBrand\System\BrainBrandServiceProvider', $providers);
echo "   - BrainBrandServiceProvider registered: " . ($brainBrandProviderRegistered ? "✓ YES" : "✗ NO") . "\n";

// 4. Check if service is bound in container
$serviceRegistered = app()->bound('App\Extensions\BrainBrand\System\Services\BrainBrandValidationService');
echo "   - BrainBrandValidationService bound: " . ($serviceRegistered ? "✓ YES" : "✗ NO") . "\n";

echo "\n";

// 5. Check routes
echo "3. Checking routes:\n";
$routes = Route::getRoutes();
$brainBrandRoutes = [];
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'brain-brand')) {
        $brainBrandRoutes[] = $route->uri();
    }
}
echo "   - Found " . count($brainBrandRoutes) . " BrainBrand routes\n";
if (count($brainBrandRoutes) > 0) {
    foreach (array_slice($brainBrandRoutes, 0, 5) as $route) {
        echo "     • " . $route . "\n";
    }
}

echo "\n";

// 6. Check views
echo "4. Checking views:\n";
$viewsPath = __DIR__ . '/app/Extensions/BrainBrand/resources/views';
$viewsExist = is_dir($viewsPath);
echo "   - Views directory exists: " . ($viewsExist ? "✓ YES" : "✗ NO") . "\n";
if ($viewsExist) {
    $viewFiles = glob($viewsPath . '/*.blade.php');
    echo "   - Found " . count($viewFiles) . " view files\n";
}

echo "\n";

// 7. Test model functionality
echo "5. Testing model functionality:\n";
try {
    // Try to query the model
    $count = BrainBrand::count();
    echo "   - Model query successful: ✓ YES\n";
    echo "   - Total BrainBrands in database: " . $count . "\n";
} catch (\Exception $e) {
    echo "   - Model query failed: ✗ NO\n";
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Check configuration
echo "6. Checking configuration:\n";
$configExists = config('brainbrand') !== null;
echo "   - BrainBrand config loaded: " . ($configExists ? "✓ YES" : "✗ NO") . "\n";

echo "\n";

// 9. Check for any recent errors in logs
echo "7. Recent error summary:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    if (preg_match_all('/BrainBrand.*error.*$/mi', $logContent, $matches)) {
        $recentErrors = array_slice($matches[0], -3);
        if (count($recentErrors) > 0) {
            echo "   Recent BrainBrand errors found:\n";
            foreach ($recentErrors as $error) {
                echo "   • " . substr($error, 0, 100) . "...\n";
            }
        } else {
            echo "   - No recent BrainBrand errors in logs\n";
        }
    } else {
        echo "   - No BrainBrand errors found in logs\n";
    }
} else {
    echo "   - Log file not found\n";
}

echo "\n=== Diagnostic Complete ===\n";

// Summary
$issues = [];
if (!$tableExists) $issues[] = "Database table 'ext_brain_brands' does not exist";
if (!$embeddingTableExists) $issues[] = "Database table 'ext_chatbot_embeddings' does not exist";
if ($embeddingTableExists && !$hasBrainBrandId) $issues[] = "Column 'brain_brand_id' missing in embeddings table";
if (!$brainBrandProviderRegistered) $issues[] = "Service provider not registered";
if (!$serviceRegistered) $issues[] = "Validation service not bound in container";
if (count($brainBrandRoutes) == 0) $issues[] = "No routes registered";
if (!$viewsExist) $issues[] = "Views directory not found";
if (!$configExists) $issues[] = "Configuration not loaded";

if (count($issues) > 0) {
    echo "\n⚠️  ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   • " . $issue . "\n";
    }
    echo "\nRECOMMENDED ACTIONS:\n";
    if (!$tableExists || !$embeddingTableExists || ($embeddingTableExists && !$hasBrainBrandId)) {
        echo "   1. Run: php artisan migrate\n";
    }
    if (!$brainBrandProviderRegistered) {
        echo "   2. Add BrainBrandServiceProvider to config/app.php\n";
    }
    if (!$serviceRegistered || count($brainBrandRoutes) == 0 || !$configExists) {
        echo "   3. Clear cache: php artisan cache:clear && php artisan config:clear\n";
    }
} else {
    echo "\n✅ All checks passed! BrainBrand should be working correctly.\n";
}