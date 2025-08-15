<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.users.permissions.index');
    }

    public function getpermission()
    {
        $permission['data'] = Permission::all();
        //dd($permission['data']);
        foreach($permission['data'] as $index => $value){
                @$permission['data'][$index]['roles']=[];
                $idpermissiondata = @$value->id;
                $roles = "SELECT b.name AS rolename FROM role_has_permissions a
                INNER JOIN roles b ON a.role_id=b.id
                WHERE a.permission_id='$idpermissiondata'
                GROUP BY b.name";
                $result = DB::select(DB::raw($roles));
                foreach($result as $index2 => $value2){
                    @$permission['data'][$index]['roles']=$result;
                }
        }
        //dd($permission['data']);
        return response()->json($permission);
    }

    public function getmenujson(){
        $userId=auth()->user()->id;
        $rolhasuser = "SELECT
                a.model_id UserID,
                b.id Rolid,
                b.name Rol,
                d.id PermisoID,
                d.name Permiso
                FROM model_has_roles a
                INNER JOIN roles b ON a.role_id=b.id
                LEFT JOIN role_has_permissions c ON b.id=c.role_id
                LEFT JOIN permissions d ON c.permission_id=d.id
                WHERE model_id=$userId";
        $result = DB::select(DB::raw($rolhasuser));
        //dd($result);

        $menu = [
            [
                "url" => "/",
                "name" => "Home",
                "icon" => "menu-icon fa-solid fa-home",
                "slug" => "dashboard"
            ],
            [
                "name" => "Administracion",
                "icon" => "menu-icon fa-solid fa-shield",
                "slug" => "user.index",
                "badge" => ["primary", "3"],
                "submenu" => [
                    [
                        "url" => "/user/index",
                        "name" => "Usuarios",
                        "slug" => "user.index"
                    ],
                    [
                        "url" => "/rol/index",
                        "name" => "Roles",
                        "slug" => "user.rol.index"
                    ],
                    [
                        "url" => "/permission/index",
                        "name" => "Permisos",
                        "slug" => "user.permission.index"
                    ]
                ]
            ],
            [
                "url" => "/company/index",
                "name" => "Empresas",
                "icon" => "menu-icon fa-solid fa-id-badge",
                "slug" => "company.index"
            ],
            [
                "url" => "/client/index",
                "name" => "Clientes",
                "icon" => "menu-icon fa-solid fa-user-plus",
                "slug" => "client.index"
            ],
            [
                "name" => "Produccion",
                "icon" => "menu-icon fa-solid fa-feed",
                "slug" => "user.index",
                "badge" => ["primary", "3"],
                "submenu" => [
                    [
                        "url" => "/provider/index",
                        "name" => "Proveedores",
                        "slug" => "provider.index"
                    ],
                    [
                        "url" => "/marcas/index",
                        "name" => "Marcas",
                        "slug" => "marcas.index"
                    ],
                    [
                        "url" => "/product/index",
                        "name" => "Productos",
                        "slug" => "product.index"
                    ]

                ]
            ],
            [
                "url" => "/presales/index",
                "name" => "Pre-ventas",
                "icon" => "menu-icon fa-solid fa-cart-shopping",
                "slug" => "presales.index"
            ],
            [
                "url" => "/sale/index",
                "name" => "Ventas",
                "icon" => "menu-icon fa-solid fa-dollar",
                "slug" => "sale.index"
            ],
            [
                "url" => "/inventory",
                "name" => "Inventario",
                "icon" => "menu-icon fa-solid fa-boxes-packing",
                "slug" => "inventory.index"
            ],
            [
                "url" => "/purchase/index",
                "name" => "Compras",
                "icon" => "menu-icon fa-solid fa-truck",
                "slug" => "purchase.index"
            ],
            [
                "url" => "/credit/index",
                "name" => "Creditos",
                "icon" => "menu-icon fa-solid fa-credit-card",
                "slug" => "credit.index"
            ],
            [
                "url" => "/cotizaciones/index",
                "name" => "Cotizaciones",
                "icon" => "menu-icon fa-solid fa-file-invoice",
                "slug" => "cotizaciones.index"
            ],
            [
                "url" => "/ai-chat/",
                "name" => "Chat IA",
                "icon" => "menu-icon fa-solid fa-robot",
                "slug" => "ai-chat"
            ],
            [
                "url" => "/report/index",
                "name" => "Reportes",
                "icon" => "menu-icon fa-solid fa-line-chart",
                "slug" => "report.index",
                "badge" => ["primary", "8"],
                "submenu" => [
                    [
                        "url" => "/report/sales",
                        "name" => "Ventas",
                        "slug" => "report.sales"
                    ],
                    [
                        "url" => "/report/contribuyentes",
                        "name" => "Ventas Contribuyentes",
                        "slug" => "report.contribuyentes"
                    ],
                    [
                        "url" => "/report/directas",
                        "name" => "Ventas Directas",
                        "slug" => "report.directas"
                    ],
                    [
                        "url" => "/report/consumidor",
                        "name" => "Ventas Consumidor",
                        "slug" => "report.consumidor"
                    ],
                    [
                        "url" => "/report/purchases",
                        "name" => "Compras",
                        "slug" => "report.purchases"
                    ],
                    [
                        "url" => "/report/bookpurchases",
                        "name" => "Libro de Compras",
                        "slug" => "report.bookpurchases"
                    ],
                    [
                        "url" => "/report/reportyear",
                        "name" => "Ventas y compras por año",
                        "slug" => "report.reportyear"
                    ]
                ]
            ],
            [
                "url" => "/factmh/index",
                "name" => "Administracion DTE",
                "icon" => "menu-icon fa-solid fa-satellite-dish",
                "slug" => "factmh.index",
                "badge" => ["primary", "8"],
                "submenu" => [
                    [
                        "url" => "/factmh/dashboard",
                        "name" => "Dashboard DTE",
                        "slug" => "factmh.dashboard"
                    ],
                    [
                        "url" => "/factmh/documentos",
                        "name" => "Gestión Documentos",
                        "slug" => "factmh.documentos"
                    ],
                    [
                        "url" => "/factmh/contingencias",
                        "name" => "Contingencias",
                        "slug" => "factmh.contingencias"
                    ],
                    [
                        "url" => "/factmh/reintentos",
                        "name" => "Reintentos Automáticos",
                        "slug" => "factmh.reintentos"
                    ],
                    [
                        "url" => "/factmh/errores",
                        "name" => "Gestión de Errores",
                        "slug" => "factmh.errores"
                    ],
                    [
                        "url" => "/correlativos/",
                        "name" => "Gestión de Correlativos",
                        "slug" => "correlativos.index",
                        "submenu" => [
                            [
                                "url" => "/correlativos/",
                                "name" => "Lista de Correlativos",
                                "slug" => "correlativos.index"
                            ],
                            [
                                "url" => "/correlativos/create",
                                "name" => "Crear Correlativo",
                                "slug" => "correlativos.create"
                            ],
                            [
                                "url" => "/correlativos/estadisticas/view",
                                "name" => "Estadísticas",
                                "slug" => "correlativos.estadisticas"
                            ]
                        ]
                    ],
                    [
                        "url" => "/factmh/test_crt",
                        "name" => "Muestra Enviados",
                        "slug" => "factmh.test_crt"
                    ],
                    [
                        "url" => "/config/index",
                        "name" => "Configuraciones Ambiente",
                        "slug" => "config.index"
                    ]
                ]
            ]
        ];

        $filteredMenu = [];
        //dd($result);
        if(@$result[0]->Rolid!=1){

        // Agregar el primer elemento "Home" directamente
        if (isset($menu[0])) {
            $filteredMenu[] = $menu[0];
        }

        foreach ($menu as $index => $menuItem) {
            // Saltar el primer elemento (ya agregado)
            if ($index === 0) {
                continue;
            }

            // Verifica si el usuario tiene acceso a la sección principal (slug)
            if (in_array($menuItem['slug'], array_column($result, 'Permiso'))) {
                // Clona el menú principal
                $filteredItem = $menuItem;

                // Filtra el submenu si existe (incluyendo submenús anidados)
                if (isset($menuItem['submenu'])) {
                    $filteredSubmenu = [];
                    foreach ($menuItem['submenu'] as $submenuItem) {
                        // Verifica el permiso para cada subelemento del menú
                        if (in_array($submenuItem['slug'], array_column($result, 'Permiso'))) {
                            $filteredSubmenuItem = $submenuItem;

                            // Maneja submenús anidados (subnivel)
                            if (isset($submenuItem['submenu'])) {
                                $filteredNestedSubmenu = [];
                                foreach ($submenuItem['submenu'] as $nestedSubmenuItem) {
                                    if (in_array($nestedSubmenuItem['slug'], array_column($result, 'Permiso'))) {
                                        $filteredNestedSubmenu[] = $nestedSubmenuItem;
                                    }
                                }
                                // Solo añade el submenu anidado si hay permisos
                                if (!empty($filteredNestedSubmenu)) {
                                    $filteredSubmenuItem['submenu'] = $filteredNestedSubmenu;
                                } else {
                                    // Si no hay permisos para submenús, quita la propiedad submenu
                                    unset($filteredSubmenuItem['submenu']);
                                }
                            }

                            $filteredSubmenu[] = $filteredSubmenuItem;
                        }
                    }
                    // Solo añade el submenu si hay permisos
                    if (!empty($filteredSubmenu)) {
                        $filteredItem['submenu'] = $filteredSubmenu;
                    }
                }

                $filteredMenu[] = $filteredItem;
            }
        }

        // Encerrar todo en la palabra "menu"
$finalMenu = ['menu' => $filteredMenu];
$menuJson = json_encode($finalMenu);
//dd($menuJson);
}else {
    $finalMenu = ['menu' => $menu];
    $menuJson = json_encode($finalMenu);;
}
return response()->json($menuJson);
    }

    public function getpermissionjson(){
        $userId=auth()->user()->id;
        $rolhasuser = "SELECT
                a.model_id UserID,
                b.id Rolid,
                b.name Rol,
                d.id PermisoID,
                d.name Permiso
                FROM model_has_roles a
                INNER JOIN roles b ON a.role_id=b.id
                LEFT JOIN role_has_permissions c ON b.id=c.role_id
                LEFT JOIN permissions d ON c.permission_id=d.id
                WHERE model_id=$userId";
        $result = DB::select(DB::raw($rolhasuser));
        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Permission::create(['name' => $request->modalPermissionName]);
        return redirect()->route('permission.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $permission = Permission::find($request->editPermissionid);
        $permission->name = $request->editPermissionName;
        $permission->save();
        return redirect()->route('permission.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $permission = Permission::find(base64_decode($id));
        $permission->delete();
        return response()->json(array(
            "res" => "1"
        ));
    }

    /**
     * Crear permisos para el módulo de correlativos
     */
    public function createCorrelativosPermissions()
    {
        try {
            $permissions = [
                'correlativos.index' => 'Ver lista de correlativos',
                'correlativos.create' => 'Crear correlativos',
                'correlativos.edit' => 'Editar correlativos',
                'correlativos.destroy' => 'Eliminar correlativos',
                'correlativos.estadisticas' => 'Ver estadísticas de correlativos',
                'correlativos.reactivar' => 'Reactivar correlativos agotados',
                'correlativos.cambiar-estado' => 'Cambiar estado de correlativos',
                'correlativos.api.siguiente-numero' => 'API: Obtener siguiente número',
                'correlativos.api.validar-disponibilidad' => 'API: Validar disponibilidad'
            ];

            $createdPermissions = [];
            $existingPermissions = [];

            foreach ($permissions as $name => $description) {
                // Verificar si el permiso ya existe
                $existingPermission = Permission::where('name', $name)->first();

                if (!$existingPermission) {
                    Permission::create([
                        'name' => $name,
                        'guard_name' => 'web'
                    ]);
                    $createdPermissions[] = $name;
                } else {
                    $existingPermissions[] = $name;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Permisos de correlativos procesados correctamente',
                'created' => $createdPermissions,
                'existing' => $existingPermissions,
                'total_created' => count($createdPermissions),
                'total_existing' => count($existingPermissions)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar permisos de correlativos a un rol específico
     */
    public function assignCorrelativosPermissions(Request $request)
    {
        try {
            $request->validate([
                'role_id' => 'required|exists:roles,id',
                'permissions' => 'array'
            ]);

            $role = Role::findById($request->role_id);

            // Obtener todos los permisos de correlativos si no se especifican
            if (empty($request->permissions)) {
                $correlativosPermissions = Permission::where('name', 'like', 'correlativos.%')->pluck('name')->toArray();
            } else {
                $correlativosPermissions = $request->permissions;
            }

            // Asignar permisos al rol
            $role->syncPermissions(array_merge($role->permissions->pluck('name')->toArray(), $correlativosPermissions));

            return response()->json([
                'success' => true,
                'message' => 'Permisos asignados correctamente al rol: ' . $role->name,
                'assigned_permissions' => $correlativosPermissions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar permisos: ' . $e->getMessage()
            ], 500);
        }
    }
}
