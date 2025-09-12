<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotApplicationController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotFrameController;
use App\Extensions\Chatbot\System\Http\Controllers\AvatarController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotCustomerController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotKnowledgeBaseArticleController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotMultiChannelController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotProductController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotTrainController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotTriggerController;
use App\Extensions\Chatbot\System\Http\Controllers\TriggerManagementController;
use App\Extensions\Chatbot\System\Http\Middleware\LanguageMiddleware;
use App\Helpers\Classes\Helper;
use App\Http\Middleware\CheckTemplateTypeAndPlan;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatbotServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface
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
            ->publishAssets()
            ->registerCommand();
    }

    public function registerCommand(): static
    {
        $this->commands([
            Console\Commands\PublishTriggerAssetsCommand::class,
        ]);

        if (Helper::appIsDemo()) {
            $this->commands([
                Console\Commands\ClearDemoModeCommand::class,
            ]);
        }

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets/js'     => public_path('vendor/chatbot/js'),
            __DIR__ . '/../resources/assets/images' => public_path('vendor/chatbot/images'),
            __DIR__ . '/../resources/assets/icons'  => public_path('vendor/chatbot/icons'),
            __DIR__ . '/../resources/js'            => public_path('vendor/chatbot/js'),
            __DIR__ . '/../resources/css'           => public_path('vendor/chatbot/css'),
        ], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chatbot.php', $this->registerKey());

        return $this;
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

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => 'web',
            ], function (Router $router) {
                $router
                    ->controller(ChatbotFrameController::class)
                    ->group(function (Router $router) {
                        $router->get('chatbot/{chatbot:uuid}/frame', 'frame')->name('chatbot.frame');
                    });
            })
            ->group([
                'middleware'     => ['api', LanguageMiddleware::class],
                'prefix'         => 'api/v2/chatbot',
                'as'             => 'api.v2.chatbot.',
                'controller'     => ChatbotApplicationController::class,
            ], function (Router $router) {
                $router->get('{chatbot:uuid}', 'index')->name('index');
                $router->get('{chatbot:uuid}/articles', 'articles')->name('articles');
                $router->get('{chatbot:uuid}/articles/{id}/show', 'showArticles')->name('articles.show');
                $router->get('{chatbot:uuid}/session/{sessionId}', 'indexSession')->name('index.session');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation', 'conversionStore')->name('conversion.store');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/connect', 'connectSupport')->name('conversion.connect.support');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}', 'conversion')->name('conversion.show');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'messages')->name('conversion.messages');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'storeMessage')->name('conversion.store.message');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/file', 'storeFile')->name('conversion.store.file');
                $router->post('{chatbot:uuid}/session/{sessionId}/send-email', 'sendEmail')->name('send-email.store');
                $router->post('{chatbot:uuid}/session/{sessionId}/collect-email', 'collectEmail')->name('collect.email');
            })
            ->group([
                'middleware' => ['api'],
                'prefix'     => 'api/v2/chatbot/triggers',
                'as'         => 'api.v2.chatbot.triggers.',
                'controller' => ChatbotTriggerController::class,
            ], function (Router $router) {
                $router->get('{chatbotId}/active', 'getActiveTriggers')->name('active');
                $router->post('evaluate', 'evaluateTrigger')->name('evaluate');
                $router->post('analytics', 'recordAnalytics')->name('analytics');
            })
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $route) {
                $route->controller(ChatbotMultiChannelController::class)
                    ->name('dashboard.chatbot-multi-channel.')
                    ->prefix('dashboard/chatbot-multi-channel')
                    ->group(function () {
                        Route::any('', 'index')->name('index');
                        Route::POST('delete', 'delete')->name('delete');
                    });
                $route->group([
                    'prefix'         => 'dashboard/chatbot',
                    'as'             => 'dashboard.chatbot.',
                ], function (Router $router) {
                    $router->resource('knowledge-base-article', ChatbotKnowledgeBaseArticleController::class);
                    $router->resource('chatbot-customer', ChatbotCustomerController::class);
                });
                $route
                    ->controller(ChatbotController::class)
                    ->prefix('dashboard/chatbot')
                    ->name('dashboard.chatbot.')
                    ->group(function (Router $route) {
                        $route->get('', 'index')
                            ->name('index')
                            ->middleware(CheckTemplateTypeAndPlan::class);
                        $route->post('', 'store')->name('store');
                        $route->post('update', 'update')->name('update');
                        $route->delete('delete', 'delete')->name('delete');

                        // conversation
                        $route->get('conversations', 'conversations')->name('conversations');
                        $route->get('conversations-with-paginate', 'conversationsWithPaginate')->name('conversations.with.paginate');

                        // ended routes
                        $route->get('{chatbot}/enbed', 'enbed')->name('enbed');
                    });
                $route
                    ->controller(ChatbotTrainController::class)
                    ->prefix('dashboard/chatbot/train')
                    ->name('dashboard.chatbot.train.')
                    ->group(function (Router $route) {
                        // train routes
                        $route->get('data', 'trainData')->name('data');
                        $route->post('delete-embedding', 'deleteEmbedding')->name('delete');
                        $route->post('generate-embedding', 'generateEmbedding')->name('generate.embedding');
                        $route->get('{chatbot}', 'train')->name('index');
                        $route->post('url', 'trainUrl')->name('url');
                        $route->post('file', 'trainFile')->name('file');
                        $route->post('text', 'trainText')->name('text');
                        $route->post('qa', 'trainQa')->name('qa');
                        $route->post('save-products', 'saveDetectedProducts')->name('save.products');
                    });
                $route
                    ->controller(ChatbotTriggerController::class)
                    ->prefix('dashboard/chatbot')
                    ->name('dashboard.chatbot.')
                    ->group(function (Router $route) {
                        $route->get('{chatbotId}/triggers', 'index')->name('triggers.index');
                        $route->post('{chatbotId}/triggers', 'store')->name('triggers.store');
                        $route->get('{chatbotId}/triggers/analytics', 'getAnalytics')->name('triggers.analytics');
                        
                        // Trigger management page
                        $route->get('{chatbotId}/triggers/management', 'managementPage')->name('triggers.management');
                        
                        // Custom triggers management
                        $route->get('{chatbotId}/triggers/custom', 'getCustomTriggers')->name('triggers.custom.index');
                        $route->post('{chatbotId}/triggers/custom', 'storeCustomTrigger')->name('triggers.custom.store');
                        $route->put('{chatbotId}/triggers/custom/{triggerId}', 'updateCustomTrigger')->name('triggers.custom.update');
                        $route->delete('{chatbotId}/triggers/custom/{triggerId}', 'deleteCustomTrigger')->name('triggers.custom.delete');
                    });
                $route
                    ->controller(TriggerManagementController::class)
                    ->prefix('dashboard/chatbot/{chatbot}/triggers/management')
                    ->name('dashboard.chatbot.triggers.management.')
                    ->group(function (Router $route) {
                        // Main dashboard
                        $route->get('/', 'index')->name('index');
                        
                        // Data endpoint for DataTable
                        $route->get('/data', 'getTriggersData')->name('data');
                        
                        // Create trigger
                        $route->get('/create', 'create')->name('create');
                        $route->post('/create', 'store')->name('store');
                        
                        // Edit trigger
                        $route->get('/{trigger}/edit', 'edit')->name('edit');
                        $route->put('/{trigger}', 'update')->name('update');
                        
                        // Trigger actions
                        $route->post('/{trigger}/toggle', 'toggleStatus')->name('toggle');
                        $route->post('/{trigger}/duplicate', 'duplicate')->name('duplicate');
                        $route->delete('/{trigger}', 'destroy')->name('destroy');
                        
                        // Testing and preview
                        $route->post('/preview', 'preview')->name('preview');
                        $route->post('/{trigger}/test', 'test')->name('test');
                        
                        // Templates
                        $route->get('/templates', 'getTemplates')->name('templates');
                        $route->post('/apply-template', 'applyTemplate')->name('apply-template');
                    });
                $route
                    ->controller(ChatbotProductController::class)
                    ->prefix('dashboard/chatbot')
                    ->name('dashboard.chatbot.')
                    ->group(function (Router $route) {
                        $route->get('products', 'index')->name('products.index');
                        $route->get('products/stats', 'stats')->name('products.stats');
                        $route->put('products/{id}', 'update')->name('products.update');
                        $route->delete('products/{id}', 'destroy')->name('products.destroy');
                    });
                $route->post('dashboard/chatbot/avatar/upload', AvatarController::class)
                    ->name('dashboard.chatbot.upload.avatar');
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public function registerKey(): string
    {
        return 'chatbot';
    }
}