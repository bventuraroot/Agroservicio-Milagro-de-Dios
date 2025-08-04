@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js" integrity="sha512-efAcjYoYT0sXxQRtxGY37CKYmqsFVOIwMApaEbrxJr4RwqVVGw8o+Lfh/+59TU07+suZn1BWq4fDl5fdgyCNkw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-marca-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-marca.js') }}"></script>
@endsection

@section('title', 'Marcas')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Marcas</h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-marca border-top">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($marcas)
                        @forelse($marcas as $marca)
                            <tr>
                                <td></td>
                                <td>{{ $marca->name }}</td>
                                <td>{{ $marca->description }}</td>
                                @if($marca->status == 'active')
                                    <td><span class="badge bg-label-success" text-capitalized="">Activo</span></td>
                                @elseif($marca->status == 'desactive')
                                    <td><span class="badge bg-label-danger" text-capitalized="">Desactivado</span></td>
                                @else
                                    <td><span class="badge bg-label-warning" text-capitalized="">{{ $marca->status }}</span></td>
                                @endif
                                <td><div class="d-flex align-items-center">
                                    <a href="javascript: editMarca({{ $marca->id }});" class="dropdown-item"><i
                                        class="ti ti-edit ti-sm me-2"></i>Editar</a>
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                    <div class="m-0 dropdown-menu dropdown-menu-end">
                                        <a href="javascript:deleteMarca({{ $marca->id }});" class="dropdown-item"><i
                                                class="ti ti-eraser ti-sm me-2"></i>Eliminar</a>

                                    </div>
                                </div></td>
                            </tr>
                            @empty
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>No hay datos</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>

            <!-- Add provider Modal -->
<div class="modal fade" id="addMarcaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Crear nueva Marca</h3>
          </div>
          <form id="addMarcaForm" class="row" action="{{Route('marcas.store')}}" method="POST">
            @csrf @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Nombre Marca</label>
              <input type="text" id="name" name="name" class="form-control" placeholder="Nombre Marca" autofocus required/>
            </div>
            <div class="mb-3 col-12">
                <label class="form-label" for="description">Descripción</label>
                <input type="text" id="description" class="form-control" placeholder="Datos adicionales a la marca"
                    aria-label="Description" name="description" />
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

   <!-- Update provider Modal -->
<div class="modal fade" id="updateMarcaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Editar Marca</h3>
          </div>
          <form id="addMarcaForm" class="row" action="{{Route('marcas.update')}}" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" id="idupdate" name="idupdate">
            <div class="mb-3 col-12">
              <label class="form-label" for="nameupdate">Nombre Marca</label>
              <input type="text" id="nameupdate" name="nameupdate" class="form-control" placeholder="Nombre Marca" autofocus required/>
            </div>
            <div class="mb-3 col-12">
                <label class="form-label" for="descriptionupdate">Descripción</label>
                <input type="text" id="descriptionupdate" class="form-control" placeholder="Datos adicionales a la marca"
                    aria-label="Description" name="descriptionupdate" />
            </div>
            <div class="mb-3 col-12">
                <label class="form-label" for="statusupdate">Estado</label>
                <select class="form-select" id="statusupdate" name="statusupdate">
                    <option value="active">Activo</option>
                    <option value="desactive">Desactivado</option>
                </select>
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Actualizar</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

    @endsection
