<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotSalesAgent\System;

use App\Extensions\ChatbotSalesAgent\System\Http\Controllers\ChatbotSalesAgentController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatbotSalesAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets();
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sales-agent.php', 'sales-agent');

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/sales-agent'),
        ], 'extension');

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'sales-agent');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'sales-agent');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router
                    ->controller(ChatbotSalesAgentController::class)
                    ->prefix('dashboard/chatbot-sales-agent')
                    ->name('dashboard.chatbot-sales-agent.')
                    ->group(function (Router $router) {
                        $router->get('{chatbot}', 'index')->name('index');
                        $router->get('{chatbot}/products', 'getProducts')->name('products');
                        $router->post('{chatbot}/order', 'createOrder')->name('create-order');
                    });
            })
            ->group([
                'middleware' => 'api',
                'prefix' => 'api/v2/chatbot',
                'as' => 'api.v2.chatbot.',
            ], function (Router $router) {
                $router
                    ->controller(ChatbotSalesAgentController::class)
                    ->group(function (Router $router) {
                        $router->post('{chatbot:uuid}/sales-agent/order', 'createOrder')->name('sales-agent.create-order');
                    });
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        // Clean up if needed when uninstalling
    }
}

