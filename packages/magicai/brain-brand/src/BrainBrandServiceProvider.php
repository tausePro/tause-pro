<?php

namespace MagicAI\BrainBrand;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\Company;
use App\Models\Chatbot\Chatbot;
use MagicAI\BrainBrand\Http\Controllers\BrainBrandController;
use App\Http\Controllers\Chatbot\ChatbotTrainingController;

class BrainBrandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Config publishable
        $this->mergeConfigFrom(__DIR__ . '/../config/brainbrand.php', 'brainbrand');
    }

    public function boot(): void
    {
        if (! config('brainbrand.enabled', false)) {
            return;
        }

        // Rutas desde vendor y, si existe, desde el workspace local
        $localRoutes = base_path('packages/magicai/brain-brand/routes/panel.php');
        if (file_exists($localRoutes)) {
            $this->loadRoutesFrom($localRoutes);
        }
        $this->loadRoutesFrom(__DIR__ . '/../routes/panel.php');

        // Vistas: prioriza workspace local para desarrollo, luego vendor
        $this->loadViewsFrom([
            base_path('packages/magicai/brain-brand/resources/views'),
            __DIR__ . '/../resources/views',
        ], 'brainbrand');

        $this->publishes([
            __DIR__ . '/../config/brainbrand.php' => config_path('brainbrand.php'),
        ], 'brainbrand-config');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/brainbrand'),
        ], 'brainbrand-views');

        // Asegurar rutas clave aunque el archivo de rutas no se recargue por cache
        Route::middleware(['web', 'auth'])->prefix('dashboard')->group(function () {
            Route::get('brain-brand/metrics', [\MagicAI\BrainBrand\Http\Controllers\BrainBrandController::class, 'metrics'])->name('brainbrand.metrics');
            Route::get('brain-brand/knowledge', [\MagicAI\BrainBrand\Http\Controllers\BrainBrandController::class, 'knowledge'])->name('brainbrand.knowledge');
        });

        // Programar barrido diario (Rebuscar + Entrenar) si está habilitado
        if (config('brainbrand.daily_sweep.enabled', false) && $this->app->runningInConsole()) {
            $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
                $cron = (string) config('brainbrand.daily_sweep.schedule', '0 3 * * *');
                $schedule->call(function () {
                    // Para cada usuario con bots, tomar compañías con website y ejecutar research + training
                    $chatbots = Chatbot::query()->whereNotNull('user_id')->where('status', 'active')->get();
                    if ($chatbots->isEmpty()) {
                        return;
                    }

                    /** @var ChatbotTrainingController $trainer */
                    $trainer = app(ChatbotTrainingController::class);

                    $chatbots->each(function (Chatbot $bot) use ($trainer) {
                        $companies = Company::query()
                            ->where('user_id', $bot->getAttribute('user_id'))
                            ->whereNotNull('website')
                            ->where('website', '<>', '')
                            ->orderBy('id')
                            ->limit(1)
                            ->get();

                        if ($companies->isEmpty()) {
                            return;
                        }

                        $company = $companies->first();

                        // Ejecutar investigación (SERP/Tavily) reutilizando controlador existente
                        try {
                            $req = new \Illuminate\Http\Request([
                                'url'  => (string) $company->getAttribute('website'),
                                'type' => null,
                            ]);
                            $trainer->postWebSites($req, $bot);
                        } catch (\Throwable $e) {
                            // continuar con el siguiente sin romper el schedule
                        }

                        // Entrenar datos en estado 'waiting' para este bot (por tipo)
                        foreach (['url','text','qa','pdf'] as $type) {
                            try {
                                $trainReq = new \Illuminate\Http\Request([
                                    'type' => $type,
                                ]);
                                $trainer->training($trainReq, $bot);
                            } catch (\Throwable $e) {
                                // continuar
                            }
                        }
                    });
                })->cron($cron)->name('brainbrand-daily-sweep');
            });
        }
    }
}


