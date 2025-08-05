/**
 * App Quotation List
 */

'use strict';

// Variable global para evitar reinicialización
var quotationsDataTable = null;

$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_quotations_table = $('#quotationsTable');

  // Quotations datatable
  if (dt_quotations_table.length && !quotationsDataTable) {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable(dt_quotations_table)) {
      dt_quotations_table.DataTable().destroy();
    }

    quotationsDataTable = dt_quotations_table.DataTable({
      ajax: {
        url: baseUrl + 'cotizaciones/get-quotations',
        type: 'GET'
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'quote_number' },
        { data: 'client' },
        { data: 'quote_date' },
        { data: 'valid_until' },
        { data: 'total_amount' },
        { data: 'status' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // Quote Number
          targets: 1,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            var $quote_number = full['quote_number'];
            return '<span class="fw-semibold text-primary">' + $quote_number + '</span>';
          }
        },
        {
          // Client
          targets: 2,
          responsivePriority: 2,
          render: function (data, type, full, meta) {
            var $client = full['client'];
            var $client_name = $client.razonsocial;
            var $client_email = $client.email || '';

            return (
              '<div class="d-flex flex-column">' +
              '<span class="fw-semibold">' + $client_name + '</span>' +
              ($client_email ? '<small class="text-muted">' + $client_email + '</small>' : '') +
              '</div>'
            );
          }
        },
        {
          // Quote Date
          targets: 3,
          render: function (data, type, full, meta) {
            var $date = new Date(full['quote_date']);
            return $date.toLocaleDateString('es-ES');
          }
        },
        {
          // Valid Until
          targets: 4,
          render: function (data, type, full, meta) {
            var $date = new Date(full['valid_until']);
            var $now = new Date();
            var $isExpired = $date < $now;

            var $dateString = $date.toLocaleDateString('es-ES');

            if ($isExpired) {
              return '<span class="text-danger">' + $dateString + '</span><br><small class="badge bg-label-danger">Expirada</small>';
            } else {
              return '<span class="text-success">' + $dateString + '</span>';
            }
          }
        },
        {
          // Total Amount
          targets: 5,
          render: function (data, type, full, meta) {
            var $amount = parseFloat(full['total_amount']);
            var $currency = full['currency'] || 'USD';

            return (
              '<strong>$' + $amount.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + '</strong>' +
              '<br><small class="text-muted">' + $currency + '</small>'
            );
          }
        },
        {
          // Status
          targets: 6,
          render: function (data, type, full, meta) {
            var $status = full['status'];
            var $status_badges = {
              'pending': { title: 'Pendiente', class: 'bg-label-warning' },
              'approved': { title: 'Aprobada', class: 'bg-label-success' },
              'rejected': { title: 'Rechazada', class: 'bg-label-danger' },
              'converted': { title: 'Convertida', class: 'bg-label-info' },
              'expired': { title: 'Expirada', class: 'bg-label-secondary' }
            };

            var $badge = $status_badges[$status] || { title: $status, class: 'bg-label-secondary' };

            return '<span class="badge ' + $badge.class + '">' + $badge.title + '</span>';
          }
        },
        {
          // Actions
          targets: -1,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var $quotation_id = full['id'];
            var $status = full['status'];

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="' + baseUrl + 'cotizaciones/show/' + $quotation_id + '" class="text-body me-2" title="Ver cotización">' +
              '<i class="ti ti-eye ti-sm"></i>' +
              '</a>' +
              '<div class="dropdown">' +
              '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
              '<i class="ti ti-dots-vertical ti-sm"></i>' +
              '</button>' +
              '<div class="dropdown-menu">' +
                            ($status === 'pending' ?
                '<a class="dropdown-item" href="' + baseUrl + 'cotizaciones/edit/' + $quotation_id + '">' +
                '<i class="ti ti-edit ti-sm me-2"></i>Editar</a>' : '') +
              '<a class="dropdown-item" href="' + baseUrl + 'cotizaciones/download/' + $quotation_id + '" target="_blank">' +
              '<i class="ti ti-download ti-sm me-2"></i>Descargar PDF</a>' +
              '<a class="dropdown-item" href="' + baseUrl + 'cotizaciones/pdf/' + $quotation_id + '" target="_blank">' +
              '<i class="ti ti-file-text ti-sm me-2"></i>Ver PDF</a>' +
              '<a class="dropdown-item" href="javascript:;" onclick="sendEmailQuotation(' + $quotation_id + ')">' +
              '<i class="ti ti-mail ti-sm me-2"></i>Enviar por Correo</a>' +
              '<div class="dropdown-divider"></div>' +
              ($status === 'pending' ?
                '<a class="dropdown-item text-success" href="javascript:;" onclick="changeStatus(' + $quotation_id + ', \'approved\')">' +
                '<i class="ti ti-check ti-sm me-2"></i>Aprobar</a>' +
                '<a class="dropdown-item text-danger" href="javascript:;" onclick="changeStatus(' + $quotation_id + ', \'rejected\')">' +
                '<i class="ti ti-x ti-sm me-2"></i>Rechazar</a>' : '') +
              ($status === 'approved' ?
                '<a class="dropdown-item text-info" href="javascript:;" onclick="convertToSale(' + $quotation_id + ')">' +
                '<i class="ti ti-shopping-cart ti-sm me-2"></i>Convertir a Venta</a>' : '') +
              (['pending', 'rejected'].includes($status) ?
                '<div class="dropdown-divider"></div>' +
                '<a class="dropdown-item text-danger" href="javascript:;" onclick="deleteQuotation(' + $quotation_id + ')">' +
                '<i class="ti ti-trash ti-sm me-2"></i>Eliminar</a>' : '') +
              '</div>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ],
      order: [[3, 'desc']], // Order by date descending
      dom:
        '<"row me-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar cotización...',
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
      },
      // Buttons with Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle mx-3',
          text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Exportar',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2" ></i>Imprimir',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('quotation-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                //customize print view for dark
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>CSV',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('quotation-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('quotation-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-code-2 me-2"></i>PDF',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('quotation-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2" ></i>Copiar',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('quotation-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        }
      ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles de ' + data['quote_number'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);

  // Marcar que la tabla ya fue inicializada
  if (quotationsDataTable) {
    console.log('DataTable de cotizaciones inicializada correctamente');
  }
});
