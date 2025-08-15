@extends('layouts.app')

@section('title', 'Ejemplo - Envío de Facturas por Correo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Envío de Facturas por Correo</h4>
                    <p class="card-text">Ejemplo de implementación de la funcionalidad de envío de facturas por correo electrónico</p>
                </div>
                <div class="card-body">

                    <!-- Ejemplo 1: Botón con datos completos -->
                    <div class="mb-4">
                        <h5>Ejemplo 1: Botón con datos del cliente</h5>
                        <button type="button"
                                class="btn btn-primary btn-enviar-correo"
                                data-factura-id="123"
                                data-correo-cliente="cliente@ejemplo.com"
                                data-numero-factura="FAC-001-2024">
                            <i class="fas fa-envelope"></i> Enviar Factura por Correo
                        </button>
                    </div>

                    <!-- Ejemplo 2: Botón sin datos del cliente -->
                    <div class="mb-4">
                        <h5>Ejemplo 2: Botón sin datos del cliente</h5>
                        <button type="button"
                                class="btn btn-success btn-enviar-correo"
                                data-factura-id="456"
                                data-numero-factura="FAC-002-2024">
                            <i class="fas fa-paper-plane"></i> Enviar Factura
                        </button>
                    </div>

                    <!-- Ejemplo 3: Llamada directa desde JavaScript -->
                    <div class="mb-4">
                        <h5>Ejemplo 3: Llamada directa desde JavaScript</h5>
                        <button type="button"
                                class="btn btn-info"
                                onclick="enviarFacturaPorCorreo(789, 'otro@ejemplo.com', 'FAC-003-2024')">
                            <i class="fas fa-envelope-open"></i> Enviar con JavaScript
                        </button>
                    </div>

                    <!-- Ejemplo 4: En una tabla de facturas -->
                    <div class="mb-4">
                        <h5>Ejemplo 4: En una tabla de facturas</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>FAC-001-2024</td>
                                        <td>Juan Pérez</td>
                                        <td>$150.00</td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-enviar-correo"
                                                    data-factura-id="123"
                                                    data-correo-cliente="juan@ejemplo.com"
                                                    data-numero-factura="FAC-001-2024">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>FAC-002-2024</td>
                                        <td>María García</td>
                                        <td>$275.50</td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-enviar-correo"
                                                    data-factura-id="456"
                                                    data-correo-cliente="maria@ejemplo.com"
                                                    data-numero-factura="FAC-002-2024">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Información de la implementación -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información de la Implementación</h6>
                        <ul class="mb-0">
                            <li>Esta funcionalidad usa la configuración de correo existente en tu archivo <code>.env</code></li>
                            <li>No modifica la configuración actual de correo</li>
                            <li>Genera automáticamente el PDF de la factura</li>
                            <li>Envía el correo con el PDF adjunto</li>
                            <li>Incluye validación de email y manejo de errores</li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Incluir el archivo JavaScript -->
<script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>

<script>
// Ejemplo de uso adicional
document.addEventListener('DOMContentLoaded', function() {
    // Puedes agregar lógica adicional aquí

    // Ejemplo: Enviar factura después de una acción específica
    function enviarFacturaDespuesDeAccion(facturaId) {
        // Realizar alguna acción
        console.log('Acción completada, enviando factura...');

        // Enviar la factura
        enviarFacturaPorCorreo(facturaId, '', '');
    }

    // Ejemplo: Botón personalizado
    const btnPersonalizado = document.createElement('button');
    btnPersonalizado.className = 'btn btn-warning';
    btnPersonalizado.innerHTML = '<i class="fas fa-star"></i> Envío Personalizado';
    btnPersonalizado.onclick = function() {
        enviarFacturaPorCorreo(999, 'personalizado@ejemplo.com', 'FAC-PERS-2024');
    };

    document.querySelector('.card-body').appendChild(btnPersonalizado);
});
</script>
@endsection
