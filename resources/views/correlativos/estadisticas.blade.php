@extends('layouts.app')

@section('title', 'Estadísticas de Correlativos')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Estadísticas de Correlativos</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('correlativos.index') }}">Correlativos</a></li>
                        <li class="breadcrumb-item active">Estadísticas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtro de Empresa -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('correlativos.estadisticas') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="empresa_id" class="form-label">Empresa</label>
                                <select name="empresa_id" id="empresa_id" class="form-select" onchange="this.form.submit()">
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ $empresaId == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('correlativos.index') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-list"></i> Ver Lista
                                    </a>
                                    <button type="button" class="btn btn-info" onclick="actualizarEstadisticas()">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Correlativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['total'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['activos'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Agotados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['agotados'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Alertas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($estadisticas['alertas']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    @if(!empty($estadisticas['alertas']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Alertas de Correlativos
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($estadisticas['alertas'] as $alerta)
                            <div class="alert alert-{{ $alerta['tipo'] }} mb-2">
                                <i class="fas fa-info-circle"></i>
                                {{ $alerta['mensaje'] }}
                                <span class="badge bg-secondary">{{ $alerta['restantes'] }} números restantes</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estadísticas por Tipo de Documento -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Correlativos por Tipo de Documento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo de Documento</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Números Restantes</th>
                                    <th class="text-center">Uso Promedio</th>
                                    <th>Estado Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estadisticas['por_tipo'] as $tipo => $datos)
                                    <tr>
                                        <td>
                                            <strong>{{ $tipo }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $datos['total'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $datos['restantes'] < 100 ? 'bg-warning' : 'bg-success' }}">
                                                {{ number_format($datos['restantes']) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $datos['porcentaje_uso'] > 90 ? 'bg-danger' : ($datos['porcentaje_uso'] > 70 ? 'bg-warning' : 'bg-success') }}">
                                                {{ number_format($datos['porcentaje_uso'], 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $colorClass = $datos['porcentaje_uso'] > 90 ? 'bg-danger' : ($datos['porcentaje_uso'] > 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}"
                                                     role="progressbar"
                                                     style="width: {{ $datos['porcentaje_uso'] }}%"
                                                     aria-valuenow="{{ $datos['porcentaje_uso'] }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($datos['porcentaje_uso'], 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                            <p>No hay datos estadísticos disponibles</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Gráfico de Estado de Correlativos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-pie-chart"></i> Distribución por Estado
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="estadoChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('correlativos.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nuevo Correlativo
                        </a>
                        <a href="{{ route('correlativos.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> Ver Todos
                        </a>
                        <button type="button" class="btn btn-info" onclick="exportarDatos()">
                            <i class="fas fa-download"></i> Exportar Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada (Opcional) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table"></i> Resumen Detallado por Correlativo
                    </h5>
                </div>
                <div class="card-body">
                    <div id="detalleCorrelativo" class="table-responsive">
                        <!-- Se carga dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando datos detallados...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de distribución por estado
    const ctx = document.getElementById('estadoChart').getContext('2d');

    const estadoChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Activos', 'Agotados', 'Inactivos', 'Suspendidos'],
            datasets: [{
                data: [
                    {{ $estadisticas['activos'] }},
                    {{ $estadisticas['agotados'] }},
                    {{ $estadisticas['total'] - $estadisticas['activos'] - $estadisticas['agotados'] }},
                    0 // Suspendidos - agregar lógica si es necesario
                ],
                backgroundColor: [
                    '#28a745',
                    '#dc3545',
                    '#6c757d',
                    '#ffc107'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Cargar datos detallados
    cargarDetalleCorrelativos();
});

function actualizarEstadisticas() {
    const empresaId = document.getElementById('empresa_id').value;

    // Mostrar loading
    document.querySelector('.spinner-border').style.display = 'block';

    // Recargar página con nueva empresa
    window.location.href = `{{ route('correlativos.estadisticas') }}?empresa_id=${empresaId}`;
}

function cargarDetalleCorrelativos() {
    const empresaId = {{ $empresaId }};

    fetch(`/api/correlativos/por-empresa?empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <table class="table table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th>Actual</th>
                            <th>Final</th>
                            <th>Restantes</th>
                            <th>Uso %</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (data.length === 0) {
                html += `
                    <tr>
                        <td colspan="8" class="text-center">No hay correlativos para esta empresa</td>
                    </tr>
                `;
            } else {
                data.forEach(correlativo => {
                    const porcentajeClass = correlativo.porcentaje_uso > 90 ? 'text-danger' :
                                          correlativo.porcentaje_uso > 70 ? 'text-warning' : 'text-success';

                    html += `
                        <tr>
                            <td>${correlativo.id}</td>
                            <td><small>${correlativo.tipo}</small></td>
                            <td>${correlativo.serie}</td>
                            <td>${correlativo.actual.toLocaleString()}</td>
                            <td>${correlativo.final.toLocaleString()}</td>
                            <td>
                                <span class="badge ${correlativo.restantes < 100 ? 'bg-warning' : 'bg-success'}">
                                    ${correlativo.restantes.toLocaleString()}
                                </span>
                            </td>
                            <td class="${porcentajeClass}">
                                <strong>${correlativo.porcentaje_uso.toFixed(1)}%</strong>
                            </td>
                            <td>
                                <small class="text-muted">${correlativo.estado}</small>
                            </td>
                        </tr>
                    `;
                });
            }

            html += `
                    </tbody>
                </table>
            `;

            document.getElementById('detalleCorrelativo').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalleCorrelativo').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error al cargar los datos detallados
                </div>
            `;
        });
}

function exportarDatos() {
    const empresaId = {{ $empresaId }};

    // Simular exportación - aquí podrías implementar una descarga real
    fetch(`{{ route('correlativos.api.estadisticas') }}?empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            const dataStr = JSON.stringify(data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});

            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `estadisticas_correlativos_empresa_${empresaId}_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al exportar los datos');
        });
}
</script>

<style>
.border-left-primary {
    border-left: 4px solid #007bff !important;
}
.border-left-success {
    border-left: 4px solid #28a745 !important;
}
.border-left-danger {
    border-left: 4px solid #dc3545 !important;
}
.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}
</style>
@endpush
