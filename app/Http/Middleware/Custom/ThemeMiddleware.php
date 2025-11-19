<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Classes\Helper;
use App\Helpers\Classes\TableSchema;
use Closure;
use Igaster\LaravelTheme\Facades\Theme;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si estamos en modo instalación ANTES de cualquier consulta a BD
        $isInstallationRoute = false;
        try {
            $isInstallationRoute = $request->is('install*') || $request->is('upgrade*') || $request->is('update*');
        } catch (\Exception $e) {
            $isInstallationRoute = true;
        }

        // Si estamos en instalación, usar tema por defecto y NO consultar BD
        if ($isInstallationRoute) {
            Theme::set('default');
            return $next($request);
        }

        // Solo intentar configurar tema si NO estamos en instalación
        try {
            if (Helper::dbConnectionStatus()) {
                try {
                    $tables = app('magicai_tables');
                    if (!empty($tables) && TableSchema::hasTable('app_settings', $tables)) {
                        $this->setDefaultSettings();

                        $activated_front_theme = setting('front_theme');
                        $activated_dash_theme = setting('dash_theme');

                        $sameTheme = $activated_front_theme === $activated_dash_theme;

                        $isDashboard = $request->is('dashboard*', '*/dashboard*');

                        $themeToSet = match (true) {
                            $sameTheme   => $activated_front_theme,
                            $isDashboard => $activated_dash_theme,
                            default      => $activated_front_theme,
                        };

                        Theme::set($themeToSet);
                    } else {
                        Theme::set('default');
                    }
                } catch (\Exception $e) {
                    // Si falla cualquier consulta, usar tema por defecto
                    Theme::set('default');
                }
            } else {
                Theme::set('default');
            }
        } catch (\Exception $e) {
            // Si hay cualquier error, usar tema por defecto
            Theme::set('default');
        }

        return $next($request);
    }

    protected function setDefaultSettings(): void
    {
        // Verificar si estamos en modo instalación
        try {
            $isInstallationRoute = request()->is('install*') || request()->is('upgrade*') || request()->is('update*');
            if ($isInstallationRoute) {
                return; // No configurar durante instalación
            }
        } catch (\Exception $e) {
            return; // Si no podemos verificar, no configurar
        }

        try {
            if (setting('front_theme') === null) {
                setting(['front_theme' => 'default'])->save();
            }
            if (setting('dash_theme') === null) {
                setting(['dash_theme' => 'default'])->save();
            }
        } catch (\Exception $e) {
            // Si falla, simplemente no configurar
            return;
        }
    }
}
