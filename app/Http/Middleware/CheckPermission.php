<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PermissionController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
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
         $user = Auth::user();

         // Verifica que el usuario esté autenticado
         if (!$user) {
             abort(403, 'No tienes permiso para acceder a esta ruta.');
         }

         // Llama a PermissionController para obtener el JSON de permisos
         $permissionController = new PermissionController();
         $verticalMenuJson = $permissionController->getpermissionjson();

         // Inicializa un array para almacenar permisos y roles
         $permissions = [];
         $roles = [];

         // Itera sobre los permisos obtenidos desde la base de datos
         foreach ($verticalMenuJson->original as $permiso) {
             $permissions[] = explode('.', $permiso->Permiso)[0]; // Almacena todos los permisos
             $roles[] = $permiso->Rolid; // Almacena todos los roles
         }

         // Extrae el permiso relacionado con la ruta actual
         $requestedPermission = $request->route()->getName();

         // Divide la ruta solicitada en segmentos usando el punto como delimitador
         $permissionPrefix = explode('.', $requestedPermission)[0];

         // Verifica si el permiso solicitado está en la lista de permisos
         if (in_array($permissionPrefix, $permissions)) {
             return $next($request);
         }

         // Si el usuario es un administrador (role_id 1), se le permite el acceso a todas las rutas
         if (in_array(1, $roles)) {
             return $next($request);
         }

         // Si no tiene permiso, aborta con error 403
         abort(403, 'No tienes permiso para acceder a esta ruta.');
     }



    public function handleother(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Verifica que el usuario esté autenticado
        if (!$user) {
            abort(403, 'No tienes permiso para acceder a esta ruta.');
        }

        // Llama a PermissionController para obtener el JSON de permisos
        $permissionController = new PermissionController();
        $verticalMenuJson = $permissionController->getpermissionjson();
        //$permisos = array_column($array, 'Permiso');
        foreach($verticalMenuJson->original as $permiso){
            $rolvalue = $permiso->Rolid;
            $permisoValue = $permiso->Permiso;
        }
        // Extrae el permiso relacionado con la ruta actual (esto depende de cómo estructures el menú y las rutas)
        $requestedPermission = $request->route()->getName();
        dd($permisoValue);

        // Verifica si el usuario tiene el permiso relacionado con la ruta actual
        if ($permisoValue===$requestedPermission || $rolvalue==1) {

        }else{
            // Si no tiene permiso, aborta con error 403
            abort(403, 'No tienes permiso para acceder a esta ruta.');
        }

        return $next($request);
    }


}
