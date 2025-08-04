<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Configurar sesiones PHP globalmente
        if (!headers_sent()) {
            // 8 horas = 28800 segundos
            ini_set('session.gc_maxlifetime', 28800);
            ini_set('session.cookie_lifetime', 28800);

            // Configuración adicional de seguridad
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
        }
    }
}
