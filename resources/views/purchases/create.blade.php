@extends('layouts.app')

@section('title', 'Nueva Compra')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Nueva Compra</h4>
                </div>
                <div class="card-body">
                    <form id="purchaseForm" method="POST" action="{{ route('purchase.store') }}">
                        @csrf

                        <!-- Información General -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="number" class="form-label">Número de Comprobante *</label>
                                <input type="text" class="form-control" id="number" name="number" required>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label">Fecha de Compra *</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="document" class="form-label">Tipo de Documento *</label>
                                <select class="form-select" id="document" name="document" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="6">FACTURA</option>
                                    <option value="3">COMPROBANTE DE CREDITO FISCAL</option>
                                    <option value="9">NOTA DE CREDITO</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="period" class="form-label">Período *</label>
                                <select class="form-select" id="period" name="period" required>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $i == date('n') ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="provider" class="form-label">Proveedor *</label>
                                <select class="form-select" id="provider" name="provider" required>
                                    <option value="">Seleccionar proveedor...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="company" class="form-label">Empresa *</label>
                                <select class="form-select" id="company" name="company" required>
                                    <option value="">Seleccionar empresa...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Productos</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="productsTable">
                                        <thead>
                                            <tr>
                                                <th>Producto *</th>
                                                <th>Cantidad *</th>
                                                <th>Precio Unitario *</th>
                                                <th>Subtotal</th>
                                                <th>IVA (13%)</th>
                                                <th>Total</th>
                                                <th>Fecha Caducidad</th>
                                                <th>Lote</th>
                                                <th>Notas</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productsTableBody">
                                            <!-- Los productos se agregarán dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-primary" id="addProductBtn">
                                    <i class="ti ti-plus"></i> Agregar Producto
                                </button>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="row mb-4">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-end"><span id="subtotal">$0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>IVA (13%):</strong></td>
                                        <td class="text-end"><span id="totalIva">$0.00</span></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td><strong>TOTAL:</strong></td>
                                        <td class="text-end"><span id="totalAmount">$0.00</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-save"></i> Guardar Compra
                                </button>
                                <a href="{{ route('purchase.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-x"></i> Cancelar
                                </a>
                            </div>
                        </div>

                        <!-- Campos ocultos -->
                        <input type="hidden" name="iduser" value="{{ auth()->id() }}">
                        <input type="hidden" name="details" id="detailsInput">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleccionar producto -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="productSearch" placeholder="Buscar producto...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="productSelectionTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Proveedor</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Productos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let products = [];
let selectedProducts = [];
let productRowIndex = 0;

$(document).ready(function() {
    loadProviders();
    loadCompanies();
    loadProducts();

    // Eventos
    $('#addProductBtn').click(addProductRow);
    $('#productSearch').on('input', filterProducts);

    // Validación del formulario
    $('#purchaseForm').submit(function(e) {
        e.preventDefault();

        if (selectedProducts.length === 0) {
            alert('Debe agregar al menos un producto');
            return;
        }

        // Preparar datos para envío
        const details = selectedProducts.map(product => ({
            product_id: product.product_id,
            quantity: parseInt(product.quantity),
            unit_price: parseFloat(product.unit_price),
            expiration_date: product.expiration_date || null,
            batch_number: product.batch_number || null,
            notes: product.notes || null
        }));

        $('#detailsInput').val(JSON.stringify(details));

        // Enviar formulario
        submitForm();
    });
});

function loadProviders() {
    $.get('/providers', function(data) {
        const select = $('#provider');
        data.forEach(provider => {
            select.append(`<option value="${provider.id}">${provider.razonsocial}</option>`);
        });
    });
}

function loadCompanies() {
    $.get('/companies', function(data) {
        const select = $('#company');
        data.forEach(company => {
            select.append(`<option value="${company.id}">${company.name}</option>`);
        });
    });
}

function loadProducts() {
    $.get('/purchase/products', function(response) {
        if (response.success) {
            products = response.products;
            renderProductTable();
        }
    });
}

