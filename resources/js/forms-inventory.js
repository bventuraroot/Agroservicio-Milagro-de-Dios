'use strict';

$(function () {
    const editinventoryForm = document.getElementById('editinventoryForm');

    if (editinventoryForm) {
        const fv = FormValidation.formValidation(editinventoryForm, {
            fields: {
                quantity: {
                    validators: {
                        notEmpty: {
                            message: 'Por favor ingrese la cantidad'
                        },
                        integer: {
                            message: 'La cantidad debe ser un número entero'
                        },
                        greaterThan: {
                            value: -1,
                            message: 'La cantidad debe ser mayor o igual a 0'
                        }
                    }
                },
                minimum_stock: {
                    validators: {
                        notEmpty: {
                            message: 'Por favor ingrese el stock mínimo'
                        },
                        integer: {
                            message: 'El stock mínimo debe ser un número entero'
                        },
                        greaterThan: {
                            value: -1,
                            message: 'El stock mínimo debe ser mayor o igual a 0'
                        }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: '.mb-3'
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                autoFocus: new FormValidation.plugins.AutoFocus()
            }
        });

        editinventoryForm.addEventListener('submit', function (event) {
            event.preventDefault();
            fv.validate().then(function (status) {
                if (status === 'Valid') {
                    const formData = new FormData(editinventoryForm);
                    const id = formData.get('idedit');

                    $.ajax({
                        url: `/inventory/${id}`,
                        type: 'PUT',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                $('#editinventoryModal').modal('hide');
                                $('.datatables-inventory').DataTable().ajax.reload();
                                toastr.success('Inventario actualizado correctamente');
                            } else {
                                toastr.error('Error al actualizar el inventario');
                            }
                        },
                        error: function () {
                            toastr.error('Error al actualizar el inventario');
                        }
                    });
                }
            });
        });
    }
});

function editinventory(id) {
    $.get(`/inventory/${id}`, function (data) {
        $('#idedit').val(id);
        $('#quantity').val(data.inventory ? data.inventory.quantity : 0);
        $('#minimum_stock').val(data.inventory ? data.inventory.minimum_stock : 0);
        $('#location').val(data.inventory ? data.inventory.location : '');
        $('#editinventoryModal').modal('show');
    });
}

function deleteinventory(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "No podrás revertir esto",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function(result) {
        if (result.value) {
            $.ajax({
                url: '/inventory/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El producto ha sido eliminado.',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    table.ajax.reload();
                }
            });
        }
    });
}

$(document).ready(function() {
    // Inicializar Select2 para proveedores
    $('.select2provider').select2({
        dropdownParent: $('#addinventoryModal'),
        ajax: {
            url: '/inventory/getproviders',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        placeholder: 'Buscar proveedor...',
        minimumInputLength: 1
    });

    // Manejar el envío del formulario de creación
    $('#addinventoryForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#addinventoryModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Producto creado correctamente',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                table.ajax.reload();
                $('#addinventoryForm')[0].reset();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al crear el producto',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
            }
        });
    });
});
