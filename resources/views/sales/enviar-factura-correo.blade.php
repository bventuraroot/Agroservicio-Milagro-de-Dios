@extends('layouts/layoutMaster')

@section('title', 'Envío de Facturas por Correo')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="ti ti-mail me-2"></i>Envío de Facturas por Correo
                    </h4>
                    <p class="card-text">Funcionalidad para enviar facturas por correo electrónico usando la configuración existente del sistema</p>
                </div>
                <div class="card-body">

                    <!-- Información de la funcionalidad -->
                    <div class="alert alert-info mb-4">
                        <h6><i class="ti ti-info-circle me-2"></i>Información de la Implementación</h6>
                        <ul class="mb-0">
                            <li>Esta funcionalidad usa <strong>exactamente</strong> la configuración de correo que ya tienes en tu archivo <code>.env</code></li>
                            <li>No modifica ninguna configuración existente</li>
                            <li>Lee automáticamente MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, etc.</li>
                            <li>Genera automáticamente el PDF de la factura</li>
                            <li>Envía el correo con el PDF adjunto</li>
                            <li>Incluye validación de email y manejo de errores</li>
                        </ul>
                    </div>

                    <!-- Ejemplos de uso -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Ejemplo 1: Botón con datos del cliente</h5>
                                </div>
                                <div class="card-body">
                                    <button type="button"
                                            class="btn btn-primary btn-enviar-correo"
                                            data-factura-id="123"
                                            data-correo-cliente="cliente@ejemplo.com"
                                            data-numero-factura="FAC-001-2024">
                                        <i class="ti ti-mail me-1"></i> Enviar Factura por Correo
                                    </button>
                                    <small class="text-muted d-block mt-2">
                                        Incluye el correo del cliente pre-llenado
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Ejemplo 2: Botón sin datos del cliente</h5>
                                </div>
                                <div class="card-body">
                                    <button type="button"
                                            class="btn btn-success btn-enviar-correo"
                                            data-factura-id="456"
                                            data-numero-factura="FAC-002-2024">
                                        <i class="ti ti-paper-plane me-1"></i> Enviar Factura
                                    </button>
                                    <small class="text-muted d-block mt-2">
                                        El usuario debe ingresar el correo manualmente
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ejemplo de tabla -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title">Ejemplo 3: En una tabla de facturas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Número</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>FAC-001-2024</td>
                                            <td>Juan Pérez</td>
                                            <td>$150.00</td>
                                            <td><span class="badge bg-label-success">Pagada</span></td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-enviar-correo"
                                                        data-factura-id="123"
                                                        data-correo-cliente="juan@ejemplo.com"
                                                        data-numero-factura="FAC-001-2024"
                                                        title="Enviar factura por correo">
                                                    <i class="ti ti-mail"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>FAC-002-2024</td>
                                            <td>María García</td>
                                            <td>$275.50</td>
                                            <td><span class="badge bg-label-warning">Pendiente</span></td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-enviar-correo"
                                                        data-factura-id="456"
                                                        data-correo-cliente="maria@ejemplo.com"
                                                        data-numero-factura="FAC-002-2024"
                                                        title="Enviar factura por correo">
                                                    <i class="ti ti-mail"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>FAC-003-2024</td>
                                            <td>Carlos López</td>
                                            <td>$89.99</td>
                                            <td><span class="badge bg-label-info">Enviada</span></td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-enviar-correo"
                                                        data-factura-id="789"
                                                        data-correo-cliente="carlos@ejemplo.com"
                                                        data-numero-factura="FAC-003-2024"
                                                        title="Enviar factura por correo">
                                                    <i class="ti ti-mail"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Ejemplo de llamada directa -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title">Ejemplo 4: Llamada directa desde JavaScript</h5>
                        </div>
                        <div class="card-body">
                            <button type="button"
                                    class="btn btn-info"
                                    onclick="enviarFacturaPorCorreo(999, 'otro@ejemplo.com', 'FAC-999-2024')">
                                <i class="ti ti-envelope-open me-1"></i> Enviar con JavaScript
                            </button>
                            <small class="text-muted d-block mt-2">
                                Usa la función global <code>enviarFacturaPorCorreo(facturaId, correo, numero)</code>
                            </small>
                        </div>
                    </div>

                    <!-- Código de ejemplo -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title">Código de Ejemplo</h5>
                        </div>
                        <div class="card-body">
                            <h6>HTML del botón:</h6>
                            <pre><code>&lt;button type="button"
        class="btn btn-primary btn-enviar-correo"
        data-factura-id="{{ $sale->id }}"
        data-correo-cliente="{{ $sale->client->email }}"
        data-numero-factura="{{ $sale->numero_control }}"
        title="Enviar factura por correo"&gt;
    &lt;i class="ti ti-mail"&gt;&lt;/i&gt;
&lt;/button&gt;</code></pre>

                            <h6 class="mt-3">JavaScript directo:</h6>
                            <pre><code>enviarFacturaPorCorreo(123, 'cliente@ejemplo.com', 'FAC-001-2024');</code></pre>

                            <h6 class="mt-3">Incluir el script:</h6>
                            <pre><code>&lt;script src="{{ asset('assets/js/enviar-factura-correo.js') }}"&gt;&lt;/script&gt;</code></pre>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
