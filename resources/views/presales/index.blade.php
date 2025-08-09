@extends('layouts/layoutMaster')

@section('title', 'Pre-Ventas - Borradores de Facturación')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('page-style')
<style>
    .form-control:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
    }

    .btn-primary {
        background-color: #696cff;
        border-color: #696cff;
    }

    .btn-primary:hover {
        background-color: #5f62e6;
        border-color: #5f62e6;
    }

    .card {
        border: 0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .table th {
        border-top: none;
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .alert {
        border: none;
        border-radius: 0.5rem;
    }

    .session-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .product-info {
        background: #f8f9fa;
        border-left: 4px solid #696cff;
    }

    .draft-invoice-item {
        transition: all 0.3s ease;
    }

    .draft-invoice-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .imagen-producto-select2 {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        vertical-align: middle;
    }
</style>
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="ti ti-shopping-cart me-2"></i>
                        Pre-Ventas - Borradores de Facturación
                    </h4>
                    <div class="gap-2 d-flex">
                        <button type="button" class="btn btn-outline-primary" onclick="showDailyStats()">
                            <i class="ti ti-chart-bar me-1"></i>
                            Estadísticas
                        </button>
                        <button type="button" class="btn btn-success" onclick="startNewSession()">
                            <i class="ti ti-plus me-1"></i>
                            Nueva Sesión
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Left Panel - Product Entry -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-barcode me-2"></i>
                        Escaneo de Productos
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Session Info -->
                    <div id="session-info" class="alert alert-info" style="display: none;">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <strong>Sesión:</strong> <span id="session-id">#000</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Estado:</strong> <span id="session-status" class="badge badge-success">ACTIVA</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Edad:</strong> <span id="session-age">0 min</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Creada:</strong> <span id="session-created">-</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Tiempo:</strong> <span id="session-time">00:00</span>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelSession()">
                                    <i class="ti ti-x me-1"></i>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Scanner -->
                    <div class="mb-4 row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="barcode-input">
                                <i class="ti ti-barcode me-1"></i>
                                Código de Barras
                            </label>
                            <input type="text"
                                   id="barcode-input"
                                   class="form-control form-control-lg"
                                   placeholder="Escanee con la pistola..."
                                   autofocus>
                            <div class="form-text">Use la pistola de código de barras o escriba manualmente</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="product-name-select">
                                <i class="ti ti-search me-1"></i>
                                Buscar por Nombre
                            </label>
                            <select class="select2psearch form-select form-control-lg" id="product-name-select" name="product-name-select">
                                <option value="">Seleccionar producto</option>
                            </select>
                            <div class="form-text">Búsqueda por nombre de producto</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="quantity-input">Cantidad</label>
                            <input type="number"
                                   id="quantity-input"
                                   class="form-control"
                                   value="1"
                                   min="1"
                                   max="999">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button"
                                    class="btn btn-primary w-100"
                                    onclick="addProduct()"
                                    id="add-product-btn"
                                    disabled>
                                <i class="ti ti-plus me-1"></i>
                                Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div id="product-info" class="mb-4 card" style="display: none;">
                        <div class="card-body">
                            <div class="row">
                                <div class="text-center col-md-3">
                                    <img id="product-image"
                                         src="{{ asset('assets/img/products/default.png') }}"
                                         class="rounded img-fluid"
                                         style="max-height: 100px;">
                                </div>
                                <div class="col-md-9">
                                    <h6 id="product-name" class="mb-1"></h6>
                                    <p id="product-description" class="mb-2 text-muted"></p>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <small class="text-muted">Código:</small>
                                            <div id="product-code" class="fw-bold"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Precio:</small>
                                            <div id="product-price" class="fw-bold text-success"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Stock:</small>
                                            <div id="product-stock" class="fw-bold"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Total:</small>
                                            <div id="product-total" class="fw-bold text-primary"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Sale Items -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="ti ti-list me-2"></i>
                                Productos en la Venta
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="sale-items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cant.</th>
                                            <th>Producto</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sale-items-body">
                                        <tr id="no-items-row">
                                            <td colspan="5" class="py-4 text-center text-muted">
                                                <i class="mb-2 ti ti-shopping-cart fs-1"></i>
                                                <br>
                                                No hay productos en la venta
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Totals & Actions -->
        <div class="col-lg-4">
            <!-- Totals Card -->
            <div class="mb-4 card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-calculator me-2"></i>
                        Totales
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span id="subtotal-amount">$0.00</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span>IVA (13%):</span>
                        <span id="iva-amount">$0.00</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span>No Sujetas:</span>
                        <span id="nosujeta-amount">$0.00</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span>Exentas:</span>
                        <span id="exempt-amount">$0.00</span>
                    </div>
                    <hr>
                    <div class="mb-3 d-flex justify-content-between">
                        <strong>TOTAL:</strong>
                        <strong id="total-amount" class="text-primary fs-5">$0.00</strong>
                    </div>
                </div>
            </div>

            <!-- Finalize Sale Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-file-text me-2"></i>
                        Crear Borrador de Factura
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="typedocument-select">Tipo de Documento</label>
                        <select class="form-select" id="typedocument-select" required>
                            <option value="">Seleccione tipo de documento</option>
                            <option value="6">Factura</option>
                            <option value="8">Nota de Crédito</option>
                            <option value="7">Nota de Débito</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="client-select">Cliente (Opcional)</label>
                        <select class="form-select" id="client-select">
                            <option value="">Sin cliente (Menudeo)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="acuenta-input">A cuenta de</label>
                        <input type="text"
                               id="acuenta-input"
                               class="form-control"
                               placeholder="Venta al menudeo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="payment-method">Forma de Pago</label>
                        <select class="form-select" id="payment-method">
                            <option value="1">Contado</option>
                            <option value="2">A crédito</option>
                            <option value="3">Tarjeta</option>
                        </select>
                    </div>
                    <button type="button"
                            class="mb-2 btn btn-success w-100"
                            onclick="finalizeSale()"
                            id="finalize-btn"
                            disabled>
                        <i class="ti ti-file-text me-1"></i>
                        Crear Borrador de Factura
                    </button>
                    <button type="button"
                            class="btn btn-outline-secondary w-100"
                            onclick="printReceipt()"
                            id="print-btn"
                            disabled>
                        <i class="ti ti-printer me-1"></i>
                        Imprimir Recibo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Start Session Modal -->
<div class="modal fade" id="startSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-play me-2"></i>
                    Iniciar Nueva Sesión
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="modal-company-select">Empresa</label>
                    <select class="form-select" id="modal-company-select" required>
                        <option value="">Seleccione una empresa</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @if($loop->first || (isset(Auth::user()->company_id) && Auth::user()->company_id == $company->id)) selected @endif>{{ $company->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="modal-client-select">Cliente (Opcional)</label>
                    <select class="form-select" id="modal-client-select">
                        <option value="">Sin cliente (Menudeo)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="modal-acuenta">A cuenta de</label>
                    <input type="text"
                           id="modal-acuenta"
                           class="form-control"
                           placeholder="Venta al menudeo">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmStartSession()">
                    <i class="ti ti-play me-1"></i>
                    Iniciar Sesión
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Daily Stats Modal -->
<div class="modal fade" id="dailyStatsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-chart-bar me-2"></i>
                    Estadísticas del Día
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center row">
                    <div class="col-md-4">
                        <div class="text-white card bg-primary">
                            <div class="card-body">
                                <h3 id="total-sales">0</h3>
                                <small>Ventas Totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-white card bg-success">
                            <div class="card-body">
                                <h3 id="menudeo-sales">0</h3>
                                <small>Ventas Menudeo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-white card bg-info">
                            <div class="card-body">
                                <h3 id="total-amount-stats">$0.00</h3>
                                <small>Total Ventas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    // Configuración global para el módulo de pre-ventas
    window.presalesConfig = {
        csrfToken: '{{ csrf_token() }}',
        baseUrl: '{{ url("/") }}',
        routes: {
            startSession: '{{ route("presales.start-session") }}',
            searchProduct: '{{ route("presales.search-product") }}',
            addProduct: '{{ route("presales.add-product") }}',
            getDetails: '{{ route("presales.get-details") }}',
            removeProduct: '{{ route("presales.remove-product") }}',
            finalize: '{{ route("presales.finalize") }}',
            cancel: '{{ route("presales.cancel") }}',
            dailyStats: '{{ route("presales.daily-stats") }}',
            printReceipt: '{{ route("presales.print-receipt") }}',
            clients: '{{ route("presales.clients") }}',
            sessionInfo: '{{ route("presales.session-info") }}'
        }
    };
</script>
<script src="{{ asset('assets/js/presales.js') }}"></script>
@endsection
