<?php

namespace MagicAI\ExternalChatbot;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ExternalChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/externalchatbot.php', 'externalchatbot');
    }

    public function boot(): void
    {
        if (! config('externalchatbot.enabled', true)) {
            return;
        }

        // Rutas de panel y de embed
        $this->loadRoutesFrom(__DIR__ . '/../routes/panel.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/embed.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'externalchatbot');

        $this->publishes([
            __DIR__ . '/../config/externalchatbot.php' => config_path('externalchatbot.php'),
        ], 'externalchatbot-config');
    }
}


