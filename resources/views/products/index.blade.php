@php
    $configData = Helper::appClasses();
    use Milon\Barcode\DNS1D;
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .form-label .text-danger {
            font-weight: bold;
        }

        .alert {
            margin-bottom: 1rem;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-product-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-product.js') }}"></script>
    <script src="{{ asset('assets/js/product-validation.js') }}"></script>
    <script>
        $(document).ready(function() {
            var codeInput = $('#code');
            var codeInputEdit = $('#codeedit');
            var barcodeDiv = $('#barcode');
            var barcodeDivEdit = $('#barcodeedit');

            if (!codeInput.length || !barcodeDiv.length) {
                return;
            }

            codeInput.on('input', function() {
                var code = $(this).val();
                if (!code) {
                    barcodeDiv.html('');
                    return;
                }
                var url = '{{ url("barcode") }}/' + code;
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        if (data.error) {
                            barcodeDiv.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                        } else {
                            barcodeDiv.html(data.html);
                        }
                    },
                    error: function() {
                        barcodeDiv.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                    }
                });
            });

            codeInputEdit.on('input', function() {
                var code = $(this).val();
                if (!code) {
                    barcodeDivEdit.html('');
                    return;
                }
                var url = '{{ url("barcode") }}/' + code;
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        if (data.error) {
                            barcodeDivEdit.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                        } else {
                            barcodeDivEdit.html(data.html);
                        }
                    },
                    error: function() {
                        barcodeDivEdit.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                    }
                });
            });

            $('#name').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
            $('#nameedit').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
            $('#codeedit').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            // Validación del formulario de crear producto
            $('#addproductForm').on('submit', function(e) {
                var isValid = true;
                var errorMessages = [];

                // Validar código
                var code = $('#code').val().trim();
                if (!code) {
                    errorMessages.push('El código del producto es obligatorio');
                    $('#code').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#code').removeClass('is-invalid');
                }

                // Validar nombre
                var name = $('#name').val().trim();
                if (!name) {
                    errorMessages.push('El nombre del producto es obligatorio');
                    $('#name').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#name').removeClass('is-invalid');
                }

                // Validar descripción
                var description = $('#description').val().trim();
                if (!description) {
                    errorMessages.push('La descripción del producto es obligatoria');
                    $('#description').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#description').removeClass('is-invalid');
                }

                // Validar clasificación fiscal
                var cfiscal = $('#cfiscal').val();
                if (!cfiscal || cfiscal === 'Seleccione') {
                    errorMessages.push('Debe seleccionar una clasificación fiscal');
                    $('#cfiscal').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#cfiscal').removeClass('is-invalid');
                }

                // Validar tipo
                var type = $('#type').val();
                if (!type || type === 'Seleccione') {
                    errorMessages.push('Debe seleccionar un tipo');
                    $('#type').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#type').removeClass('is-invalid');
                }

                // Validar precio
                var price = $('#price').val().trim();
                if (!price) {
                    errorMessages.push('El precio es obligatorio');
                    $('#price').addClass('is-invalid');
                    isValid = false;
                } else if (isNaN(price) || parseFloat(price) < 0) {
                    errorMessages.push('El precio debe ser un número válido mayor o igual a 0');
                    $('#price').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#price').removeClass('is-invalid');
                }

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        html: errorMessages.join('<br>'),
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
            });

            // Validación del formulario de editar producto
            $('#editproductForm').on('submit', function(e) {
                var isValid = true;
                var errorMessages = [];

                // Validar código
                var code = $('#codeedit').val().trim();
                if (!code) {
                    errorMessages.push('El código del producto es obligatorio');
                    $('#codeedit').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#codeedit').removeClass('is-invalid');
                }

                // Validar nombre
                var name = $('#nameedit').val().trim();
                if (!name) {
                    errorMessages.push('El nombre del producto es obligatorio');
                    $('#nameedit').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#nameedit').removeClass('is-invalid');
                }

                // Validar descripción
                var description = $('#descriptionedit').val().trim();
                if (!description) {
                    errorMessages.push('La descripción del producto es obligatoria');
                    $('#descriptionedit').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#descriptionedit').removeClass('is-invalid');
                }

                // Validar clasificación fiscal
                var cfiscal = $('#cfiscaledit').val();
                if (!cfiscal || cfiscal === 'Seleccione') {
                    errorMessages.push('Debe seleccionar una clasificación fiscal');
                    $('#cfiscaledit').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#cfiscaledit').removeClass('is-invalid');
                }

                // Validar tipo
                var type = $('#typeedit').val();
                if (!type || type === 'Seleccione') {
                    errorMessages.push('Debe seleccionar un tipo');
                    $('#typeedit').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#typeedit').removeClass('is-invalid');
                }

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        html: errorMessages.join('<br>'),
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
            });

            // Limpiar clases de error cuando el usuario empiece a escribir
            $('input, textarea, select').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        });

        var select2marcaredit = $('.select2marcaredit');

        if (select2marcaredit.length) {
            var $this = select2marcaredit;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Marca',
                dropdownParent: $this.parent()
            });
        }

        var select2category = $('.select2category');

        if (select2category.length) {
            var $this = select2category;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Categoría',
                dropdownParent: $this.parent()
            });
        }

        var select2categoryedit = $('.select2categoryedit');

        if (select2categoryedit.length) {
            var $this = select2categoryedit;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Categoría',
                dropdownParent: $this.parent()
            });
        }
    </script>
