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
    <script src="{{ asset('assets/js/forms-quotation-edit.js') }}"></script>
@endsection

@section('title', 'Editar Cotización - ' . $quotation->quote_number)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-edit me-2"></i>Editar Cotización {{ $quotation->quote_number }}
                </h5>
                <div>
                    <a href="{{ route('cotizaciones.show', $quotation->id) }}" class="btn btn-outline-info me-2">
                        <i class="ti ti-eye me-1"></i>Ver Cotización
                    </a>
                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Volver al Listado
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form id="quotationEditForm" method="POST" action="{{ route('cotizaciones.update', $quotation->id) }}">
                    @csrf
                    @method('PATCH')

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
                            <label class="form-label" for="client_id">Cliente <span class="text-danger">*</span></label>
                            <select id="client_id" name="client_id" class="form-select select2" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}"
                                            {{ $client->id == $quotation->client_id ? 'selected' : '' }}>
                                        {{ $client->razonsocial }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Número de Cotización</label>
                            <input type="text" class="form-control" value="{{ $quotation->quote_number }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <input type="text" class="form-control" value="{{ $quotation->getStatusInSpanish() }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="quote_date">Fecha de Cotización <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="quote_date" name="quote_date"
                                   value="{{ $quotation->quote_date->format('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="valid_until">Válida Hasta <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="valid_until" name="valid_until"
                                   value="{{ $quotation->valid_until->format('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="delivery_time">Tiempo de Entrega</label>
                            <input type="text" class="form-control" id="delivery_time" name="delivery_time"
                                   value="{{ $quotation->delivery_time }}" placeholder="ej: 5-7 días hábiles">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="currency">Moneda</label>
                            <select id="currency" name="currency" class="form-select">
                                <option value="USD" {{ $quotation->currency == 'USD' ? 'selected' : '' }}>USD - Dólares</option>
                                <option value="EUR" {{ $quotation->currency == 'EUR' ? 'selected' : '' }}>EUR - Euros</option>
                                <option value="GTQ" {{ $quotation->currency == 'GTQ' ? 'selected' : '' }}>GTQ - Quetzales</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="payment_terms">Términos de Pago</label>
                            <input type="text" class="form-control" id="payment_terms" name="payment_terms"
                                   value="{{ $quotation->payment_terms }}" placeholder="ej: 50% adelanto, 50% contra entrega">
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
                            <label class="form-label" for="product_select">Agregar Producto</label>
                            <select id="product_select" class="form-select select2">
                                <option value="">Buscar producto...</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-name="{{ $product->name }}">
                                        {{ $product->name }} - ${{ number_format($product->price, 2) }}
                                    </option>
                                @endforeach
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
                                @foreach($quotation->details as $detail)
                                    <tr class="product-row" data-index="{{ $loop->index }}">
                                        <td>
                                            <strong>{{ $detail->product->name }}</strong>
                                            <input type="hidden" name="products[{{ $loop->index }}][product_id]" value="{{ $detail->product_id }}">
                                            <input type="hidden" name="products[{{ $loop->index }}][quantity]" value="{{ $detail->quantity }}">
                                            <input type="hidden" name="products[{{ $loop->index }}][unit_price]" value="{{ $detail->unit_price }}">
                                            <input type="hidden" name="products[{{ $loop->index }}][discount_percentage]" value="{{ $detail->discount_percentage }}">
                                        </td>
                                        <td class="text-center">{{ $detail->quantity }}</td>
                                        <td class="text-end">${{ number_format($detail->unit_price, 2) }}</td>
                                        <td class="text-end">
                                            @if($detail->discount_percentage > 0)
                                                {{ $detail->discount_percentage }}%<br>
                                                <small>${{ number_format($detail->discount_amount, 2) }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">${{ number_format($detail->subtotal, 2) }}</td>
                                        <td class="text-end">${{ number_format($detail->tax_amount, 2) }}</td>
                                        <td class="text-end"><strong>${{ number_format($detail->total, 2) }}</strong></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-product" data-index="{{ $loop->index }}">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Notas y Términos -->
                            <div class="mb-3">
                                <label class="form-label" for="notes">Notas Adicionales</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                          placeholder="Información adicional sobre la cotización...">{{ $quotation->notes }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="terms_conditions">Términos y Condiciones</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4"
                                          placeholder="Términos y condiciones específicos...">{{ $quotation->terms_conditions }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Resumen de Totales</h6>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotalAmount">${{ number_format($quotation->subtotal, 2) }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Descuentos:</span>
                                        <span id="discountAmount">${{ number_format($quotation->discount_amount, 2) }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span>IVA (13%):</span>
                                        <span id="taxAmount">${{ number_format($quotation->taxes, 2) }}</span>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-primary" id="totalAmount">${{ number_format($quotation->total_amount, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('cotizaciones.show', $quotation->id) }}" class="btn btn-outline-secondary me-2">
                                        <i class="ti ti-arrow-left me-1"></i>Cancelar
                                    </a>
                                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-info">
                                        <i class="ti ti-list me-1"></i>Ir al Listado
                                    </a>
                                </div>

                                <div>
                                    <button type="button" class="btn btn-outline-primary me-2" onclick="window.open('{{ route('cotizaciones.pdf', $quotation->id) }}', '_blank')">
                                        <i class="ti ti-eye me-1"></i>Vista Previa PDF
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="updateQuotationBtn">
                                        <i class="ti ti-device-floppy me-1"></i>Actualizar Cotización
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

<style>
.select2-container {
    width: 100% !important;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: 1px solid #dee2e6;
}

.card.bg-light .card-body {
    background-color: #f8f9fa;
}

.product-row:hover {
    background-color: #f8f9fa;
}
</style>

<script>
// Pasar datos existentes a JavaScript
window.existingProducts = @json($quotation->details->map(function($detail, $index) {
    return [
        'index' => $index,
        'product_id' => $detail->product_id,
        'product_name' => $detail->product->name,
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount_percentage' => $detail->discount_percentage,
        'discount_amount' => $detail->discount_amount,
        'subtotal' => $detail->subtotal,
        'tax_amount' => $detail->tax_amount,
        'total' => $detail->total
    ];
}));
</script>

@endsection