function renderProductTable() {
    const tbody = $('#productSelectionTable tbody');
    tbody.empty();

    products.forEach(product => {
        const row = `
            <tr>
                <td>${product.code || 'N/A'}</td>
                <td>${product.name}</td>
                <td>${product.provider ? product.provider.razonsocial : 'N/A'}</td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="selectProduct(${product.id})">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function filterProducts() {
    const searchTerm = $('#productSearch').val().toLowerCase();
    const rows = $('#productSelectionTable tbody tr');

    rows.each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(searchTerm));
    });
}

function selectProduct(productId) {
    const product = products.find(p => p.id === productId);
    if (product) {
        addProductRow(product);
        $('#productModal').modal('hide');
    }
}

function addProductRow(product = null) {
    const row = `
        <tr id="productRow_${productRowIndex}">
            <td>
                <input type="text" class="form-control product-name" readonly
                       value="${product ? product.name : ''}"
                       onclick="showProductModal(${productRowIndex})">
                <input type="hidden" class="product-id" value="${product ? product.id : ''}">
            </td>
            <td>
                <input type="number" class="form-control quantity" min="1" value="1"
                       onchange="calculateRowTotal(${productRowIndex})">
            </td>
            <td>
                <input type="number" class="form-control unit-price" min="0" step="0.01"
                       value="${product ? product.price : ''}"
                       onchange="calculateRowTotal(${productRowIndex})">
            </td>
            <td>
                <span class="subtotal">$0.00</span>
            </td>
            <td>
                <span class="iva">$0.00</span>
            </td>
            <td>
                <span class="total">$0.00</span>
            </td>
            <td>
                <input type="date" class="form-control expiration-date"
                       min="${$('#date').val()}"
                       onchange="updateSelectedProduct(${productRowIndex})">
            </td>
            <td>
                <input type="text" class="form-control batch-number"
                       onchange="updateSelectedProduct(${productRowIndex})">
            </td>
            <td>
                <input type="text" class="form-control notes"
                       onchange="updateSelectedProduct(${productRowIndex})">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${productRowIndex})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#productsTableBody').append(row);

    if (product) {
        selectedProducts[productRowIndex] = {
            product_id: product.id,
            quantity: 1,
            unit_price: parseFloat(product.price),
            expiration_date: null,
            batch_number: null,
            notes: null
        };

        calculateRowTotal(productRowIndex);
    }

    productRowIndex++;
}

function showProductModal(rowIndex) {
    currentRowIndex = rowIndex;
    $('#productModal').modal('show');
}

function calculateRowTotal(rowIndex) {
    const row = $(`#productRow_${rowIndex}`);
    const quantity = parseInt(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;

    const subtotal = quantity * unitPrice;
    const iva = subtotal * 0.13;
    const total = subtotal + iva;

    row.find('.subtotal').text(`$${subtotal.toFixed(2)}`);
    row.find('.iva').text(`$${iva.toFixed(2)}`);
    row.find('.total').text(`$${total.toFixed(2)}`);

    updateSelectedProduct(rowIndex);
    calculateTotals();
}

function updateSelectedProduct(rowIndex) {
    const row = $(`#productRow_${rowIndex}`);

    selectedProducts[rowIndex] = {
        product_id: parseInt(row.find('.product-id').val()),
        quantity: parseInt(row.find('.quantity').val()) || 0,
        unit_price: parseFloat(row.find('.unit-price').val()) || 0,
        expiration_date: row.find('.expiration-date').val() || null,
        batch_number: row.find('.batch-number').val() || null,
        notes: row.find('.notes').val() || null
    };
}

function removeProductRow(rowIndex) {
    $(`#productRow_${rowIndex}`).remove();
    delete selectedProducts[rowIndex];
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalIva = 0;
    let totalAmount = 0;

    Object.values(selectedProducts).forEach(product => {
        const rowSubtotal = product.quantity * product.unit_price;
        const rowIva = rowSubtotal * 0.13;
        const rowTotal = rowSubtotal + rowIva;

        subtotal += rowSubtotal;
        totalIva += rowIva;
        totalAmount += rowTotal;
    });

    $('#subtotal').text(`$${subtotal.toFixed(2)}`);
    $('#totalIva').text(`$${totalIva.toFixed(2)}`);
    $('#totalAmount').text(`$${totalAmount.toFixed(2)}`);
}

function submitForm() {
    const formData = new FormData($('#purchaseForm')[0]);

    $.ajax({
        url: $('#purchaseForm').attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Compra creada correctamente');
                window.location.href = '{{ route("purchase.index") }}';
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('Error: ' + (response?.message || 'Error desconocido'));
        }
    });
}
</script>
@endpush
