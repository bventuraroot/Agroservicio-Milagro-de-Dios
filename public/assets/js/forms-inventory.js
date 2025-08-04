'use strict';

// Definir baseUrl si no está definida (evitar redeclaración)
window.baseUrl = window.baseUrl || window.location.origin + '/';

// Configurar token CSRF para todas las peticiones AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(function () {
    // Prevenir submit automático del formulario cuando se escanea código de barras
    $('#codesearch').on('keydown', function(e) {
        if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    // Búsqueda por código con debounce para evitar múltiples llamadas
    var searchTimeout;
    $('#codesearch').on('input', function() {
        clearTimeout(searchTimeout);
        var value = $(this).val();

        if (value.length >= 3) { // Solo buscar si tiene al menos 3 caracteres
            searchTimeout = setTimeout(function() {
                searchproductcode(value);
            }, 300); // Esperar 300ms después de que el usuario deje de escribir
        }
    });

    // Manejar escaneo de códigos de barras (búsqueda inmediata)
    $('#codesearch').on('change paste', function() {
        var value = $(this).val();
        if (value.length > 0) {
            searchproductcode(value);
        }
    });

    // Función para formatear el estado del select2 (igual que en ventas)
    function formatState(state) {
        if (state.id == 0) {
            return state.text;
        }
        var $state = $(
            '<span><img src="' + window.baseUrl + 'assets/img/products/' + state.title + '" class="imagen-producto-select2" /> ' + state.text + '</span>'
        );
        return $state;
    }

    // Inicializar Select2 para productos (igual que en ventas)
    var selectdpsearch = $(".select2psearch");
    if (selectdpsearch.length) {
        var $this = selectdpsearch;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar Producto",
            dropdownParent: $this.parent(),
            templateResult: formatState
        });
    }

    // Cargar todos los productos al inicio (igual que en ventas)
    $.ajax({
        url: window.baseUrl + "product/getproductall",
        method: "GET",
        success: function (response) {
            $("#psearch").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#psearch").append(
                    '<option value="' +
                        value.id +
                        '" title="' + value.image + '">' +
                        value.name.toUpperCase() + "| Descripción: " + value.description + "| Proveedor: " + value.nameprovider +
                        "</option>"
                );
            });
        },
    });

    // Formulario de agregar inventario
    $('#addinventoryForm').on('submit', function (event) {
        event.preventDefault();

        // Obtener los valores del formulario
        var productid = $('#productid').val();
        var quantity = $('#quantity').val();
        var minimum_stock = $('#minimum_stock').val();
        var location = $('#location').val();

        // Validar que los campos requeridos estén llenos
        if (!productid || !quantity || !minimum_stock) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor complete todos los campos requeridos',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
            return false;
        }

        // Crear FormData
        var formData = new FormData(this);

        $.ajax({
            url: window.baseUrl + 'inve/store',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#addinventoryModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message || 'Inventario creado correctamente',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                }).then(() => {
                    $('.datatables-inventory').DataTable().ajax.reload();
                    $('#addinventoryForm')[0].reset();
                    $('#add-information-products').hide();
                });
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Hubo un error al crear el inventario';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
            }
        });
    });
});

// Función para buscar producto por código (igual que en ventas)
function searchproductcode(codeproduct) {
    if (!codeproduct || codeproduct.trim() === '') return;

    $.ajax({
        url: window.baseUrl + "product/getproductcode/" + btoa(codeproduct),
        method: "GET",
        success: function (response) {
            if (response && response.length > 0) {
                var product = response[0];

                // Actualizar el combobox correctamente
                var $select = $("#psearch");
                var option = new Option(product.name.toUpperCase() + "| Descripción: " + product.description + "| Proveedor: " + product.provider, product.id, true, true);
                $select.empty().append(option).trigger('change');

                // Actualizar campos ocultos
                $("#productid").val(product.id);
                $("#productname").val(product.name);
                $("#productdescription").val(product.description);
                $("#productprice").val(product.price);
                $("#codesearch").val(product.code);

                // Mostrar información del producto
                $("#add-information-products").show();
                $("#product-image").attr("src", window.baseUrl + 'assets/img/products/' + (product.image || 'none.jpg'));
                $("#product-name").html(product.name);
                $("#product-description").html(product.description || '-');
                $("#product-provider").html(product.provider || '-');
                $("#product-price").html('$ ' + parseFloat(product.price).toFixed(2));
            } else {
                // Limpiar campos si no se encuentra el producto
                $("#psearch").empty().trigger("change");
                $("#productid").val('');
                $("#productname").val('');
                $("#productdescription").val('');
                $("#productprice").val('');
                $("#add-information-products").hide();
            }
        },
        error: function(xhr) {
            console.error('Error al buscar producto por código:', xhr);
        }
    });
}

// Función para buscar producto por ID (desde select2) - igual que en ventas
function searchproduct(idpro) {
    if (!idpro || idpro === '') {
        $("#add-information-products").hide();
        return;
    }

    $.ajax({
        url: window.baseUrl + "product/getproductid/" + btoa(idpro),
        method: "GET",
        success: function (response) {
            if (response && response.length > 0) {
                var product = response[0];
                $("#productid").val(product.id);
                $("#productname").val(product.name);
                $("#productdescription").val(product.description);
                $("#productprice").val(product.price);
                $("#codesearch").val(product.code);

                // Mostrar información del producto
                $("#add-information-products").show();
                $("#product-image").attr("src", window.baseUrl + 'assets/img/products/' + (product.image || 'none.jpg'));
                $("#product-name").html(product.name);
                $("#product-description").html(product.description || '-');
                $("#product-provider").html(product.provider || '-');
                $("#product-price").html('$ ' + parseFloat(product.price).toFixed(2));
            }
        },
        error: function(xhr) {
            console.error('Error al buscar producto por ID:', xhr);
        }
    });
}
