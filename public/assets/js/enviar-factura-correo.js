/**
 * Funcionalidad para enviar facturas por correo electrónico
 * Usa la configuración existente del .env
 */

class EnviarFacturaCorreo {
    constructor() {
        this.init();
    }

    init() {
        // Agregar event listeners si es necesario
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Event listener para botones de envío de correo
        $(document).on('click', '.btn-enviar-correo', (e) => {
            e.preventDefault();
            const facturaId = $(e.target).data('factura-id');
            const correoCliente = $(e.target).data('correo-cliente') || '';
            const numeroFactura = $(e.target).data('numero-factura') || '';
            this.mostrarModalEnvio(facturaId, correoCliente, numeroFactura);
        });
    }

    /**
     * Muestra el modal para enviar factura por correo
     */
    mostrarModalEnvio(facturaId, correoCliente = '', numeroFactura = '') {
        Swal.fire({
            title: 'Enviar Factura por Correo',
            html: `
                <div class="text-start">
                    <label for="email-factura" class="form-label">Correo Electrónico:</label>
                    <input type="email" id="email-factura" class="form-control"
                           placeholder="correo@ejemplo.com" value="${correoCliente}">

                    <label for="nombre-cliente" class="form-label mt-3">Nombre del Cliente (opcional):</label>
                    <input type="text" id="nombre-cliente" class="form-control"
                           placeholder="Nombre del cliente">

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            La factura se enviará como PDF adjunto
                        </small>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const email = document.getElementById('email-factura').value;
                const nombreCliente = document.getElementById('nombre-cliente').value;

                if (!email) {
                    Swal.showValidationMessage('El correo electrónico es requerido');
                    return false;
                }

                if (!this.validarEmail(email)) {
                    Swal.showValidationMessage('El formato del correo no es válido');
                    return false;
                }

                return { email, nombreCliente };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.enviarFactura(facturaId, result.value.email, result.value.nombreCliente, numeroFactura);
            }
        });
    }

    /**
     * Valida el formato del email
     */
    validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Envía la factura por correo
     */
    enviarFactura(facturaId, email, nombreCliente, numeroFactura) {
        // Mostrar loading
        Swal.fire({
            title: 'Enviando factura...',
            text: 'Por favor espere mientras se genera y envía el PDF',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Realizar petición AJAX
        $.ajax({
            url: '/sale/enviar-factura-correo',
            type: 'POST',
            data: {
                id_factura: facturaId,
                email: email,
                nombre_cliente: nombreCliente,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success) {
                    Swal.fire({
                        title: '¡Factura Enviada!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                <p class="mt-3">La factura ha sido enviada exitosamente a:</p>
                                <strong class="text-primary">${email}</strong>
                                <br><br>
                                <small class="text-muted">
                                    Factura: ${response.data?.numero_factura || numeroFactura}<br>
                                    Empresa: ${response.data?.empresa || ''}
                                </small>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message || 'Error al enviar la factura',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: (xhr) => {
                let errorMessage = 'Error al enviar la factura';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        // Errores de validación
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join('\n');
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }

                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    /**
     * Método estático para usar desde otros archivos
     */
    static enviar(facturaId, correoCliente = '', numeroFactura = '') {
        const instance = new EnviarFacturaCorreo();
        instance.mostrarModalEnvio(facturaId, correoCliente, numeroFactura);
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.enviarFacturaCorreo = new EnviarFacturaCorreo();
});

// Función global para usar desde otros archivos
window.enviarFacturaPorCorreo = function(facturaId, correoCliente = '', numeroFactura = '') {
    EnviarFacturaCorreo.enviar(facturaId, correoCliente, numeroFactura);
};
