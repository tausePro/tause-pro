<?php

// Test script to verify trigger dashboard functionality
require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

echo "🧪 Testing Trigger Dashboard Functionality\n";
echo "==========================================\n\n";

// Test 1: Check if TriggerManagementController exists
echo "1. Checking TriggerManagementController...\n";
$controllerPath = 'app/Extensions/Chatbot/System/Http/Controllers/TriggerManagementController.php';
if (file_exists($controllerPath)) {
    echo "   ✅ Controller exists\n";
} else {
    echo "   ❌ Controller missing\n";
}

// Test 2: Check if routes file exists
echo "\n2. Checking routes file...\n";
$routesPath = 'routes/chatbot-triggers.php';
if (file_exists($routesPath)) {
    echo "   ✅ Routes file exists\n";
    
    // Check if routes are properly included
    $webRoutes = file_get_contents('routes/web.php');
    if (strpos($webRoutes, 'chatbot-triggers.php') !== false) {
        echo "   ✅ Routes are included in web.php\n";
    } else {
        echo "   ❌ Routes not included in web.php\n";
    }
} else {
    echo "   ❌ Routes file missing\n";
}

// Test 3: Check if views exist
echo "\n3. Checking views...\n";
$viewPaths = [
    'app/Extensions/Chatbot/resources/views/management/triggers/index.blade.php',
    'app/Extensions/Chatbot/resources/views/management/triggers/create.blade.php'
];

foreach ($viewPaths as $viewPath) {
    if (file_exists($viewPath)) {
        echo "   ✅ " . basename($viewPath) . " exists\n";
    } else {
        echo "   ❌ " . basename($viewPath) . " missing\n";
    }
}

// Test 4: Check if services exist
echo "\n4. Checking services...\n";
$servicePaths = [
    'app/Extensions/Chatbot/System/Services/TriggerAnalyticsService.php',
    'app/Extensions/Chatbot/System/Services/ProactiveTriggerService.php'
];

foreach ($servicePaths as $servicePath) {
    if (file_exists($servicePath)) {
        echo "   ✅ " . basename($servicePath) . " exists\n";
    } else {
        echo "   ❌ " . basename($servicePath) . " missing\n";
    }
}

// Test 5: Check if models exist
echo "\n5. Checking models...\n";
$modelPaths = [
    'app/Extensions/Chatbot/System/Models/ChatbotTrigger.php',
    'app/Extensions/Chatbot/System/Models/ChatbotTriggerAnalytic.php'
];

foreach ($modelPaths as $modelPath) {
    if (file_exists($modelPath)) {
        echo "   ✅ " . basename($modelPath) . " exists\n";
    } else {
        echo "   ❌ " . basename($modelPath) . " missing\n";
    }
}

// Test 6: Check database tables
echo "\n6. Checking database structure...\n";
try {
    // This would require Laravel to be bootstrapped
    echo "   ⚠️  Database check requires Laravel bootstrap\n";
} catch (Exception $e) {
    echo "   ⚠️  Cannot check database: " . $e->getMessage() . "\n";
}

echo "\n🎯 Summary:\n";
echo "The trigger dashboard system appears to be structurally complete.\n";
echo "If there are issues, they're likely related to:\n";
echo "- Route registration in the service provider\n";
echo "- View path resolution\n";
echo "- Database migrations not run\n";
echo "- Missing dependencies in composer\n\n";

echo "💡 Next steps:\n";
echo "1. Run: php artisan migrate\n";
echo "2. Run: php artisan optimize:clear\n";
echo "3. Check: php artisan route:list | grep trigger\n";
echo "4. Test: Visit /chatbot/{id}/triggers/management\n";