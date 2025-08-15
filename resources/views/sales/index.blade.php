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
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-sale-list.js') }}"></script>
    <script>
        function EnviarCorreo(id_factura,correo,numero) {
            (async () => {
                _token = '{{ csrf_token() }}';

                const { value: email } = await Swal.fire({
                    title: 'Mandar comprobante por Correo',
                    input: 'email',
                    inputLabel: 'Correo a Enviar',
                    inputPlaceholder: 'Introduzca el Correo',
                    inputValue: correo
                });

                if (email) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Enviando correo...',
                        text: 'Por favor espere mientras se genera y envía el PDF',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const url = "{{ route('sale.enviar_correo_offline') }}";

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            id_factura: id_factura,
                            email: email,
                            numero: numero,
                            nombre_cliente: '', // Parámetro requerido por la nueva función
                            _token: _token
                        },
                        success: function(response, status) {

                            if (response.success) {
                                Swal.fire({
                                    title: '¡Correo Enviado!',
                                    html: `
                                        <p>Comprobante enviado exitosamente a:</p>
                                        <strong>${email}</strong>
                                        <br><br>
                                        <small class="text-muted">Factura: ${response.data?.numero_factura || numero}</small>
                                    `,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error al enviar el correo',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {

                            let errorMessage = 'Error al enviar el correo';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.status === 404) {
                                errorMessage = 'Función no encontrada. Verifique la configuración.';
                            } else if (xhr.status === 405) {
                                errorMessage = 'Método no permitido. Contacte al administrador.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Error interno del servidor.';
                            } else if (xhr.status === 0) {
                                errorMessage = 'Error de conexión. Verifique su internet.';
                            }

                            Swal.fire({
                                title: 'Error de Envío',
                                html: `
                                    <p>${errorMessage}</p>
                                    <hr>
                                    <small class="text-muted">Código: ${xhr.status}</small>
                                `,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            })()
        }

        function retomarsale(saleId, typeDocumentId) {
            // Usar el mismo patrón original para máxima compatibilidad
            const url = `{{ route('sale.create') }}?corr=${saleId}&draft=true&typedocument=${typeDocumentId}&operation=delete`;
            window.location.href = url;
        }

        function cancelsale(saleId) {
            Swal.fire({
                title: '¿Está seguro?',
                text: 'Esta acción anulará la venta y no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí iría la lógica para anular la venta
                    console.log('Anulando venta:', saleId);
                }
            });
        }

        function ncr(saleId) {
            // Función para crear nota de crédito
            const url = `{{ route('sale.create') }}?ncr_id=${saleId}`;
            window.location.href = url;
        }

        // Cargar borradores de factura pendientes (desde preventas)
        function loadDraftInvoices() {
            const section = document.getElementById('draft-invoices-section');
            const tbody = document.getElementById('draft-invoices-body');

            if (section.style.display === 'none') {
                section.style.display = 'block';
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <i class="ti ti-loader fs-1"></i>
                            <br>
                            Cargando borradores...
                        </td>
                    </tr>
                `;

                // Simular carga de borradores - en una implementación real estos datos vendrían del servidor
                fetch('{{ route("sale.get-draft-preventa", "0") }}')
                    .then(response => response.json())
                    .then(data => {
                        const drafts = data.drafts || [];
                        updateDraftCount(drafts.length);

                        if (drafts.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="mb-2 ti ti-file-invoice fs-1"></i>
                                        <br>
                                        No hay borradores de factura pendientes
                                    </td>
                                </tr>
                            `;
                        } else {
                            tbody.innerHTML = drafts.map(draft => {
                                const clientName = draft.client ?
                                    (draft.client.firstname ?
                                        `${draft.client.firstname} ${draft.client.firstlastname || ''}`.trim() :
                                        draft.client.name_contribuyente || 'Sin nombre'
                                    ) : 'Venta al menudeo';

                                const companyName = draft.company ? draft.company.name : 'N/A';
                                const documentType = draft.typedocument ? draft.typedocument.description : 'N/A';
                                const userName = draft.user ? draft.user.name : 'N/A';
                                const total = parseFloat(draft.totalamount || 0).toFixed(2);
                                const date = new Date(draft.created_at).toLocaleDateString('es-ES');

                                return `
                                    <tr>
                                        <td></td>
                                        <td><strong>#${draft.id}</strong></td>
                                        <td>${clientName}</td>
                                        <td>${companyName}</td>
                                        <td>
                                            <span class="badge bg-info">${documentType}</span>
                                        </td>
                                        <td><strong>$${total}</strong></td>
                                        <td>${date}</td>
                                        <td>${userName}</td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-success me-1"
                                                    onclick="completeDraftInvoice(${draft.id}, ${draft.typedocument_id})"
                                                    title="Completar factura">
                                                <i class="ti ti-check me-1"></i>
                                                Completar
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    onclick="viewDraftDetails(${draft.id})"
                                                    title="Ver detalles">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            }).join('');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="mb-2 ti ti-exclamation-triangle fs-1"></i>
                                    <br>
                                    Error al cargar los borradores
                                </td>
                            </tr>
                        `;
                    });
            } else {
                section.style.display = 'none';
            }
        }

        // Completar un borrador de factura (usando el patrón original)
        function completeDraftInvoice(draftId, typeDocumentId) {
            Swal.fire({
                title: '¿Completar borrador de factura?',
                text: '¿Estás seguro de que deseas completar este borrador y emitir la factura electrónica?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar factura',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Usar el mismo patrón que retomarsale original
                    const url = `{{ route('sale.create') }}?corr=${draftId}&draft=true&typedocument=${typeDocumentId}&operation=delete`;
                    window.location.href = url;
                }
            });
        }

        // Ver detalles de un borrador
        function viewDraftDetails(draftId) {
            fetch(`{{ url('sale/get-draft-preventa') }}/${draftId}`)
                .then(response => response.json())
                .then(data => {
                    const draft = data.draft;

                        let detailsHtml = '';
                        if (draft.details && draft.details.length > 0) {
                            detailsHtml = draft.details.map(detail => {
                                const productName = detail.product ? detail.product.name : 'Producto eliminado';
                                const unitPrice = parseFloat(detail.priceunit || 0).toFixed(2);
                                const total = parseFloat(
                                    (detail.pricesale || 0) +
                                    (detail.nosujeta || 0) +
                                    (detail.exempt || 0) +
                                    (detail.detained13 || 0)
                                ).toFixed(2);

                                return `
                                    <tr>
                                        <td>${productName}</td>
                                        <td class="text-center">${detail.amountp}</td>
                                        <td class="text-end">$${unitPrice}</td>
                                        <td class="text-end">$${total}</td>
                                    </tr>
                                `;
                            }).join('');
                        } else {
                            detailsHtml = '<tr><td colspan="4" class="text-center text-muted">No hay productos</td></tr>';
                        }

                        const clientName = draft.client ?
                            (draft.client.firstname ?
                                `${draft.client.firstname} ${draft.client.firstlastname || ''}`.trim() :
                                draft.client.name_contribuyente || 'Sin nombre'
                            ) : 'Venta al menudeo';

                        const total = parseFloat(draft.totalamount || 0).toFixed(2);

                        Swal.fire({
                            title: `Detalles del Borrador #${draft.id}`,
                            html: `
                                <div class="text-start">
                                    <div class="mb-3 row">
                                        <div class="col-md-6">
                                            <strong>Cliente:</strong><br>
                                            ${clientName}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Empresa:</strong><br>
                                            ${draft.company ? draft.company.name : 'N/A'}
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col-md-6">
                                            <strong>Tipo de Documento:</strong><br>
                                            ${draft.typedocument ? draft.typedocument.description : 'N/A'}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Forma de Pago:</strong><br>
                                            ${draft.waytopay === '1' ? 'Contado' : draft.waytopay === '2' ? 'Crédito' : 'Otro'}
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Productos:</strong>
                                        <div class="mt-2 table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th class="text-center">Cant.</th>
                                                        <th class="text-end">Precio Unit.</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${detailsHtml}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="text-primary">Total: $${total}</h5>
                                    </div>
                                </div>
                            `,
                            width: '600px',
                            showCancelButton: true,
                            confirmButtonText: 'Completar Factura',
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                completeDraftInvoice(draftId, draft.typedocument_id);
                            }
                        });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'No se pudieron cargar los detalles del borrador', 'error');
                });
        }

        // Actualizar contador de borradores
        function updateDraftCount(count) {
            const countBadge = document.getElementById('draft-count');
            if (countBadge) {
                countBadge.textContent = count;
                if (count > 0) {
                    countBadge.style.display = 'inline';
                } else {
                    countBadge.style.display = 'none';
                }
            }
        }

                // Cargar contador inicial al cargar la página
        $(document).ready(function() {
            // Cargar contador de borradores
            loadDraftCounter();

            // Prevenir DataTables en tabla de borradores después de que todo esté cargado
            setTimeout(function() {
                protectDraftTable();
            }, 200);
        });

        // Función para cargar el contador de borradores
        function loadDraftCounter() {
            fetch('{{ route("sale.get-draft-preventa", "0") }}')
                .then(response => response.json())
                .then(data => {
                    const drafts = data.drafts || [];
                    updateDraftCount(drafts.length);
                })
                .catch(error => {
                    console.error('Error cargando contador de borradores:', error);
                });
        }

                // Función para proteger la tabla de borradores de DataTables
        function protectDraftTable() {
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                // Destruir DataTables en tabla de borradores si existe
                const draftTable = $('#draft-invoices-table');
                if (draftTable.length && $.fn.DataTable.isDataTable(draftTable[0])) {
                    draftTable.DataTable().destroy();
                    console.log('DataTable destruido en tabla de borradores');
                }

                // Destruir en cualquier tabla con clase draft-table
                $('.draft-table').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().destroy();
                        console.log('DataTable destruido en tabla draft-table');
                    }
                });

                // Prevenir futuras inicializaciones
                $('.draft-table').off('.dt');
                $('.draft-table').removeClass('dataTable');

                // Proteger tablas marcadas como excluidas
                $('[data-exclude-datatables="true"]').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().destroy();
                        console.log('DataTable destruido en tabla excluida');
                    }
                });

                // Verificación final: asegurar que solo existe UNA instancia de DataTable
                const dataTableInstances = $('.dataTable').length;
                if (dataTableInstances > 1) {
                    console.warn(`Se encontraron ${dataTableInstances} instancias de DataTable. Limpiando...`);
                    $('.dataTable').each(function() {
                        if (!$(this).hasClass('datatables-sale')) {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().destroy();
                                console.log('DataTable no autorizado destruido');
                            }
                        }
                    });
                }
            }
        }
    </script>
