<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    echo "Laravel loaded successfully\n";
    
    // Test if we can access the router
    $router = $app['router'];
    echo "Router loaded successfully\n";
    
    // Test if we can load the service provider
    $provider = new App\Extensions\Chatbot\System\ChatbotServiceProvider($app);
    echo "Service provider loaded successfully\n";
    
    // Test if we can register the provider
    $provider->register();
    echo "Service provider registered successfully\n";
    
    $provider->boot();
    echo "Service provider booted successfully\n";
    
    // List routes
    $routes = $router->getRoutes();
    echo "Total routes: " . count($routes) . "\n";
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'product') !== false || strpos($route->uri(), 'train') !== false) {
            echo "Found route: " . $route->methods()[0] . " " . $route->uri() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}