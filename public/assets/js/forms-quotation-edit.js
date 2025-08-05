/**
 * JavaScript para Editar Cotizaciones
 */

'use strict';

$(document).ready(function() {
    let products = []; // Array para almacenar productos agregados
    let productIndex = 0; // Índice para productos únicos

    // Cargar productos existentes
    if (window.existingProducts) {
        products = window.existingProducts;
        productIndex = products.length;
    }

    // Inicializar Select2
    $('.select2').select2({
        placeholder: function() {
            return $(this).data('placeholder') || 'Seleccione una opción';
        },
        allowClear: true
    });

    // Inicializar Flatpickr para fechas
    if (document.querySelector('#quote_date')) {
        flatpickr('#quote_date', {
            dateFormat: 'Y-m-d'
        });
    }

    if (document.querySelector('#valid_until')) {
        flatpickr('#valid_until', {
            dateFormat: 'Y-m-d'
        });
    }

    // Evento para cuando se selecciona un producto
    $('#product_select').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price') || 0;

        if (selectedOption.val()) {
            $('#product_price').val(parseFloat(price).toFixed(2));
        } else {
            $('#product_price').val('');
        }
    });

    // Evento para agregar producto
    $('#addProductBtn').on('click', function() {
        addProduct();
    });

    // Permitir agregar producto con Enter
    $('#product_quantity, #product_price, #product_discount').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            addProduct();
        }
    });

    // Función para agregar producto
    function addProduct() {
        const productSelect = $('#product_select');
        const selectedOption = productSelect.find('option:selected');
        const productId = selectedOption.val();
        const productName = selectedOption.data('name');
        const quantity = parseInt($('#product_quantity').val()) || 1;
        const unitPrice = parseFloat($('#product_price').val()) || 0;
        const discountPercentage = parseFloat($('#product_discount').val()) || 0;

        // Validaciones
        if (!productId) {
            Swal.fire('Error', 'Por favor seleccione un producto', 'error');
            return;
        }

        if (quantity <= 0) {
            Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
            return;
        }

        if (unitPrice <= 0) {
            Swal.fire('Error', 'El precio debe ser mayor a 0', 'error');
            return;
        }

        // Verificar si el producto ya existe
        const existingProduct = products.find(p => p.product_id === productId);
        if (existingProduct) {
            Swal.fire('Error', 'Este producto ya ha sido agregado', 'error');
            return;
        }

        // Calcular montos
        const subtotal = quantity * unitPrice;
        const discountAmount = subtotal * (discountPercentage / 100);
        const subtotalAfterDiscount = subtotal - discountAmount;
        const taxAmount = subtotalAfterDiscount * 0.13; // 13% IVA
        const total = subtotalAfterDiscount + taxAmount;

        // Crear objeto producto
        const product = {
            index: productIndex++,
            product_id: productId,
            product_name: productName,
            quantity: quantity,
            unit_price: unitPrice,
            discount_percentage: discountPercentage,
            discount_amount: discountAmount,
            subtotal: subtotalAfterDiscount,
            tax_amount: taxAmount,
            total: total
        };

        // Agregar producto al array
        products.push(product);

        // Agregar fila a la tabla
        addProductRow(product);

        // Limpiar campos
        clearProductFields();

        // Actualizar totales
        updateTotals();
    }

    // Función para agregar fila a la tabla
    function addProductRow(product) {
        const row = `
            <tr class="product-row" data-index="${product.index}">
                <td>
                    <strong>${product.product_name}</strong>
                    <input type="hidden" name="products[${product.index}][product_id]" value="${product.product_id}">
                    <input type="hidden" name="products[${product.index}][quantity]" value="${product.quantity}">
                    <input type="hidden" name="products[${product.index}][unit_price]" value="${product.unit_price}">
                    <input type="hidden" name="products[${product.index}][discount_percentage]" value="${product.discount_percentage}">
                </td>
                <td class="text-center">${product.quantity}</td>
                <td class="text-end">$${product.unit_price.toFixed(2)}</td>
                <td class="text-end">
                    ${product.discount_percentage > 0 ? product.discount_percentage + '%' : '-'}
                    ${product.discount_amount > 0 ? '<br><small>$' + product.discount_amount.toFixed(2) + '</small>' : ''}
                </td>
                <td class="text-end">$${product.subtotal.toFixed(2)}</td>
                <td class="text-end">$${product.tax_amount.toFixed(2)}</td>
                <td class="text-end"><strong>$${product.total.toFixed(2)}</strong></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-product" data-index="${product.index}">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#productsTableBody').append(row);
    }

    // Evento para eliminar producto
    $(document).on('click', '.remove-product', function() {
        const index = $(this).data('index');

        Swal.fire({
            title: '¿Eliminar producto?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                removeProduct(index);
            }
        });
    });

    // Función para eliminar producto
    function removeProduct(index) {
        // Eliminar del array
        products = products.filter(p => p.index !== index);

        // Eliminar fila de la tabla
        $(`.product-row[data-index="${index}"]`).remove();

        // Actualizar totales
        updateTotals();
    }

    // Función para limpiar campos de producto
    function clearProductFields() {
        $('#product_select').val('').trigger('change');
        $('#product_quantity').val(1);
        $('#product_price').val('');
        $('#product_discount').val(0);
    }

    // Función para actualizar totales
    function updateTotals() {
        let subtotal = 0;
        let totalDiscount = 0;
        let totalTax = 0;
        let grandTotal = 0;

        products.forEach(product => {
            subtotal += (product.quantity * product.unit_price);
            totalDiscount += product.discount_amount;
            totalTax += product.tax_amount;
            grandTotal += product.total;
        });

        $('#subtotalAmount').text('$' + subtotal.toFixed(2));
        $('#discountAmount').text('$' + totalDiscount.toFixed(2));
        $('#taxAmount').text('$' + totalTax.toFixed(2));
        $('#totalAmount').text('$' + grandTotal.toFixed(2));
    }

    // Evento para enviar formulario
    $('#quotationEditForm').on('submit', function(e) {
        e.preventDefault();

        // Validar que haya al menos un producto
        if (products.length === 0) {
            Swal.fire('Error', 'Debe tener al menos un producto en la cotización', 'error');
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Actualizando...',
            text: 'Por favor espere mientras se actualiza la cotización',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar formulario
        $.ajax({
            url: $(this).attr('action'),
            method: 'PATCH',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Cotización actualizada exitosamente',
                        icon: 'success',
                        confirmButtonText: 'Ver Cotización'
                    }).then(() => {
                        window.location.href = '/cotizaciones/show/' + $('input[name="_method"]').closest('form').attr('action').split('/').pop();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error al actualizar la cotización';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });

    // Inicializar estado
    updateTotals();

    // Re-numerar índices de productos existentes para mantener consistencia
    function reindexProducts() {
        $('#productsTableBody tr').each(function(index) {
            const $row = $(this);
            $row.attr('data-index', index);
            $row.find('input[name^="products["]').each(function() {
                const name = $(this).attr('name');
                const newName = name.replace(/products\[\d+\]/, `products[${index}]`);
                $(this).attr('name', newName);
            });
            $row.find('.remove-product').attr('data-index', index);

            // Actualizar el producto en el array
            if (products[index]) {
                products[index].index = index;
            }
        });
    }

    // Llamar a reindexProducts después de cargar productos existentes
    reindexProducts();
});

// Función para formatear números
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}