@endsection

@section('title', 'Ventas')

@section('page-style')
<style>
    /* Estilos específicos para la tabla de borradores */
    .draft-table {
        border-collapse: separate !important;
    }
    .draft-table thead th {
        position: relative;
    }
    /* Prevenir que DataTables afecte esta tabla */
    .draft-table_wrapper {
        display: none !important;
    }

    /* Protección adicional contra DataTables */
    #draft-invoices-table_wrapper {
        display: none !important;
    }

    /* Asegurar que la tabla de borradores no reciba estilos de DataTables */
    .draft-table .sorting,
    .draft-table .sorting_asc,
    .draft-table .sorting_desc {
        background-image: none !important;
        cursor: default !important;
    }

    /* Prevenir que DataTables procese tablas marcadas como excluidas */
    [data-exclude-datatables="true"] {
        pointer-events: auto !important;
    }

    /* Forzar separación de contextos entre tablas */
    .draft-table table,
    #draft-invoices-table {
        isolation: isolate !important;
    }

    /* Prevenir que DataTables añada clases automáticamente a tablas de borradores */
    .draft-table.dataTable,
    #draft-invoices-table.dataTable {
        display: table !important;
    }

    /* Ocultar cualquier wrapper de DataTables que se pueda generar para borradores */
    .draft-table .dataTables_wrapper,
    #draft-invoices-table_wrapper,
    .draft-table_wrapper {
        display: none !important;
    }
