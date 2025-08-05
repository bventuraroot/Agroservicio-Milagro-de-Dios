@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/forms-quotation-create.js') }}"></script>
@endsection

@section('title', 'Nueva Cotización')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-file-plus me-2"></i>Nueva Cotización
                </h5>
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Volver al Listado
                </a>
            </div>

            <div class="card-body">
                <form id="quotationForm" method="POST" action="{{ route('cotizaciones.store') }}">
                    @csrf

                    <!-- Información General -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-semibold mb-3">
                                <i class="ti ti-info-circle me-2"></i>Información General
                            </h6>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="company_id">Empresa <span class="text-danger">*</span></label>
                            <select id="company_id" name="company_id" class="form-select select2" required>
                                <option value="">Seleccione una empresa</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="client_id">Cliente <span class="text-danger">*</span></label>
                            <select id="client_id" name="client_id" class="form-select select2" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->razonsocial }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="quote_date">Fecha de Cotización <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="quote_date" name="quote_date" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="valid_until">Válida Hasta <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="valid_until" name="valid_until" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="currency">Moneda</label>
                            <select id="currency" name="currency" class="form-select">
                                <option value="USD" selected>USD - Dólares</option>
                                <option value="EUR">EUR - Euros</option>
                                <option value="GTQ">GTQ - Quetzales</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="delivery_time">Tiempo de Entrega</label>
                            <input type="text" class="form-control" id="delivery_time" name="delivery_time" placeholder="ej: 5-7 días hábiles">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="payment_terms">Términos de Pago</label>
                            <input type="text" class="form-control" id="payment_terms" name="payment_terms" placeholder="ej: 50% adelanto, 50% contra entrega">
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-semibold mb-3">
                                <i class="ti ti-package me-2"></i>Productos y Servicios
                            </h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="product_select">Seleccionar Producto</label>
                            <select id="product_select" class="form-select select2">
                                <option value="">Buscar producto...</option>
                                @forelse($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-name="{{ $product->name }}">
                                        {{ $product->name }} - ${{ number_format($product->price, 2) }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay productos disponibles</option>
                                @endforelse
                            </select>


                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="product_quantity">Cantidad</label>
                            <input type="number" class="form-control" id="product_quantity" min="1" value="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="product_price">Precio Unit.</label>
                            <input type="number" class="form-control" id="product_price" step="0.01" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="product_discount">Descuento %</label>
                            <input type="number" class="form-control" id="product_discount" step="0.01" min="0" max="100" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-primary w-100" id="addProductBtn">
                                <i class="ti ti-plus me-1"></i>Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Descuento</th>
                                    <th>Subtotal</th>
                                    <th>IVA (13%)</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- Los productos se agregan dinámicamente aquí -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Mensaje para cuando no hay productos -->
                    <div id="noProductsMessage" class="alert alert-info text-center">
                        <i class="ti ti-info-circle me-2"></i>
                        No hay productos agregados. Seleccione un producto para comenzar.
                    </div>

                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Notas y Términos -->
                            <div class="mb-3">
                                <label class="form-label" for="notes">Notas Adicionales</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Información adicional sobre la cotización..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="terms_conditions">Términos y Condiciones</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" placeholder="Términos y condiciones específicos...">• Esta cotización es válida hasta la fecha indicada.
• Los precios están sujetos a cambio sin previo aviso.
• Una vez aceptada la cotización, se procederá según los términos acordados.</textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Resumen de Totales</h6>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotalAmount">$0.00</span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Descuentos:</span>
                                        <span id="discountAmount">$0.00</span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>IVA (13%):</span>
                                        <span id="taxAmount">$0.00</span>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-primary" id="totalAmount">$0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrow-left me-1"></i>Cancelar
                                </a>

                                <div>
                                    <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                                        <i class="ti ti-eye me-1"></i>Vista Previa
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="saveQuotationBtn">
                                        <i class="ti ti-device-floppy me-1"></i>Guardar Cotización
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden inputs para productos -->
<div id="hiddenProductsContainer"></div>

<style>
.select2-container {
    width: 100% !important;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: 1px solid #dee2e6;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #b6d7ff;
    color: #0c63e4;
}

.card.bg-light .card-body {
    background-color: #f8f9fa;
}

#productsTable {
    display: none;
}

.product-row:hover {
    background-color: #f8f9fa;
}
</style>

@endsection
