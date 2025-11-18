<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotApplicationController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotFrameController;
use App\Extensions\Chatbot\System\Http\Controllers\AvatarController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotAnalyticsController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotCrmController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotCustomerController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotEcommerceController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotKnowledgeBaseArticleController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotMultiChannelController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotTrainController;
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
        if (Helper::appIsDemo()) {
            $this->commands([
                Console\Commands\ClearDemoModeCommand::class,
            ]);

            //            if ($this->app->runningInConsole()) {
            //                $this->app->booted(function () {
            //                    $schedule = $this->app->make(Schedule::class);
            //                    $schedule->command('app:clear-chatbot-demo-mode')->everyMinute();
            //                });
            //            }
        }

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets/js'     => public_path('vendor/chatbot/js'),
            __DIR__ . '/../resources/assets/images' => public_path('vendor/chatbot/images'),
            __DIR__ . '/../resources/assets/icons'  => public_path('vendor/chatbot/icons'),
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
                $router->get('{chatbot:uuid}/triggers', 'triggers')->name('triggers');
                $router->get('{chatbot:uuid}/articles', 'articles')->name('articles');
                $router->get('{chatbot:uuid}/articles/{id}/show', 'showArticles')->name('articles.show');
                $router->get('{chatbot:uuid}/session/{sessionId}', 'indexSession')->name('index.session');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation', 'conversionStore')->name('conversion.store');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/connect', 'connectSupport')->name('conversion.connect.support');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}', 'conversion')->name('conversion.show');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'messages')->name('conversion.messages');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/export', 'export')->name('conversion.export');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'storeMessage')->name('conversion.store.message');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/file', 'storeFile')->name('conversion.store.file');
                $router->post('{chatbot:uuid}/session/{sessionId}/send-email', 'sendEmail')->name('send-email.store');
                $router->any('{chatbot:uuid}/session/{sessionId}/enable-sound', 'enableSound')->name('enable-sound');
                $router->post('{chatbot:uuid}/session/{sessionId}/collect-email', 'collectEmail')->name('collect.email');
                $router->post('{chatbot:uuid}/session/{sessionId}/gdpr-consent', 'saveGdprConsent')->name('gdpr.consent');
                // Sales Agent endpoints
                $router->get('{chatbot:uuid}/products', 'getProducts')->name('products');
                $router->post('{chatbot:uuid}/generate-payment-link', 'generatePaymentLink')->name('payment-link');
                $router->post('{chatbot:uuid}/create-order', 'createOrder')->name('create-order');
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
                        $route->post('conversations/search', 'searchConversation')->name('conversations.search');

                        // triggers
                        $route->get('{chatbot}/triggers', 'getTriggers')->name('triggers.get');
                        $route->post('{chatbot}/triggers', 'saveTriggers')->name('triggers.save');

                        // agents
                        $route->get('{chatbot}/agents', 'getAgents')->name('agents.get');

                        // ended routes
                        $route->get('{chatbot}/enbed', 'enbed')->name('enbed');
                    });
                $route
                    ->controller(ChatbotAnalyticsController::class)
                    ->prefix('dashboard/chatbot')
                    ->name('dashboard.chatbot.')
                    ->group(function (Router $route) {
                        $route->get('analytics', 'index')->name('analytics.index');
                        $route->get('analytics/{chatbotId}/data', 'data')->name('analytics.data');
                        $route->get('analytics/{chatbotId}/export', 'export')->name('analytics.export');
                    });
                $route
                    ->controller(ChatbotCrmController::class)
                    ->prefix('dashboard/chatbot/{chatbot}')
                    ->name('dashboard.chatbot.crm.')
                    ->group(function (Router $route) {
                        $route->get('leads/export', 'exportLeads')->name('leads.export');
                        $route->get('leads/stats', 'getLeadsStats')->name('leads.stats');
                    });
                $route
                    ->controller(ChatbotEcommerceController::class)
                    ->prefix('dashboard/chatbot/{chatbot}')
                    ->name('dashboard.chatbot.ecommerce.')
                    ->group(function (Router $route) {
                        $route->get('ecommerce', 'index')->name('index');
                        $route->post('ecommerce/woocommerce', 'saveWooCommerceConfig')->name('woocommerce.save');
                        $route->match(['get', 'post'], 'ecommerce/sync', 'syncProducts')->name('sync');
                        $route->post('ecommerce/wompi', 'saveWompiConfig')->name('wompi.save');
                        $route->post('ecommerce/sales-agent', 'saveSalesAgentConfig')->name('sales-agent.save');
                        $route->post('ecommerce/generate-coupon', 'generateCoupon')->name('generate-coupon');
                        $route->post('ecommerce/product/{product}/toggle', 'toggleProduct')->name('product.toggle');
                        $route->delete('ecommerce/product/{product}', 'deleteProduct')->name('product.delete');
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
