<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\PermissionController;

class LoadMenuMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $permissionController = new PermissionController();
            $verticalMenuJson = $permissionController->getmenujson();
            $verticalMenuData = json_decode($verticalMenuJson->original);

            $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            // Compartir los datos del menú con todas las vistas
            \View::share('menuData', [$verticalMenuData, $horizontalMenuData]);
        }

        return $next($request);
    }
}
