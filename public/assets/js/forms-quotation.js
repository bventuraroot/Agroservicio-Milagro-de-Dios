/**
 * JavaScript para el módulo de Cotizaciones
 */

'use strict';

$(document).ready(function() {
    console.log('Forms Quotation JS loaded');
});

/**
 * Eliminar cotización
 */
function deleteQuotation(id) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
        title: '¿Eliminar Cotización?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Eliminarla!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/cotizaciones/destroy/" + btoa(id),
                method: "GET",
                success: function(response) {
                    if (response.res == 1) {
                        Swal.fire({
                            title: 'Eliminada',
                            text: 'La cotización ha sido eliminada exitosamente',
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else if (response.res == 0) {
                        swalWithBootstrapButtons.fire(
                            'Error!',
                            response.message || 'No se pudo eliminar la cotización',
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    swalWithBootstrapButtons.fire(
                        'Error!',
                        'Error al eliminar la cotización: ' + error,
                        'error'
                    );
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            swalWithBootstrapButtons.fire(
                'Cancelado',
                'La cotización no ha sido eliminada',
                'error'
            );
        }
    });
}

/**
 * Cambiar estado de cotización
 */
function changeStatus(id, status) {
    const statusText = {
        'approved': 'aprobar',
        'rejected': 'rechazar',
        'expired': 'marcar como expirada'
    };

    const statusColors = {
        'approved': 'success',
        'rejected': 'error',
        'expired': 'warning'
    };

    Swal.fire({
        title: `¿Confirmar acción?`,
        text: `¿Está seguro que desea ${statusText[status]} esta cotización?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/cotizaciones/change-status/${id}`,
                method: "PATCH",
                data: {
                    status: status,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: response.message,
                            icon: statusColors[status],
                            confirmButtonText: 'Ok'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al cambiar el estado: ' + error, 'error');
                }
            });
        }
    });
}

/**
 * Enviar cotización por correo
 */
function sendEmailQuotation(id) {
    // Establecer el ID de la cotización seleccionada
    $('#selectedQuotationId').val(id);

    // Limpiar el formulario
    $('#emailForm')[0].reset();

    // Mostrar el modal
    $('#sendEmailModal').modal('show');
}

/**
 * Procesar envío de correo
 */
$(document).on('submit', '#emailForm', function(e) {
    e.preventDefault();

    const quotationId = $('#selectedQuotationId').val();
    const formData = {
        email: $('#email').val(),
        subject: $('#subject').val(),
        message: $('#message').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Validar que se haya ingresado el email
    if (!formData.email) {
        Swal.fire('Error', 'Por favor ingrese un correo electrónico', 'error');
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: 'Enviando...',
        text: 'Por favor espere mientras se envía la cotización',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: `/cotizaciones/send-email/${quotationId}`,
        method: "POST",
        data: formData,
        success: function(response) {
            $('#sendEmailModal').modal('hide');

            if (response.success) {
                Swal.fire({
                    title: 'Enviado!',
                    text: 'La cotización ha sido enviada por correo exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            $('#sendEmailModal').modal('hide');

            let errorMessage = 'Error al enviar la cotización';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            Swal.fire('Error', errorMessage, 'error');
        }
    });
});

/**
 * Convertir cotización a venta
 */
function convertToSale(id) {
    Swal.fire({
        title: '¿Convertir a Venta?',
        text: "Esta acción creará una nueva venta basada en esta cotización",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, convertir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/cotizaciones/convert-to-sale/${id}`,
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Convertida!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'Ok'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Info', response.message, 'info');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al convertir la cotización: ' + error, 'error');
                }
            });
        }
    });
}

/**
 * Filtrar tabla por estado
 */
$(document).on('change', '#StatusFilter', function() {
    const status = $(this).val();
    const table = $('#quotationsTable').DataTable();

    if (status) {
        table.column(6).search(status).draw();
    } else {
        table.column(6).search('').draw();
    }
});

/**
 * Buscar en la tabla
 */
$(document).on('keyup', '#SearchInput', function() {
    const searchTerm = $(this).val();
    const table = $('#quotationsTable').DataTable();
    table.search(searchTerm).draw();
});

/**
 * Inicializar DataTable cuando el documento esté listo
 */
$(document).ready(function() {
    if ($('#quotationsTable').length) {
        $('#quotationsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                    checkboxes: {
                        selectRow: true,
                        selectAll: false
                    }
                },
                {
                    targets: -1,
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[3, 'desc']], // Ordenar por fecha descendente
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                    className: 'btn btn-outline-success',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="ti ti-file-type-pdf me-1"></i>PDF',
                    className: 'btn btn-outline-danger',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    }
                }
            ]
        });
    }
});

/**
 * Cargar datos de cotización para edición
 */
function editQuotation(id) {
    $.ajax({
        url: "/cotizaciones/get-quotation/" + btoa(id),
        method: "GET",
        success: function(response) {
            // Llenar el formulario con los datos de la cotización
            // Esta función se implementaría en la vista de edición
            console.log('Datos de cotización:', response);
        },
        error: function(xhr, status, error) {
            Swal.fire('Error', 'Error al cargar los datos de la cotización', 'error');
        }
    });
}
