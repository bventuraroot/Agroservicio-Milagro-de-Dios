@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/forms-quotation.js') }}"></script>
    <!-- Script para envío de facturas por correo -->
    <script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>
@endsection

@section('title', 'Ver Cotización - ' . $quotation->quote_number)

@section('content')


<div class="row">
    <div class="col-12">
        <!-- Header de la Cotización -->
        <div class="mb-4 card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 card-title">
                        <i class="ti ti-file-text me-2"></i>Cotización {{ $quotation->quote_number }}
                    </h5>
                    <small class="text-muted">
                        Creada el {{ $quotation->created_at->format('d/m/Y H:i') }} por {{ $quotation->user->name }}
                    </small>
                </div>
                <div>
                    @switch($quotation->status)
                        @case('pending')
                            <span class="badge bg-label-warning fs-6">Pendiente</span>
                            @break
                        @case('approved')
                            <span class="badge bg-label-success fs-6">Aprobada</span>
                            @break
                        @case('rejected')
                            <span class="badge bg-label-danger fs-6">Rechazada</span>
                            @break
                        @case('converted')
                            <span class="badge bg-label-info fs-6">Convertida</span>
                            @break
                        @case('expired')
                            <span class="badge bg-label-secondary fs-6">Expirada</span>
                            @break
                    @endswitch
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <!-- Acciones Principales -->
                    <div class="mb-3 col-12">
                        <div class="flex-wrap gap-2 d-flex">
                            <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Volver al Listado
                            </a>

                            @if($quotation->status === 'pending')
                                <a href="{{ route('cotizaciones.edit', $quotation->id) }}" class="btn btn-primary">
                                    <i class="ti ti-edit me-1"></i>Editar
                                </a>
                            @endif

                            <a href="{{ route('cotizaciones.pdf', $quotation->id) }}" class="btn btn-outline-info" target="_blank">
                                <i class="ti ti-file-text me-1"></i>Ver PDF
                            </a>

                            <a href="{{ route('cotizaciones.download', $quotation->id) }}" class="btn btn-outline-success">
                                <i class="ti ti-download me-1"></i>Descargar PDF
                            </a>

                            <button type="button" class="btn btn-outline-primary" onclick="sendEmailQuotation({{ $quotation->id }})">
                                <i class="ti ti-mail me-1"></i>Enviar por Correo
                            </button>

                            <!-- Acciones de Estado -->
                            @if($quotation->status === 'pending')
                                <div class="dropdown">
                                    <button class="btn btn-outline-warning dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-settings me-1"></i>Cambiar Estado
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item text-success" href="javascript:;" onclick="changeStatus({{ $quotation->id }}, 'approved')">
                                                <i class="ti ti-check me-2"></i>Aprobar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="javascript:;" onclick="changeStatus({{ $quotation->id }}, 'rejected')">
                                                <i class="ti ti-x me-2"></i>Rechazar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                            @if($quotation->status === 'approved')
                                <button type="button" class="btn btn-success" onclick="convertToSale({{ $quotation->id }})">
                                    <i class="ti ti-shopping-cart me-1"></i>Convertir a Venta
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la Cotización -->
        <div class="row">
            <!-- Información General -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-info-circle me-2"></i>Información General
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <small class="text-muted">Número de Cotización</small>
                                <p class="mb-0 fw-semibold">{{ $quotation->quote_number }}</p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Empresa</small>
                                <p class="mb-0">{{ $quotation->company->name }}</p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Fecha de Cotización</small>
                                <p class="mb-0">{{ $quotation->quote_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Válida Hasta</small>
                                <p class="mb-0 @if($quotation->isExpired()) text-danger @else text-success @endif">
                                    {{ $quotation->valid_until->format('d/m/Y') }}
                                    @if($quotation->isExpired())
                                        <i class="ti ti-alert-triangle ms-1"></i>
                                    @endif
                                </p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Moneda</small>
                                <p class="mb-0">{{ $quotation->currency }}</p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Estado</small>
                                <p class="mb-0">{{ $quotation->getStatusInSpanish() }}</p>
                            </div>
                            @if($quotation->payment_terms)
                                <div class="col-12">
                                    <small class="text-muted">Términos de Pago</small>
                                    <p class="mb-0">{{ $quotation->payment_terms }}</p>
                                </div>
                            @endif
                            @if($quotation->delivery_time)
                                <div class="col-12">
                                    <small class="text-muted">Tiempo de Entrega</small>
                                    <p class="mb-0">{{ $quotation->delivery_time }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-user me-2"></i>Información del Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <small class="text-muted">Cliente</small>
                                <p class="mb-0 fw-semibold">{{ $quotation->client->razonsocial }}</p>
                            </div>
                            @if($quotation->client->email)
                                <div class="col-12">
                                    <small class="text-muted">Email</small>
                                    <p class="mb-0">
                                        <a href="mailto:{{ $quotation->client->email }}">{{ $quotation->client->email }}</a>
                                    </p>
                                </div>
                            @endif
                            @if($quotation->client->nit)
                                <div class="col-6">
                                    <small class="text-muted">NIT</small>
                                    <p class="mb-0">{{ $quotation->client->nit }}</p>
                                </div>
                            @endif
                            @if($quotation->client->ncr)
                                <div class="col-6">
                                    <small class="text-muted">NCR</small>
                                    <p class="mb-0">{{ $quotation->client->ncr }}</p>
                                </div>
                            @endif
                            @if($quotation->client->address)
                                <div class="col-12">
                                    <small class="text-muted">Dirección</small>
                                    <p class="mb-0">{{ $quotation->client->address->address ?? 'No disponible' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos de la Cotización -->
        <div class="mt-4 card">
            <div class="card-header">
                <h6 class="mb-0 card-title">
                    <i class="ti ti-package me-2"></i>Productos y Servicios
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Descuento</th>
                                <th>Subtotal</th>
                                <th>IVA ({{ $quotation->details->first()->tax_rate ?? 13 }}%)</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($quotation->details->count() > 0)
                                @foreach($quotation->details as $detail)
                                    <tr>
                                        <td>
                                            <strong>{{ $detail->product->name ?? 'Producto no encontrado' }}</strong>
                                            @if($detail->description)
                                                <br><small class="text-muted">{{ $detail->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($detail->quantity, 0) }}</td>
                                        <td class="text-end">${{ number_format($detail->unit_price, 2) }}</td>
                                        <td class="text-end">
                                            @if($detail->discount_percentage > 0)
                                                {{ $detail->discount_percentage }}%<br>
                                                <small class="text-danger">${{ number_format($detail->discount_amount, 2) }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">${{ number_format($detail->subtotal, 2) }}</td>
                                        <td class="text-end">${{ number_format($detail->tax_amount, 2) }}</td>
                                        <td class="text-end"><strong>${{ number_format($detail->total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="ti ti-package me-2"></i>No hay productos en esta cotización
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Totales y Notas -->
        <div class="mt-4 row">
            <!-- Notas -->
            <div class="col-md-8">
                @if($quotation->notes || $quotation->terms_conditions)
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-notes me-2"></i>Notas y Términos
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($quotation->notes)
                                <div class="mb-3">
                                    <strong>Notas Adicionales:</strong>
                                    <p class="mt-2 mb-0">{{ $quotation->notes }}</p>
                                </div>
                            @endif

                            @if($quotation->terms_conditions)
                                <div>
                                    <strong>Términos y Condiciones:</strong>
                                    <div class="mt-2" style="white-space: pre-line;">{{ $quotation->terms_conditions }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Resumen de Totales -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-calculator me-2"></i>Resumen de Totales
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>${{ number_format($quotation->subtotal, 2) }}</span>
                        </div>

                        @if($quotation->discount_amount > 0)
                            <div class="mb-2 d-flex justify-content-between text-danger">
                                <span>Descuento General:</span>
                                <span>-${{ number_format($quotation->discount_amount, 2) }}</span>
                            </div>
                        @endif

                        <div class="mb-2 d-flex justify-content-between">
                            <span>IVA:</span>
                            <span>${{ number_format($quotation->taxes, 2) }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <strong class="fs-5">Total {{ $quotation->currency }}:</strong>
                            <strong class="fs-5 text-primary">${{ number_format($quotation->total_amount, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Enviar por Correo -->
<div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar Cotización por Correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emailForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ $quotation->client->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Asunto</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                               value="Cotización #{{ $quotation->quote_number }} - {{ $quotation->company->name }}">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Mensaje Personalizado</label>
                        <textarea class="form-control" id="message" name="message" rows="4"
                                  placeholder="Agregar un mensaje personalizado (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-send me-1"></i>Enviar Cotización
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar el ID de la cotización -->
<input type="hidden" id="selectedQuotationId" value="{{ $quotation->id }}">

@endsection