@endsection

@section('title', 'Productos')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Productos</h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-products border-top">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>IMAGEN</th>
                        <th>CODIGO</th>
                        <th>NOMBRE</th>
                        <th>PRECIO</th>
                        <th>PROVEEDOR</th>
                        <th>MARCA</th>
                        <th>C. FISCAL</th>
                        <th>TIPO</th>
                        <th>CATEGORIA</th>
                        <th>ESTADO</th>
                        <th>DESCRIPCION</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($products)
                        @forelse($products as $product)
                            <tr>
                                <td></td>
                                <td><img src="{{ asset('assets/img/products/' . $product->image) }}" alt="image" width="150px">
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>$ {{ $product->price }}</td>
                                <td>{{ $product->nameprovider }}</td>
                                <td>{{ $product->marcaname }}</td>
                                <td>{{ $product->cfiscal }}</td>
                                <td>{{ $product->type }}</td>
                                <td>{{ $product->category ?? 'Sin categoría' }}</td>
                                <td>
                                    @if($product->state == 1)
                                        <span class="badge bg-label-success">Activo</span>
                                    @else
                                        <span class="badge bg-label-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>{{ $product->description }}</td>
                                <td><div class="d-flex align-items-center">
                                    <a href="javascript: editproduct({{ $product->id }});" class="dropdown-item"><i
                                        class="ti ti-edit ti-sm me-2"></i>Editar</a>
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                    <div class="m-0 dropdown-menu dropdown-menu-end">
                                        @if($product->state == 1)
                                            <a href="javascript:toggleState({{ $product->id }}, 0);" class="dropdown-item"><i
                                                class="ti ti-toggle-left ti-sm me-2"></i>Desactivar</a>
                                        @else
                                            <a href="javascript:toggleState({{ $product->id }}, 1);" class="dropdown-item"><i
                                                class="ti ti-toggle-right ti-sm me-2"></i>Activar</a>
                                        @endif
                                        <a href="javascript:deleteproduct({{ $product->id }});" class="dropdown-item"><i
                                                class="ti ti-eraser ti-sm me-2"></i>Eliminar</a>
                                    </div>
                                </div></td>
                            </tr>
                            @empty
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>No hay datos</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>

            <!-- Add product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Crear nuevo producto</h3>
          </div>

          @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          <form id="addproductForm" class="row" action="{{Route('product.store')}}" method="POST" enctype="multipart/form-data">
            @csrf @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <div class="mb-3 col-6">
                <label class="form-label" for="code">Código <span class="text-danger">*</span></label>
                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="Código del producto" autofocus required value="{{ old('code') }}"/>
                <div class="invalid-feedback">El código del producto es obligatorio</div>
                @error('code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 col-6">
                <div id="barcode" style="max-width: 200px; margin: 0 auto;"></div>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Nombre Producto <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nombre del producto" required value="{{ old('name') }}"/>
              <div class="invalid-feedback">El nombre del producto es obligatorio</div>
              @error('name')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3 col-12">
                <label class="form-label" for="description">Descripción <span class="text-danger">*</span></label>
                <textarea id="description" class="form-control @error('description') is-invalid @enderror" aria-label="Descripción" name="description" rows="3" cols="46" placeholder="Descripción del producto" required>{{ old('description') }}</textarea>
                <div class="invalid-feedback">La descripción del producto es obligatoria</div>
                @error('description')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 col-12">
                <label for="marca" class="form-label">Marca</label>
                <select class="select2marca form-select" id="marca" name="marca"
                    aria-label="Seleccionar opcion">
                </select>
            </div>
            <div class="mb-3 col-12">
                <label for="provider" class="form-label">Proveedor</label>
                <select class="select2provider form-select" id="provider" name="provider"
                    aria-label="Seleccionar opcion">
                </select>
            </div>
            <div class="mb-3 col-12">
                <label for="category" class="form-label">Categoría</label>
                <select class="select2category form-select" id="category" name="category" aria-label="Seleccionar categoría">
                    <option value="">Seleccione una categoría</option>
                    <!-- SEMILLAS Y PLANTAS -->
                    <option value="Semillas de Granos Básicos">Semillas de Granos Básicos</option>
                    <option value="Semillas de Hortalizas">Semillas de Hortalizas</option>
                    <option value="Semillas de Pastos">Semillas de Pastos</option>
                    <option value="Plantas Ornamentales">Plantas Ornamentales</option>
                    <option value="Plantas Frutales">Plantas Frutales</option>
                    <option value="Pilones">Pilones</option>

                    <!-- FERTILIZANTES Y NUTRICIÓN -->
                    <option value="Fertilizantes Químicos">Fertilizantes Químicos</option>
                    <option value="Fertilizantes Orgánicos">Fertilizantes Orgánicos</option>
                    <option value="Abonos Foliares">Abonos Foliares</option>
                    <option value="Micronutrientes">Micronutrientes</option>
                    <option value="Correctores de pH">Correctores de pH</option>
                    <option value="Bioestimulantes">Bioestimulantes</option>

                    <!-- PRODUCTOS FITOSANITARIOS -->
                    <option value="Insecticidas">Insecticidas</option>
                    <option value="Fungicidas">Fungicidas</option>
                    <option value="Herbicidas">Herbicidas</option>
                    <option value="Acaricidas">Acaricidas</option>
                    <option value="Nematicidas">Nematicidas</option>
                    <option value="Reguladores de Crecimiento">Reguladores de Crecimiento</option>
                    <option value="Productos Biológicos">Productos Biológicos</option>

                    <!-- GANADERÍA -->
                    <option value="Alimentos para Ganado">Alimentos para Ganado</option>
                    <option value="Concentrados">Concentrados</option>
                    <option value="Sales Minerales">Sales Minerales</option>
                    <option value="Vitaminas para Animales">Vitaminas para Animales</option>
                    <option value="Medicamentos Veterinarios">Medicamentos Veterinarios</option>
                    <option value="Vacunas">Vacunas</option>
                    <option value="Desparasitantes">Desparasitantes</option>

                    <!-- HERRAMIENTAS Y EQUIPOS -->
                    <option value="Herramientas Manuales">Herramientas Manuales</option>
                    <option value="Bombas de Fumigar">Bombas de Fumigar</option>
                    <option value="Sistemas de Riego">Sistemas de Riego</option>
                    <option value="Maquinaria Agrícola">Maquinaria Agrícola</option>
                    <option value="Repuestos y Accesorios">Repuestos y Accesorios</option>

                    <!-- MATERIALES E INSUMOS -->
                    <option value="Sustratos y Tierra">Sustratos y Tierra</option>
                    <option value="Mallas y Tutores">Mallas y Tutores</option>
                    <option value="Macetas y Contenedores">Macetas y Contenedores</option>
                    <option value="Cintas de Riego">Cintas de Riego</option>
                    <option value="Plásticos Agrícolas">Plásticos Agrícolas</option>
                    <option value="Material de Invernadero">Material de Invernadero</option>

                    <!-- OTROS -->
                    <option value="Análisis de Suelos">Análisis de Suelos</option>
                    <option value="Servicios Técnicos">Servicios Técnicos</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div class="mb-3 col-6">
                <label for="cfiscal" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                <select class="select2cfiscal form-select @error('cfiscal') is-invalid @enderror" id="cfiscal" name="cfiscal"
                    aria-label="Seleccionar opcion" required>
                    <option value="">Seleccione</option>
                    <option value="gravado" {{ old('cfiscal') == 'gravado' ? 'selected' : '' }}>Gravado</option>
                    <option value="exento" {{ old('cfiscal') == 'exento' ? 'selected' : '' }}>Exento</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una clasificación fiscal</div>
                @error('cfiscal')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 col-6">
                <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                <select class="select2type form-select @error('type') is-invalid @enderror" id="type" name="type" aria-label="Seleccionar opcion" required>
                    <option value="">Seleccione</option>
                    <option value="directo" {{ old('type') == 'directo' ? 'selected' : '' }}>Directo</option>
                    <option value="tercero" {{ old('type') == 'tercero' ? 'selected' : '' }}>Tercero</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar un tipo</div>
                @error('type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 col-6">
                <label class="form-label" for="price">Precio <span class="text-danger">*</span></label>
                <input type="number" id="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" step="0.01" min="0"
                    aria-label="Precio $" name="price" required value="{{ old('price') }}"/>
                <div class="invalid-feedback">El precio es obligatorio y debe ser un número válido</div>
                @error('price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                        <div class="mb-3 col-12">
                <x-simple-image-upload
                    name="image"
                    label="Imagen del Producto"
                    :required="false"
                />
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Crear</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

             <!-- Add update Modal -->
<div class="modal fade" id="updateProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Editar producto</h3>
          </div>

          @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          <form id="editproductForm" class="row" action="{{Route('product.update')}}" method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <input type="hidden" name="idedit" id="idedit">
            <div class="mb-3 col-6">
                <label class="form-label" for="codeedit">Código <span class="text-danger">*</span></label>
                <input type="text" id="codeedit" name="codeedit" class="form-control" placeholder="Código del producto" autofocus required/>
                <div class="invalid-feedback">El código del producto es obligatorio</div>
            </div>
            <div class="mb-3 col-6">
                <div id="barcodeedit" style="max-width: 200px; margin: 0 auto;"></div>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="nameedit">Nombre Producto <span class="text-danger">*</span></label>
              <input type="text" id="nameedit" name="nameedit" class="form-control" placeholder="Nombre del producto" autofocus required/>
              <div class="invalid-feedback">El nombre del producto es obligatorio</div>
            </div>
            <div class="mb-3 col-12">
                <label class="form-label" for="descriptionedit">Descripción <span class="text-danger">*</span></label>
                <textarea id="descriptionedit" class="form-control" aria-label="Descripción" name="descriptionedit" rows="3" cols="46" placeholder="Descripción del producto" required></textarea>
                <div class="invalid-feedback">La descripción del producto es obligatoria</div>
            </div>
            <div class="mb-3 col-12">
                <label for="marcaedit" class="form-label">Marca</label>
                <select class="select2marcaredit form-select" id="marcaredit" name="marcaredit"
                    aria-label="Seleccionar opcion">
                </select>
            </div>
            <div class="mb-3 col-12">
                <label for="provideredit" class="form-label">Proveedor</label>
                <select class="select2provideredit form-select" id="provideredit" name="provideredit"
                    aria-label="Seleccionar opcion">
                </select>
            </div>
            <div class="mb-3 col-12">
                <label for="categoryedit" class="form-label">Categoría</label>
                <select class="select2categoryedit form-select" id="categoryedit" name="categoryedit" aria-label="Seleccionar categoría">
                    <option value="">Seleccione una categoría</option>
                    <!-- SEMILLAS Y PLANTAS -->
                    <option value="Semillas de Granos Básicos">Semillas de Granos Básicos</option>
                    <option value="Semillas de Hortalizas">Semillas de Hortalizas</option>
                    <option value="Semillas de Pastos">Semillas de Pastos</option>
                    <option value="Plantas Ornamentales">Plantas Ornamentales</option>
                    <option value="Plantas Frutales">Plantas Frutales</option>
                    <option value="Pilones">Pilones</option>

                    <!-- FERTILIZANTES Y NUTRICIÓN -->
                    <option value="Fertilizantes Químicos">Fertilizantes Químicos</option>
                    <option value="Fertilizantes Orgánicos">Fertilizantes Orgánicos</option>
                    <option value="Abonos Foliares">Abonos Foliares</option>
                    <option value="Micronutrientes">Micronutrientes</option>
                    <option value="Correctores de pH">Correctores de pH</option>
                    <option value="Bioestimulantes">Bioestimulantes</option>

                    <!-- PRODUCTOS FITOSANITARIOS -->
                    <option value="Insecticidas">Insecticidas</option>
                    <option value="Fungicidas">Fungicidas</option>
                    <option value="Herbicidas">Herbicidas</option>
                    <option value="Acaricidas">Acaricidas</option>
                    <option value="Nematicidas">Nematicidas</option>
                    <option value="Reguladores de Crecimiento">Reguladores de Crecimiento</option>
                    <option value="Productos Biológicos">Productos Biológicos</option>

                    <!-- GANADERÍA -->
                    <option value="Alimentos para Ganado">Alimentos para Ganado</option>
                    <option value="Concentrados">Concentrados</option>
                    <option value="Sales Minerales">Sales Minerales</option>
                    <option value="Vitaminas para Animales">Vitaminas para Animales</option>
                    <option value="Medicamentos Veterinarios">Medicamentos Veterinarios</option>
                    <option value="Vacunas">Vacunas</option>
                    <option value="Desparasitantes">Desparasitantes</option>

                    <!-- HERRAMIENTAS Y EQUIPOS -->
                    <option value="Herramientas Manuales">Herramientas Manuales</option>
                    <option value="Bombas de Fumigar">Bombas de Fumigar</option>
                    <option value="Sistemas de Riego">Sistemas de Riego</option>
                    <option value="Maquinaria Agrícola">Maquinaria Agrícola</option>
                    <option value="Repuestos y Accesorios">Repuestos y Accesorios</option>

                    <!-- MATERIALES E INSUMOS -->
                    <option value="Sustratos y Tierra">Sustratos y Tierra</option>
                    <option value="Mallas y Tutores">Mallas y Tutores</option>
                    <option value="Macetas y Contenedores">Macetas y Contenedores</option>
                    <option value="Cintas de Riego">Cintas de Riego</option>
                    <option value="Plásticos Agrícolas">Plásticos Agrícolas</option>
                    <option value="Material de Invernadero">Material de Invernadero</option>

                    <!-- OTROS -->
                    <option value="Análisis de Suelos">Análisis de Suelos</option>
                    <option value="Servicios Técnicos">Servicios Técnicos</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div class="mb-3 col-6">
                <label for="cfiscaledit" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                <select class="select2cfiscaledit form-select" id="cfiscaledit" name="cfiscaledit"
                    aria-label="Seleccionar opcion" required>
                    <option value="">Seleccione</option>
                    <option value="gravado">Gravado</option>
                    <option value="exento">Exento</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una clasificación fiscal</div>
            </div>
            <div class="mb-3 col-6">
                <label for="typeedit" class="form-label">Tipo <span class="text-danger">*</span></label>
                <select class="select2typeedit form-select" id="typeedit" name="typeedit"
                    aria-label="Seleccionar opcion" required>
                    <option value="">Seleccione</option>
                    <option value="directo">Directo</option>
                    <option value="tercero">Tercero</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar un tipo</div>
            </div>
            <div class="mb-3 col-6">
                <label class="form-label" for="priceedit">Precio</label>
                <input type="text" id="priceedit" class="form-control" placeholder="$" aria-label="Precio $" name="priceedit"/>
            </div>
            <div class="mb-3 col-6" id="imageview">
            </div>
            <!-- Preview de imagen actual para edición -->
            <div class="mb-3 col-12">
                <div id="current-image-imageedit"></div>
            </div>
            <div class="mb-3 col-12">
                <x-simple-image-upload
                    name="imageedit"
                    label="Imagen del Producto"
                    :required="false"
                />
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Guardar</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
    @endsection
