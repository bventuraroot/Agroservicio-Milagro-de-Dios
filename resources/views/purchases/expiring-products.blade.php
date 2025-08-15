@extends('layoutMaster')

@section('title', 'Productos Próximos a Vencer')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Productos Próximos a Vencer</h4>
                        <div>
                            <button class="btn btn-primary" onclick="refreshData()">
                                <i class="ti ti-refresh"></i> Actualizar
                            </button>
                            <a href="{{ route('purchase.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Estado</label>
                            <select class="form-select" id="statusFilter" onchange="filterByStatus()">
                                <option value="">Todos</option>
                                <option value="critical">Críticos (≤7 días)</option>
                                <option value="warning">Advertencia (8-30 días)</option>
                                <option value="expired">Vencidos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="providerFilter" class="form-label">Proveedor</label>
                            <select class="form-select" id="providerFilter" onchange="filterByProvider()">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="daysFilter" class="form-label">Días para vencer</label>
                            <input type="number" class="form-control" id="daysFilter" min="1" max="365" value="30" onchange="filterByDays()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="ti ti-download"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Críticos</h5>
                                    <h3 id="criticalCount">0</h3>
                                    <small>≤7 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Advertencia</h5>
                                    <h3 id="warningCount">0</h3>
                                    <small>8-30 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Vencidos</h5>
                                    <h3 id="expiredCount">0</h3>
                                    <small>Ya vencidos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total</h5>
                                    <h3 id="totalCount">0</h3>
                                    <small>Productos</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="expiringProductsTable">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>Cantidad</th>
                                    <th>Fecha Caducidad</th>
                                    <th>Días Restantes</th>
                                    <th>Estado</th>
                                    <th>Lote</th>
                                    <th>Ubicación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="expiringProductsTableBody">
                                <!-- Los productos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del producto -->
<div class="modal fade" id="productDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailModalBody">
                <!-- Los detalles se cargarán dinámicamente -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let expiringProducts = [];
let filteredProducts = [];

$(document).ready(function() {
    loadExpiringProducts();
    loadProviders();
});

function loadExpiringProducts() {
    $.get('/purchase/expiring-products', function(response) {
        if (response.success) {
            expiringProducts = [];

            // Combinar productos críticos y de advertencia
            response.data.critical.forEach(product => {
                product.status = 'critical';
                expiringProducts.push(product);
            });

            response.data.warning.forEach(product => {
                product.status = 'warning';
                expiringProducts.push(product);
            });

            // Cargar productos vencidos
            $.get('/purchase/expired-products', function(expiredResponse) {
                if (expiredResponse.success) {
                    expiredResponse.data.forEach(product => {
                        product.status = 'expired';
                        expiringProducts.push(product);
                    });
                }

                filteredProducts = [...expiringProducts];
                renderTable();
                updateStatistics();
            });
        }
    });
}

function loadProviders() {
    $.get('/providers', function(data) {
        const select = $('#providerFilter');
        data.forEach(provider => {
            select.append(`<option value="${provider.id}">${provider.razonsocial}</option>`);
        });
    });
}

function renderTable() {
    const tbody = $('#expiringProductsTableBody');
    tbody.empty();

    filteredProducts.forEach(product => {
        const daysUntilExpiration = product.getDaysUntilExpiration;
        const statusColor = getStatusColor(product.status);
        const statusText = getStatusText(product.status);

        const row = `
            <tr>
                <td>
                    <strong>${product.product ? product.product.name : 'N/A'}</strong>
                    <br><small class="text-muted">${product.product ? product.product.code : 'N/A'}</small>
                </td>
                <td>${product.product && product.product.provider ? product.product.provider.razonsocial : 'N/A'}</td>
                <td>
                    <span class="badge bg-primary">${product.quantity}</span>
                </td>
                <td>
                    ${product.expiration_date ? new Date(product.expiration_date).toLocaleDateString('es-ES') : 'N/A'}
                </td>
                <td>
                    <span class="badge bg-${statusColor}">
                        ${daysUntilExpiration !== null ? Math.abs(daysUntilExpiration) + ' días' : 'N/A'}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${statusColor}">${statusText}</span>
                </td>
                <td>${product.batch_number || 'N/A'}</td>
                <td>${product.location || 'N/A'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showProductDetails(${product.id})">
                        <i class="ti ti-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="showInventoryAdjustment(${product.id})">
                        <i class="ti ti-edit"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function updateStatistics() {
    const critical = filteredProducts.filter(p => p.status === 'critical').length;
    const warning = filteredProducts.filter(p => p.status === 'warning').length;
    const expired = filteredProducts.filter(p => p.status === 'expired').length;
    const total = filteredProducts.length;

    $('#criticalCount').text(critical);
    $('#warningCount').text(warning);
    $('#expiredCount').text(expired);
    $('#totalCount').text(total);
}

function getStatusColor(status) {
    return {
        'critical': 'danger',
        'warning': 'warning',
        'expired': 'secondary'
    }[status] || 'secondary';
}

function getStatusText(status) {
    return {
        'critical': 'Crítico',
        'warning': 'Advertencia',
        'expired': 'Vencido'
    }[status] || 'Desconocido';
}

function filterByStatus() {
    const status = $('#statusFilter').val();

    if (status === '') {
        filteredProducts = [...expiringProducts];
    } else {
        filteredProducts = expiringProducts.filter(p => p.status === status);
    }

    renderTable();
    updateStatistics();
}

function filterByProvider() {
    const providerId = $('#providerFilter').val();

    if (providerId === '') {
        filteredProducts = [...expiringProducts];
    } else {
        filteredProducts = expiringProducts.filter(p =>
            p.product && p.product.provider && p.product.provider.id == providerId
        );
    }

    renderTable();
    updateStatistics();
}

function filterByDays() {
    const days = parseInt($('#daysFilter').val()) || 30;

    // Recargar datos con el nuevo filtro de días
    $.get(`/purchase/expiring-products?days=${days}`, function(response) {
        if (response.success) {
            expiringProducts = [];

            response.data.critical.forEach(product => {
                product.status = 'critical';
                expiringProducts.push(product);
            });

            response.data.warning.forEach(product => {
                product.status = 'warning';
                expiringProducts.push(product);
            });

            filteredProducts = [...expiringProducts];
            renderTable();
            updateStatistics();
        }
    });
}

function showProductDetails(inventoryId) {
    // Aquí puedes implementar la lógica para mostrar detalles del producto
    alert('Funcionalidad de detalles en desarrollo');
}

function showInventoryAdjustment(inventoryId) {
    // Aquí puedes implementar la lógica para ajustar el inventario
    alert('Funcionalidad de ajuste en desarrollo');
}

function refreshData() {
    loadExpiringProducts();
}

function exportToExcel() {
    // Aquí puedes implementar la exportación a Excel
    alert('Funcionalidad de exportación en desarrollo');
}
</script>
@endpush