</style>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-receipt me-2"></i>
                Ventas
            </h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
                <div class="col-md-8 text-end">
                    <button id="ticket-auto-toggle"
                            onclick="toggleTicketAuto()"
                            class="btn btn-sm btn-success me-2"
                            title="Activar/Desactivar generación automática de tickets al completar ventas">
                        <i class="ti ti-check me-1"></i>Ticket Auto: ON
                    </button>
                    <button type="button" class="btn btn-outline-warning me-2" onclick="loadDraftInvoices()">
                        <i class="ti ti-file-invoice me-1"></i>
                        Borradores de Factura
                        <span class="badge bg-warning ms-1" id="draft-count">0</span>
                    </button>
                    <!--<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#selectDocumentModal">
                        <i class="ti ti-plus me-1"></i>
                        Nueva Venta
                    </button>-->
                </div>
            </div>
        </div>

        <!-- Sección de Borradores de Factura Pendientes -->
        <div class="card-body" id="draft-invoices-section" style="display: none;">
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="ti ti-info-circle me-2"></i>
                    Borradores de Factura Pendientes (desde Preventas)
                </h6>
                <p class="mb-0">Estos son borradores de factura creados desde el módulo de preventas que están listos para ser completados.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover draft-table" id="draft-invoices-table" data-exclude-datatables="true">
                    <thead class="table-light">
                        <tr>
                            <th>Ver</th>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Empresa</th>
                            <th>Tipo Doc.</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="draft-invoices-body">
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="ti ti-loader fs-1"></i>
                                <br>
                                Cargando borradores...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table datatables-sale border-top nowrap">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>CORRELATIVO</th>
                        <th>FECHA</th>
                        <th>TIPO</th>
                        <th>CLIENTE</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th>FORMA DE PAGO</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($sales)
                        @forelse($sales as $sale)
                            <tr>
                                <td></td>
                                @if ($sale->estadoHacienda=='PROCESADO')
                                <td>{{ $sale->id_doc }}</td>
                                @else
                                <td>{{ $sale->id }}</td>
                                @endif

                                <td>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                                <td>{{ $sale->document_name }}</td>
                                <td>
                                    @switch($sale->tpersona)
                                        @case('N')
                                    {{$sale->firstname . ' ' . $sale->firstlastname}}
                                            @break
                                        @case('J')
                                    {{substr($sale->nameClient,0,30)}}
                                        @break

                                        @default

                                    @endswitch
                                </td>
                                <td>$ {{ number_format($sale->totalamount, 2, '.', ',') }}</td>
                                <td>
                                    @switch($sale->state)
                                        @case(0)
                                            <span class="badge bg-danger">ANULADO</span>
                                        @break

                                        @case(1)
                                            <span class="badge bg-success">CONFIRMADO</span>
                                        @break

                                        @case(2)
                                            <span class="badge bg-warning">PENDIENTE</span>
                                        @break

                                        @case(3)
                                            <span class="badge bg-info">FACTURADO</span>
                                        @break

                                        @default
                                    @endswitch
                                </td>
                                <td>
                                    @switch($sale->waytopay)
                                        @case(1)
                                            <span class="badge bg-primary">CONTADO</span>
                                        @break

                                        @case(2)
                                            <span class="badge bg-secondary">CRÉDITO</span>
                                        @break

                                        @case(3)
                                            <span class="badge bg-info">OTRO</span>
                                        @break

                                        @default
                                    @endswitch
                                </td>
                                <td>
                                    @switch($sale->typesale)
                                        @case(1)
                                        <div class="d-flex align-items-center">
                                            <a href="{{route('sale.print', $sale->id)}}"
                                                    class="btn btn-icon btn-outline-secondary btn-sm me-1" target="_blank" title="Imprimir Factura">
                                                <i class="ti ti-printer"></i>
                                            </a>
                                                                                         <!-- Botón principal: IMPRIME AUTOMÁTICO -->
                                            <a href="javascript:void(0)"
                                               onclick="imprimirTicketAutomatico({{$sale->id}})"
                                               class="btn btn-icon btn-outline-info btn-sm me-1"
                                               title="Imprimir Ticket Automáticamente">
                                                <i class="ti ti-receipt"></i>
                                            </a>

                                            <!-- Botón secundario: SOLO PREVISUALIZAR -->
                                            <a href="{{route('sale.ticket', $sale->id)}}?autoprint=false"
                                               target="_blank"
                                               class="btn btn-icon btn-outline-secondary btn-sm"
                                               title="Solo Previsualizar Ticket">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-icon btn-outline-success btn-sm me-1 btn-enviar-correo"
                                                    data-factura-id="{{ $sale->id }}"
                                                    data-correo-cliente="{{ $sale->mailClient }}"
                                                    data-numero-factura="{{ $sale->id_doc }}"
                                                    title="Enviar factura por correo">
                                                <i class="ti ti-mail"></i>
                                            </button>
                                            <div class="btn-group dropup">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="javascript:cancelsale({{ $sale->id }});" class="dropdown-item">
                                                        <i class="ti ti-x me-2"></i>Anular
                                                    </a>
                                                    @if ($sale->tipoDte=="03"  && $sale->estadoHacienda=='PROCESADO' && $sale->tipoDte!="05" && $sale->relatedSale=="")
                                                    <a href="javascript:ncr({{ $sale->id }});" class="dropdown-item">
                                                        <i class="ti ti-file-minus me-2"></i>Crear Nota de Crédito
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @break

                                        @case(2)
                                        <div class="d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="retomarsale({{ $sale->id }}, {{ $sale->typedocument_id}})">
                                                <i class="ti ti-pencil me-1"></i>Retomar Borrador
                                            </button>
                                        </div>
                                        @break
                                        @case(0)
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted">Sin acciones</span>
                                        </div>
                                        @break

                                        @default
                                    @endswitch
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No hay ventas registradas</td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar tipo de documento -->
    <div class="modal fade" id="selectDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-simple modal-pricing">
          <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
              <div class="mb-4 text-center">
                <h3 class="mb-2">
                    <i class="ti ti-file-text me-2"></i>
                    Documentos disponibles
                </h3>
                <p class="text-muted">Seleccione el tipo de documento que desea crear</p>
              </div>
              <form id="selectDocumentForm" class="row" action="{{Route('sale.create')}}" method="GET">
                @csrf @method('GET')
                <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                <div id="wizard-create-deal" class="mt-2 bs-stepper vertical">
                    <div class="bs-stepper-content">
                        <!-- Deal Type -->
                        <div id="deal-type" class="content">
                          <div class="row g-3">
                            <div class="pt-4 rounded border col-12 d-flex justify-content-center">
                              <img src="{{ asset('assets/img/illustrations/auth-register-illustration-'.$configData['style'].'.png') }}" alt="wizard-create-deal" data-app-light-img="illustrations/auth-register-illustration-light.png" data-app-dark-img="illustrations/auth-register-illustration-dark.png" width="250" class="img-fluid">
                            </div>
                            <div class="pb-2 col-12">
                              <div class="row">
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="factura">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt-2"></i>
                                        <span class="custom-option-title">FACTURA</span>
                                        <small>Creación de factura para personas naturales contribuyentes o no contribuyentes</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="6" id="factura" checked />
                                    </label>
                                  </div>
                                </div>
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="fiscal">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt"></i>
                                        <span class="custom-option-title">COMPROBANTE DE CREDITO FISCAL</span>
                                        <small>Creación de documentos donde necesitas una persona natural o jurídica que declare IVA</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="3" id="fiscal" />
                                    </label>
                                  </div>
                                </div>
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="nota">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt-refund"></i>
                                        <span class="custom-option-title">FACTURAS DE SUJETO EXCLUIDO</span>
                                        <small>Creación de documento para que el impuesto no es aplicable a la operación que se realiza.</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="8" id="nota" />
                                    </label>
                                  </div>
                                </div>
                                <div class="mt-4 col-12 d-flex justify-content-center">
                                    <button class="btn btn-success btn-submit btn-next">
                                        <span class="align-center d-sm-inline-block d-none me-sm-1">Comenzar</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                              </div>
                            </div>
                    </div>
                  </div>
              </form>
            </div>
                    </div>
        </div>
    </div>

    <script>
        // Configuración de ticket automático (se puede cambiar por el usuario)
        let ticketAutoEnabled = localStorage.getItem('ticket_auto_enabled') !== 'false'; // Por defecto activado

        // Función para alternar ticket automático
        function toggleTicketAuto() {
            ticketAutoEnabled = !ticketAutoEnabled;
            localStorage.setItem('ticket_auto_enabled', ticketAutoEnabled);

            const btn = document.getElementById('ticket-auto-toggle');
            if (btn) {
                btn.innerHTML = ticketAutoEnabled ?
                    '<i class="ti ti-check me-1"></i>Ticket Auto: ON' :
                    '<i class="ti ti-x me-1"></i>Ticket Auto: OFF';
                btn.className = ticketAutoEnabled ?
                    'btn btn-sm btn-success' :
                    'btn btn-sm btn-secondary';
            }

            // Mostrar notificación
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: ticketAutoEnabled ? 'Ticket Automático Activado' : 'Ticket Automático Desactivado',
                    text: ticketAutoEnabled ?
                        'Se generará automáticamente después de cada venta' :
                        'No se generará automáticamente',
                    icon: ticketAutoEnabled ? 'success' : 'info',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }

                                                                // Función SIMPLIFICADA: Imprime automáticamente SIN opciones
        function imprimirTicketAutomatico(saleId) {
            console.log('🖨️ Imprimiendo ticket automáticamente para venta #' + saleId);

            // Mostrar notificación de imprimiendo
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Imprimiendo...',
                    icon: 'info',
                    timer: 1000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }

            // Intentar impresión directa del servidor
            const ticketPrintUrl = '{{ route("sale.ticket-print", ":id") }}'.replace(':id', saleId);

            fetch(ticketPrintUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Ticket impreso directamente');

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '✅ ¡Impreso!',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                    } else {
                        // Fallback: abrir ticket para imprimir en navegador
                        console.log('⚠️ Fallback a navegador');
                        const ticketUrl = '{{ route("sale.ticket-direct", ":id") }}'.replace(':id', saleId);
                        window.open(ticketUrl, 'ticket_' + saleId, 'width=400,height=500,scrollbars=no,resizable=no,menubar=no,toolbar=no');
                    }
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    // Fallback: abrir ticket para imprimir en navegador
                    const ticketUrl = '{{ route("sale.ticket-direct", ":id") }}'.replace(':id', saleId);
                    window.open(ticketUrl, 'ticket_' + saleId, 'width=400,height=500,scrollbars=no,resizable=no,menubar=no,toolbar=no');
                });
        }



        // Inicializar estado del botón de ticket automático al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('ticket-auto-toggle');
            if (btn) {
                // Aplicar estado actual
                btn.innerHTML = ticketAutoEnabled ?
                    '<i class="ti ti-check me-1"></i>Ticket Auto: ON' :
                    '<i class="ti ti-x me-1"></i>Ticket Auto: OFF';
                btn.className = ticketAutoEnabled ?
                    'btn btn-sm btn-success me-2' :
                    'btn btn-sm btn-secondary me-2';
            }
        });
    </script>

    <!-- Script para envío de facturas por correo -->
    <script src="{{ asset('assets/js/enviar-factura-correo.js') }}"></script>
@endsection
