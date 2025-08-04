'use strict';

// Definir baseUrl si no está definida (evitar redeclaración)
window.baseUrl = window.baseUrl || window.location.origin + '/';

// Configurar token CSRF para todas las peticiones AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    xhrFields: {
        withCredentials: true
    }
});

$(function () {
    let dt_table = $('.datatables-inventory');

    // Función para cambiar el estado
    window.toggleState = function(id, currentState) {
        const isActive = currentState === true || currentState === 1;
        const newState = isActive ? 'inactivo' : 'activo';

        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas cambiar el estado del inventario a ${newState}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url: window.baseUrl + 'inve/toggle-state/' + id,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            dt_table.DataTable().ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al cambiar el estado del inventario',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                });
            }
        });
    };

    // DataTable with export buttons
    if (dt_table.length) {
        const dt_inventory = dt_table.DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                emptyTable: "No hay datos disponibles",
                zeroRecords: "No se encontraron registros coincidentes",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                error: "Error al cargar los datos",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                lengthMenu: "Mostrar _MENU_ registros",
                search: "Buscar:",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-label-secondary dropdown-toggle mx-3',
                    text: '<i class="ti ti-download me-1 ti-xs"></i> <span class="align-middle">Exportar</span>',
                    buttons: [
                        {
                            extend: 'print',
                            text: '<i class="ti ti-printer me-2" ></i>Imprimir',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
                            customize: function (win) {
                                $(win.document.body)
                                    .css('font-size', '10pt')
                                    .prepend('<img src="' + window.baseUrl + 'assets/img/logo.png" style="position:absolute; top:0; left:0;" />');
                                $(win.document.body)
                                    .find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="ti ti-file-spreadsheet me-2"></i>Csv',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="ti ti-file-text me-2"></i>Pdf',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                        }
                    ]
                }
            ],
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: window.baseUrl + 'inve/list',
                cache: true,
                xhrFields: {
                    withCredentials: true
                },
                error: function (xhr, error, thrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al cargar los datos del inventario',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            },
            columns: [
                { data: 'code', title: 'CÓDIGO' },
                { data: 'name', title: 'NOMBRE' },
                { data: 'description', title: 'DESCRIPCIÓN' },
                {
                    data: 'price',
                    title: 'PRECIO',
                    render: function(data) {
                        return '$ ' + parseFloat(data).toFixed(2);
                    }
                },
                { data: 'type', title: 'TIPO' },
                { data: 'provider_name', title: 'PROVEEDOR' },
                {
                    data: 'quantity',
                    title: 'CANTIDAD',
                    render: function(data, type, row) {
                        return `<span class="${data <= row.minimum_stock ? 'text-danger fw-bold' : ''}">${data}</span>`;
                    }
                },
                { data: 'minimum_stock', title: 'STOCK MÍNIMO' },
                { data: 'location', title: 'UBICACIÓN' },
                {
                    data: 'active',
                    title: 'ESTADO',
                    render: function(data) {
                        const isActive = data === true || data === 1;
                        return `<span class="badge bg-${isActive ? 'success' : 'danger'}">${isActive ? 'Activo' : 'Inactivo'}</span>`;
                    }
                },
                {
                    data: null,
                    title: 'ACCIONES',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center">
                                <a href="javascript:editinventory(${row.id});" class="text-body">
                                    <i class="mx-2 ti ti-edit ti-sm"></i>
                                </a>
                                <div class="dropdown">
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="mx-2 ti ti-dots-vertical ti-sm"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="javascript:toggleState(${row.id}, ${row.active});" class="dropdown-item">
                                            <i class="ti ti-toggle-right ti-sm me-2"></i>
                                            <span class="align-middle">Cambiar Estado</span>
                                        </a>
                                        <a href="javascript:deleteinventory(${row.id});" class="dropdown-item text-danger">
                                            <i class="ti ti-trash ti-sm me-2"></i>
                                            <span class="align-middle">Eliminar</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100]
        });
    }
});

// Función para editar inventario
window.editinventory = function(id) {
    // Limpiar el formulario antes de llenarlo
    $('#editinventoryForm')[0].reset();
    $('#edit_inventoryid').val('');
    $('#edit_quantity').val('');
    $('#edit_minimum_stock').val('');
    $('#edit_location').val('');

    // Guardar el ID del inventario en un campo oculto
    $('#edit_inventoryid').val(id);

    $.ajax({
        url: window.baseUrl + 'inve/edit/' + id,
        type: 'GET',
        success: function(response) {
            // Llenar el formulario de edición con los datos
            if (response.inventory) {
                $('#edit_productid').val(response.inventory.product_id);
                $('#edit_quantity').val(response.inventory.quantity);
                $('#edit_minimum_stock').val(response.inventory.minimum_stock);
                $('#edit_location').val(response.inventory.location);
            }
            // Abrir el modal de edición solo después de llenar los campos
            $('#editinventoryModal').modal('show');
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al cargar los datos del inventario',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
};

// Manejar el submit del formulario de edición
$('#editinventoryForm').on('submit', function(e) {
    e.preventDefault();
    var id = $('#edit_inventoryid').val();
    var data = {
        quantity: $('#edit_quantity').val(),
        minimum_stock: $('#edit_minimum_stock').val(),
        location: $('#edit_location').val(),
        _method: 'PUT'
    };
    $.ajax({
        url: window.baseUrl + 'inve/edit/' + id,
        type: 'PUT',
        data: data,
        success: function(response) {
            $('#editinventoryModal').modal('hide');
            $('.datatables-inventory').DataTable().ajax.reload();
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: response.message,
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
        },
        error: function(xhr) {
            let msg = 'Hubo un error al actualizar el inventario';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
});

// Función para eliminar inventario
window.deleteinventory = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: window.baseUrl + 'inve/edit/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.datatables-inventory').DataTable().ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'El inventario ha sido eliminado correctamente',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al eliminar el inventario',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        }
    });
};
