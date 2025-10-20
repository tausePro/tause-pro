<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Simulate a request to the products endpoint
    $request = Illuminate\Http\Request::create('/dashboard/chatbot/products?chatbot_id=5', 'GET');
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}