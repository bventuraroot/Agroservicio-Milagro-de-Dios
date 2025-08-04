@php
    $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Nuevo documento')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/form-wizard-icons.js') }}"></script>
@endsection

@section('content')
<style>
    .imagen-producto-select2 {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        vertical-align: middle;
    }
</style>

@php
switch (request('typedocument')) {
    case '6':
        $document = 'Factura';
        break;
    case '8':
        $document = 'Factura de sujeto excluido';
        break;
    case '3':
        $document = 'Crédito Fiscal';
        break;
    default:
        $document = 'Documento';
        break;
}
@endphp

<!-- Default Icons Wizard -->
<div class="mb-4 col-12">
    <h4 class="py-3 mb-4 fw-bold">
        <span class="text-center fw-semibold">Creación de {{ $document }}</span>
    </h4>
    <div class="mt-2 bs-stepper wizard-icons wizard-icons-example">
        <div class="bs-stepper-header">
            <div class="step" data-target="#company-select">
                <button type="button" class="step-trigger" disabled>
                    <span class="bs-stepper-icon">
                        <svg viewBox="0 0 54 54">
                            <use xlink:href='{{ asset('assets/svg/icons/form-wizard-account.svg#wizardAccount') }}'>
                            </use>
                        </svg>
                    </span>
                    <span class="bs-stepper-label">Seleccionar Empresa</span>
                </button>
            </div>
            <div class="line">
                <i class="ti ti-chevron-right"></i>
            </div>
            <div class="step" data-target="#personal-info">
                <button type="button" class="step-trigger" disabled>
                    <span class="bs-stepper-icon">
                        <svg viewBox="0 0 58 54">
                            <use xlink:href='{{ asset('assets/svg/icons/form-wizard-personal.svg#wizardPersonal') }}'>
                            </use>
                        </svg>
                    </span>
                    <span class="bs-stepper-label">Información {{ $document }}</span>
                </button>
            </div>
            <div class="line">
                <i class="ti ti-chevron-right"></i>
            </div>
            <div class="step" data-target="#products" id="step-products">
                <button type="button" id="button-products" class="step-trigger" disabled>
                    <span class="bs-stepper-icon">
                        <svg viewBox="0 0 54 54">
                            <use xlink:href='{{ asset('assets/svg/icons/wizard-checkout-cart.svg#wizardCart') }}'>
                            </use>
                        </svg>
                    </span>
                    <span class="bs-stepper-label">Productos</span>
                </button>
            </div>
            <div class="line">
                <i class="ti ti-chevron-right"></i>
            </div>
            <div class="step" data-target="#review-submit">
                <button type="button" class="step-trigger" disabled>
                    <span class="bs-stepper-icon">
                        <svg viewBox="0 0 54 54">
                            <use xlink:href='{{ asset('assets/svg/icons/form-wizard-submit.svg#wizardSubmit') }}'>
                        </use>
                        </svg>
                    </span>
                    <span class="bs-stepper-label">Revisión & Creación</span>
                </button>
            </div>
        </div>
        <div class="bs-stepper-content">
            <form onSubmit="return false">
                <!-- select company -->
                <div id="company-select" class="content">
                    <input type="hidden" name="iduser" id="iduser" value="{{ Auth::user()->id }}">
                    <div class="row g-5">
                        <div class="col-sm-12">
                            <label for="company" class="form-label">
                                <h6>Empresa</h6>
                            </label>
                            <select class="select2company form-select" id="company" name="company"
                                onchange="aviablenext(this.value)" aria-label="Seleccionar opcion">
                            </select>
                            <input type="hidden" name="typedocument" id="typedocument" value="{{ request('typedocument') }}">
                            <input type="hidden" name="typecontribuyente" id="typecontribuyente">
                            <input type="hidden" name="iva" id="iva">
                            <input type="hidden" name="iva_entre" id="iva_entre">
                            <input type="hidden" name="valcorr" id="valcorr" value="{{ request('corr')!='' ? request('corr') : '' }}">
                            <input type="hidden" name="valdraft" id="valdraft" value="{{ request('draft')!='' ? request('draft') : '' }}">
                            <input type="hidden" name="operation" id="operation" value="{{ request('operation')!='' ? request('operation') : '' }}">
                            <input type="hidden" name="draft_id" id="draft_id" value="{{ $draftId ?: '' }}">
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button class="btn btn-label-secondary btn-prev" disabled> <i
                                    class="ti ti-arrow-left me-sm-1"></i>
                                <span class="align-middle d-sm-inline-block d-none">Previous</span>
                            </button>
                            <button id="step1" class="btn btn-primary btn-next" disabled> <span
                                    class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                    class="ti ti-arrow-right"></i></button>
                        </div>
                    </div>
                </div>

                <!-- details document -->
                <div id="personal-info" class="content">
                    <div class="mb-3 content-header">
                        <h6 class="mb-0">Detalles de {{ $document }}</h6>
                        <small>Ingresa los campos requeridos</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-2">
                            <label class="form-label" for="corr">Correlativo</label>
                            <input type="text" id="corr" name="corr" class="form-control" readonly />
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label" for="date">Fecha</label>
                            <input type="date" id="date" name="date" class="form-control"
                                value="{{ now()->format('Y-m-d') }}" readonly />
                        </div>
                        <div class="col-sm-8">
                            <label for="client" class="form-label">Cliente</label>
                            <select class="select2client form-select" id="client" name="client" onchange="valtrypecontri(this.value)"
                                aria-label="Seleccionar opcion">
                            </select>
                            <input type="hidden" name="typecontribuyenteclient" id="typecontribuyenteclient">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="fpago">Forma de pago</label>
                            <select class="select2" id="fpago" name="fpago" onchange="valfpago(this.value)">
                                <option value="0">Seleccione</option>
                                <option value="1">Contado</option>
                                <option value="2">A crédito</option>
                                <option value="3">Tarjeta</option>
                            </select>
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label" for="acuenta">Venta a cuenta de</label>
                            <input type="text" id="acuenta" name="acuenta" class="form-control"
                                placeholder="" />
                        </div>
                        <div class="col-sm-3" style="display: none;" id="isfcredito">
                            <label class="form-label" for="datefcredito">Fecha</label>
                            <input type="date" id="datefcredito" name="datefcredito" class="form-control"
                                value="{{ now()->format('Y-m-d') }}" />
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                <span class="align-middle d-sm-inline-block d-none">Previous</span>
                            </button>
                            <button id="step2" class="btn btn-primary btn-next"> <span
                                    class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                    class="ti ti-arrow-right"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div id="products" class="content">
                    <div class="mb-3 content-header">
                        <h6 class="mb-0">Productos</h6>
                        <small>Agregue los productos necesarios.</small>
                    </div>
                    <div class="row g-3 col-12" style="margin-bottom: 3%">
                        <div class="col-sm-5">
                            <label class="form-label" for="codesearch">Código de búsqueda</label>
                            <input type="text" id="codesearch" name="codesearch" class="form-control" placeholder="Escanee con la pistola"
                            onchange="searchproductcode(this.value)" onpaste="searchproductcode(this.value)" onkeydown="searchproductcode(this.value)" onkeyup="searchproductcode(this.value)" onkeypress="searchproductcode(this.value)" oninput="searchproductcode(this.value)">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="psearch">Buscar Producto</label>
                            <select class="select2psearch" id="psearch" name="psearch" onchange="searchproduct(this.value)">
                            </select>
                            <input type="hidden" id="productname" name="productname">
                            <input type="hidden" id="marca" name="marca">
                            <input type="hidden" id="productid" name="productid">
                            <input type="hidden" id="productdescription" name="productdescription">
                            <input type="hidden" id="productunitario" name="productunitario">
                            <input type="hidden" id="sumas" value="0" name="sumas">
                            <input type="hidden" id="13iva" value="0" name="13iva">
                            <input type="hidden" id="ivaretenido" value="0" name="ivaretenido">
                            <input type="hidden" id="rentaretenido" value="0" name="rentaretenido">
                            <input type="hidden" id="ventasnosujetas" value="0" name="ventasnosujetas">
                            <input type="hidden" id="ventasexentas" value="0" name="ventasexentas">
                            <input type="hidden" id="ventatotal" value="0" name="ventatotal">
                            <input type="hidden" id="ventatotallhidden" value="0" name="ventatotallhidden">
                            <!-- Campos adicionales para productos especiales -->
                            <input type="hidden" id="reserva" name="reserva" value="">
                            <input type="hidden" id="ruta" name="ruta" value="">
                            <input type="hidden" id="destino" name="destino" value="0">
                            <input type="hidden" id="linea" name="linea" value="0">
                            <input type="hidden" id="Canal" name="Canal" value="">
                            <input type="hidden" id="fee" name="fee" value="0.00">
                        </div>
                        <div class="col-sm-1">
                            <label class="form-label" for="cantidad">Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" min="1" max="10" value="1" class="form-control" onchange="totalamount();">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="typesale">Tipo de venta</label>
                            <select class="form-select" id="typesale" name="typesale" onchange="changetypesale(this.value)">
                                <option value="gravada">Gravadas</option>
                                <option value="exenta">Exenta</option>
                                <option value="nosujeta">No Sujeta</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="precio">Precio Unitario</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" max="10000" placeholder="0.00" class="form-control" onchange="totalamount();">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label" for="ivarete13">Iva 13%</label>
                            <input type="number" id="ivarete13" name="ivarete13" step="0.01" max="10000" placeholder="0.00" class="form-control" onchange="totalamount();">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label" for="ivarete">Iva Percibido</label>
                            <input type="number" id="ivarete" name="ivarete" step="0.01" max="10000" placeholder="0.00" class="form-control" onchange="totalamount();">
                        </div>
                        @if(request('typedocument')==8)
                        <div class="col-sm-2">
                            <label class="form-label" for="rentarete">Renta 10%</label>
                            <input type="number" id="rentarete" name="rentarete" step="0.01" max="10000" placeholder="0.00" class="form-control">
                        </div>
                        @endif
                        <div class="col-sm-3">
                            <label class="form-label" for="total">Total</label>
                            <input type="number" id="total" name="total" step="0.01" max="10000" placeholder="0.00" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 col-12" style="margin-bottom: 3%; display: none;" id="add-information-products">
                        <div class="mb-4 col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0 card-title">Detalles del Producto</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="text-center col-md-4">
                                            <img id="product-image" src="{{ asset('assets/img/products/default.png') }}" alt="Imagen del producto" class="mb-3 img-fluid" style="max-height: 200px;">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="table-responsive">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <th style="width: 35%">Nombre:</th>
                                                            <td id="product-name">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Marca:</th>
                                                            <td id="product-marca">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Proveedor:</th>
                                                            <td id="product-provider">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Precio:</th>
                                                            <td id="product-price">$ 0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4" style="margin-bottom: 5%">
                        <button type="button" class="btn btn-primary" onclick="agregarp()">
                            <span class="ti ti-playlist-add"></span> &nbsp;&nbsp;&nbsp;Agregar
                        </button>
                    </div>

                    <div class="card-datatable table-responsive" id="resultados">
                        <div class="panel">
                            <table class="table table-sm animated table-hover table-striped table-bordered fadeIn" id="tblproduct">
                                <thead class="bg-secondary">
                                    <tr>
                                        <th class="text-center text-white">CANT.</th>
                                        <th class="text-white">DESCRIPCION</th>
                                        <th class="text-right text-white">PRECIO UNIT.</th>
                                        <th class="text-right text-white">NO SUJETAS</th>
                                        <th class="text-right text-white">EXENTAS</th>
                                        <th class="text-right text-white">GRAVADAS</th>
                                        <th class="text-right text-white">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td rowspan="8" colspan="5"></td>
                                        <td class="text-right">SUMAS</td>
                                        <td class="text-center" id="sumasl">$ 0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    @if(request('typedocument')==3 || request('typedocument')==8)
                                    <tr>
                                        <td class="text-right">IVA 13%</td>
                                        <td class="text-center" id="13ival">$ 0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    @endif
                                    @if(request('typedocument')==8)
                                    <tr>
                                        <td class="text-right">(-) Renta 10%</td>
                                        <td class="text-center" id="10rental">$ 0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="text-right">(-) IVA Retenido</td>
                                        <td class="text-center" id="ivaretenidol">$0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Ventas No Sujetas</td>
                                        <td class="text-center" id="ventasnosujetasl">$0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Ventas Exentas</td>
                                        <td class="text-center" id="ventasexentasl">$0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Venta Total</td>
                                        <td class="text-center" id="ventatotall">$ 0.00</td>
                                        <td class="quitar_documents"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button id="step3" class="btn btn-primary btn-next"> <span
                                class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                class="ti ti-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Review -->
                <div id="review-submit" class="content">
                    <div class="mb-3 content-header">
                        <h6 class="mb-0">Revisión & Creación</h6>
                        <small>Revisa la información antes de crear el documento.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Resumen de la {{ $document }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información General</h6>
                                            <p><strong>Empresa:</strong> <span id="resumen-empresa">-</span></p>
                                            <p><strong>Cliente:</strong> <span id="resumen-cliente">-</span></p>
                                            <p><strong>Forma de Pago:</strong> <span id="resumen-pago">-</span></p>
                                            <p><strong>Total:</strong> <span id="resumen-total">$ 0.00</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Productos</h6>
                                            <div id="resumen-productos">
                                                <p>No hay productos agregados</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                <span class="align-middle d-sm-inline-block d-none">Previous</span>
                            </button>
                            <button class="btn btn-success btn-submit" onclick="creardocuments()">Crear {{ $document }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Default Icons Wizard -->
@endsection
