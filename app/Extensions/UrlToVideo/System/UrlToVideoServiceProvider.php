<?php

declare(strict_types=1);

namespace App\Extensions\UrlToVideo\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\UrlToVideo\System\Http\Controllers\UrlToVideoController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Author: MagicAI Team <info@liquid-themes.com>
 *
 * @note When you create a new service provider, make sure to add it to the "MarketplaceServiceProvider". Otherwise, your Laravel application won’t recognize this provider, and the related functions won’t work properly.
 * @note If you want to perform a specific action when an extension is uninstalled, you can use the UninstallExtensionServiceProviderInterface. By implementing this interface, you can define custom operations that will be triggered during the uninstallation of the extension.
 */
class UrlToVideoServiceProvider extends ServiceProvider implements UninstallExtensionServiceProviderInterface
{
    public function register()
    {
        $this->registerConfig();
    }

    public function boot(Kernel $kernel): void
    {
        $this->registerViews()
            ->registerRoutes()
            ->publishAssets();

    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../config/url-to-video.php' => config_path('url-to-video.php'),
        ], 'extension');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'url-to-video');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/creatify.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/topview.php');

        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router->group([
                    'prefix' => 'dashboard/user/url-to-video',
                    'as'     => 'dashboard.user.url-to-video.',
                ], function (Router $router) {
                    $router->get('', [UrlToVideoController::class, 'index'])->name('index');

                    $router->post('store-creatify-final-video', [UrlToVideoController::class, 'storeCreatifyFinalVideo'])->name('store-creatify-final-video');
                    $router->post('store-topview-final-video', [UrlToVideoController::class, 'storeTopviewFinalVideo'])->name('store-topview-final-video');
                });
            });

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/url-to-video.php', 'url-to-video');

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
}
