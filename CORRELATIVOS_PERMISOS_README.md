# 🔐 Configuración de Permisos - Módulo de Correlativos

## 📋 Resumen

Este documento explica cómo configurar y gestionar los permisos para el módulo de correlativos en el sistema. El módulo utiliza **Laravel Spatie Permissions** para controlar el acceso a las diferentes funcionalidades.

---

## 🚀 Instalación Rápida de Permisos

### Opción 1: Panel de Administración (Recomendado)

1. **Acceder al panel**: Ve a `/permission/correlativos-setup`
2. **Instalar permisos**: Haz clic en "Instalar Permisos" 
3. **Asignar a roles**: Selecciona el rol y asigna los permisos necesarios

### Opción 2: API Directa

```bash
# Crear todos los permisos
curl -X POST /permission/create-correlativos-permissions \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token"

# Asignar permisos a un rol
curl -X POST /permission/assign-correlativos-permissions \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{"role_id": 1}'
```

---

## 🛡️ Permisos Disponibles

### **Permisos Principales**

| Permiso | Descripción | Endpoints |
|---------|-------------|-----------|
| `correlativos.index` | Ver lista de correlativos | `GET /correlativos/` |
| `correlativos.create` | Crear nuevos correlativos | `GET /correlativos/create`, `POST /correlativos/` |
| `correlativos.edit` | Editar correlativos | `GET /correlativos/{id}/edit`, `PUT /correlativos/{id}` |
| `correlativos.destroy` | Eliminar correlativos | `DELETE /correlativos/{id}` |
| `correlativos.estadisticas` | Ver estadísticas | `GET /correlativos/estadisticas/view` |

### **Permisos Especiales**

| Permiso | Descripción | Endpoints |
|---------|-------------|-----------|
| `correlativos.reactivar` | Reactivar correlativos agotados | `POST /correlativos/{id}/reactivar` |
| `correlativos.cambiar-estado` | Cambiar estado de correlativos | `PATCH /correlativos/{id}/estado` |

### **Permisos API**

| Permiso | Descripción | Endpoints |
|---------|-------------|-----------|
| `correlativos.api.siguiente-numero` | Obtener siguiente número | `POST /api/correlativos/siguiente-numero` |
| `correlativos.api.validar-disponibilidad` | Validar disponibilidad | `POST /api/correlativos/validar-disponibilidad` |

---

## 👥 Configuración por Roles

### **Administrador (Acceso Completo)**
```php
$admin = Role::findByName('Administrador');
$admin->givePermissionTo([
    'correlativos.index',
    'correlativos.create', 
    'correlativos.edit',
    'correlativos.destroy',
    'correlativos.estadisticas',
    'correlativos.reactivar',
    'correlativos.cambiar-estado',
    'correlativos.api.siguiente-numero',
    'correlativos.api.validar-disponibilidad'
]);
```

### **Supervisor (Solo Lectura + Estadísticas)**
```php
$supervisor = Role::findByName('Supervisor');
$supervisor->givePermissionTo([
    'correlativos.index',
    'correlativos.estadisticas',
    'correlativos.api.validar-disponibilidad'
]);
```

### **Usuario (Solo API)**
```php
$usuario = Role::findByName('Usuario');
$usuario->givePermissionTo([
    'correlativos.api.siguiente-numero',
    'correlativos.api.validar-disponibilidad'
]);
```

---

## 🔧 Implementación Técnica

### **En el Controlador**

Los permisos se aplican automáticamente usando middleware:

```php
// En CorrelativoController.php
$this->middleware('permission:correlativos.index')->only(['index', 'show']);
$this->middleware('permission:correlativos.create')->only(['create', 'store']);
$this->middleware('permission:correlativos.edit')->only(['edit', 'update']);
// ... etc
```

### **En las Vistas Blade**

```php
@can('correlativos.create')
    <a href="{{ route('correlativos.create') }}" class="btn btn-success">
        Crear Correlativo
    </a>
@endcan

@can('correlativos.destroy')
    <button class="btn btn-danger" onclick="eliminar({{ $correlativo->id }})">
        Eliminar
    </button>
@endcan
```

### **En el Menú Dinámico**

El menú se filtra automáticamente según los permisos del usuario:

```php
// En PermissionController@getmenujson()
if (in_array($menuItem['slug'], array_column($result, 'Permiso'))) {
    // Mostrar elemento de menú
}
```

---

## 🛠️ Personalización

### **Agregar Nuevos Permisos**

1. **Definir el permiso**:
```php
Permission::create(['name' => 'correlativos.nueva-funcion']);
```

2. **Agregar middleware al controlador**:
```php
$this->middleware('permission:correlativos.nueva-funcion')->only(['nuevaFuncion']);
```

3. **Actualizar vista de configuración**:
```php
// En resources/views/admin/users/permissions/correlativos.blade.php
'correlativos.nueva-funcion' => 'Descripción de la nueva función'
```

### **Modificar Permisos por Defecto**

Edita la función `createCorrelativosPermissions()` en `PermissionController.php`:

```php
$permissions = [
    'correlativos.index' => 'Ver lista de correlativos',
    'correlativos.nueva-funcion' => 'Nueva funcionalidad',
    // ... agregar más permisos
];
```

---

## 📊 Verificación de Estado

### **Verificar Permisos Instalados**

```php
// En tinker o código
$permisos = Permission::where('name', 'like', 'correlativos.%')->get();
foreach($permisos as $permiso) {
    echo $permiso->name . "\n";
}
```

### **Verificar Asignaciones de Rol**

```php
$rol = Role::findByName('Administrador');
$permisosCorrelativos = $rol->permissions()
    ->where('name', 'like', 'correlativos.%')
    ->pluck('name');
```

---

## 🚨 Solución de Problemas

### **Error: "Permission does not exist"**

1. Ejecutar instalador de permisos: `/permission/correlativos-setup`
2. O crear manualmente: `POST /permission/create-correlativos-permissions`

### **Usuario no ve el menú**

1. Verificar que el usuario tenga al menos `correlativos.index`
2. Revisar asignación de roles al usuario
3. Limpiar cache de permisos: `php artisan permission:cache-reset`

### **API retorna 403**

1. Verificar permisos API específicos
2. Asegurar que el token/sesión incluya los permisos correctos

---

## 📚 Enlaces Útiles

- **Panel de Configuración**: `/permission/correlativos-setup`
- **Gestión de Permisos**: `/permission/index`
- **Gestión de Roles**: `/rol/index`
- **Documentación Spatie**: [spatie.be/docs/laravel-permission](https://spatie.be/docs/laravel-permission)

---

## ✅ Lista de Verificación

- [ ] Permisos instalados correctamente
- [ ] Roles configurados según necesidades
- [ ] Usuarios asignados a roles apropiados
- [ ] Menú se filtra correctamente
- [ ] APIs responden según permisos
- [ ] Vistas muestran/ocultan elementos según permisos

---

*Última actualización: [Fecha]* 
