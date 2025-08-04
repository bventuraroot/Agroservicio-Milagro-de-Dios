@extends('layouts.app')

@section('title', 'Permisos de Correlativos')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Gestión de Permisos - Módulo Correlativos</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('permission.index') }}">Permisos</a></li>
                        <li class="breadcrumb-item active">Correlativos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Instalador de Permisos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs"></i> Instalador de Permisos para Correlativos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="card-text">
                                Utiliza esta herramienta para crear automáticamente todos los permisos necesarios
                                para el módulo de correlativos. Estos permisos controlan el acceso a las diferentes
                                funcionalidades del sistema.
                            </p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Ver lista de correlativos</li>
                                <li><i class="fas fa-check text-success"></i> Crear nuevos correlativos</li>
                                <li><i class="fas fa-check text-success"></i> Editar correlativos existentes</li>
                                <li><i class="fas fa-check text-success"></i> Eliminar correlativos</li>
                                <li><i class="fas fa-check text-success"></i> Ver estadísticas</li>
                                <li><i class="fas fa-check text-success"></i> Reactivar correlativos agotados</li>
                                <li><i class="fas fa-check text-success"></i> Cambiar estados</li>
                                <li><i class="fas fa-check text-success"></i> Acceso a APIs</li>
                            </ul>
                        </div>
                        <div class="col-md-4 text-center">
                            <button type="button" class="btn btn-success btn-lg" onclick="instalarPermisos()">
                                <i class="fas fa-download"></i> Instalar Permisos
                            </button>
                            <div id="installationStatus" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Asignación de Permisos a Roles -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog"></i> Asignar Permisos a Roles
                    </h5>
                </div>
                <div class="card-body">
                    <form id="assignPermissionsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_select" class="form-label">Seleccionar Rol</label>
                                    <select class="form-select" id="role_select" required>
                                        <option value="">Cargando roles...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Permisos a Asignar</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allPermissions" checked>
                                        <label class="form-check-label" for="allPermissions">
                                            Todos los permisos de correlativos
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="specificPermissions" style="display: none;">
                            <h6>Seleccionar permisos específicos:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_index" value="correlativos.index">
                                        <label class="form-check-label" for="perm_index">Ver lista</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_create" value="correlativos.create">
                                        <label class="form-check-label" for="perm_create">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_edit" value="correlativos.edit">
                                        <label class="form-check-label" for="perm_edit">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_destroy" value="correlativos.destroy">
                                        <label class="form-check-label" for="perm_destroy">Eliminar</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_stats" value="correlativos.estadisticas">
                                        <label class="form-check-label" for="perm_stats">Ver estadísticas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_reactivar" value="correlativos.reactivar">
                                        <label class="form-check-label" for="perm_reactivar">Reactivar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_estado" value="correlativos.cambiar-estado">
                                        <label class="form-check-label" for="perm_estado">Cambiar estado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permission-check" type="checkbox"
                                               id="perm_api" value="correlativos.api.siguiente-numero">
                                        <label class="form-check-label" for="perm_api">Acceso API</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-shield"></i> Asignar Permisos
                            </button>
                        </div>
                    </form>

                    <div id="assignmentStatus" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado Actual de Permisos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check"></i> Estado Actual de Permisos
                    </h5>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-info mb-3" onclick="cargarEstadoPermisos()">
                        <i class="fas fa-sync-alt"></i> Actualizar Estado
                    </button>
                    <div id="permissionsStatus">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarRoles();
    cargarEstadoPermisos();

    // Manejar checkbox de todos los permisos
    document.getElementById('allPermissions').addEventListener('change', function() {
        const specificDiv = document.getElementById('specificPermissions');
        if (this.checked) {
            specificDiv.style.display = 'none';
        } else {
            specificDiv.style.display = 'block';
        }
    });

    // Manejar formulario de asignación
    document.getElementById('assignPermissionsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        asignarPermisos();
    });
});

function instalarPermisos() {
    const statusDiv = document.getElementById('installationStatus');
    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Instalando...';

    fetch('/permission/create-correlativos-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>¡Éxito!</strong> ${data.message}<br>
                    <small>
                        Creados: ${data.total_created} |
                        Ya existían: ${data.total_existing}
                    </small>
                </div>
            `;
            cargarEstadoPermisos(); // Actualizar estado
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        statusDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    });
}

function cargarRoles() {
    // Aquí deberías implementar la carga de roles desde tu API
    // Por ahora, uso roles de ejemplo
    const roleSelect = document.getElementById('role_select');
    roleSelect.innerHTML = `
        <option value="">Seleccione un rol</option>
        <option value="1">Administrador</option>
        <option value="2">Supervisor</option>
        <option value="3">Usuario</option>
    `;
}

function asignarPermisos() {
    const roleId = document.getElementById('role_select').value;
    const allPermissions = document.getElementById('allPermissions').checked;
    const statusDiv = document.getElementById('assignmentStatus');

    if (!roleId) {
        statusDiv.innerHTML = '<div class="alert alert-warning">Seleccione un rol</div>';
        return;
    }

    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Asignando...';

    let permissions = [];
    if (!allPermissions) {
        const checkboxes = document.querySelectorAll('.permission-check:checked');
        permissions = Array.from(checkboxes).map(cb => cb.value);
    }

    fetch('/permission/assign-correlativos-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            role_id: roleId,
            permissions: permissions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>¡Éxito!</strong> ${data.message}
                </div>
            `;
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        statusDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    });
}

function cargarEstadoPermisos() {
    const statusDiv = document.getElementById('permissionsStatus');

    // Simulación de carga de estado - aquí deberías hacer una llamada real a tu API
    setTimeout(() => {
        statusDiv.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Permiso</th>
                            <th>Estado</th>
                            <th>Roles Asignados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>correlativos.index</td>
                            <td><span class="badge bg-success">Creado</span></td>
                            <td>Administrador, Supervisor</td>
                        </tr>
                        <tr>
                            <td>correlativos.create</td>
                            <td><span class="badge bg-success">Creado</span></td>
                            <td>Administrador</td>
                        </tr>
                        <tr>
                            <td>correlativos.edit</td>
                            <td><span class="badge bg-success">Creado</span></td>
                            <td>Administrador</td>
                        </tr>
                        <tr>
                            <td>correlativos.destroy</td>
                            <td><span class="badge bg-success">Creado</span></td>
                            <td>Administrador</td>
                        </tr>
                        <tr>
                            <td>correlativos.estadisticas</td>
                            <td><span class="badge bg-success">Creado</span></td>
                            <td>Administrador, Supervisor</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
    }, 1000);
}
</script>
@endpush
