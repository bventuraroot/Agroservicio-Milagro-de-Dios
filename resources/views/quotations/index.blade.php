@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-quotation-list.js') }}"></script>
    <!-- Script para envío de facturas por correo -->
    <script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>
@endsection

@section('title', 'Cotizaciones')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-file-text me-2"></i>Gestión de Cotizaciones
            </h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 user_role">
                    <select id="StatusFilter" class="form-select text-capitalize">
                        <option value="">Filtrar por Estado</option>
                        <option value="pending">Pendiente</option>
                        <option value="approved">Aprobada</option>
                        <option value="rejected">Rechazada</option>
                        <option value="converted">Convertida</option>
                        <option value="expired">Expirada</option>
                    </select>
                </div>
                <div class="col-md-4 user_plan">
                    <input type="text" id="SearchInput" class="form-control" placeholder="Buscar cotización...">
                </div>
                <div class="col-md-4 user_status">
                    <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary add-new btn-primary" tabindex="0">
                        <span><i class="ti ti-plus me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">Nueva Cotización</span></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table class="datatables-users table border-top" id="quotationsTable">
                <thead>
                    <tr>
                        <th></th>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Válida Hasta</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

            </table>
        </div>
    </div>

    <!-- Modal para Enviar por Correo -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar Cotización por Correo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="emailForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Asunto</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Mensaje Personalizado</label>
                            <textarea class="form-control" id="message" name="message" rows="4"
                                      placeholder="Agregar un mensaje personalizado (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i>Enviar Cotización
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Campo oculto para almacenar el ID de la cotización seleccionada -->
    <input type="hidden" id="selectedQuotationId" value="">

@endsection
