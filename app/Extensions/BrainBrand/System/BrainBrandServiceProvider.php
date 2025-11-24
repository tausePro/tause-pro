<?php

declare(strict_types=1);

namespace App\Extensions\BrainBrand\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Extensions\BrainBrand\System\Http\Controllers\BrainBrandTrainController;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BrainBrandServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerServices();
    }

    public function registerServices(): void
    {
        $this->app->singleton(
            \App\Extensions\BrainBrand\System\Services\BrainBrandValidationService::class,
            \App\Extensions\BrainBrand\System\Services\BrainBrandValidationService::class
        );
    }

    public function boot(): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations();
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', $this->registerKey());

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], $this->registerKey());

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router
                    ->controller(BrainBrandTrainController::class)
                    ->prefix('dashboard/user/brain-brand')
                    ->name('dashboard.user.brain-brand.')
                    ->group(function (Router $router) {
                        $router->get('', 'index')->name('index');
                        $router->get('{brainBrand}', 'show')->name('train');
                        $router->get('{brainBrand}/data', 'trainData')->name('data');
                        $router->get('{brainBrand}/statistics', 'getStatistics')->name('statistics');
                        $router->post('{brainBrand}/url', 'trainUrlBrainBrand')->name('url');
                        $router->post('{brainBrand}/file', 'trainFileBrainBrand')->name('file');
                        $router->post('{brainBrand}/text', 'trainTextBrainBrand')->name('text');
                        $router->post('{brainBrand}/qa', 'trainQaBrainBrand')->name('qa');
                        $router->post('{brainBrand}/embedding', 'generateEmbeddingBrainBrand')->name('embedding');
                        $router->post('{brainBrand}/distribute', 'distribute')->name('distribute');
                        $router->post('', 'store')->name('store');
                    });
            });

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/brainbrand.php', $this->registerKey());

        return $this;
    }

    public function registerKey(): string
    {
        return 'brainbrand';
    }

    private function router(): Router
    {
        return $this->app['router'];
    }
}
