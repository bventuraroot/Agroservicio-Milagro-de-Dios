'use strict';

$(function() {
    let dt_table = $('.datatables-inventory');
    if (dt_table.length) {
        const dt_inventory = dt_table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/inventory/list'
            },
            columns: [
                { data: '' },
                { data: 'code' },
                { data: 'name' },
                { data: 'description' },
                {
                    data: 'price',
                    render: function(data) {
                        return '$ ' + parseFloat(data).toFixed(2);
                    }
                },
                { data: 'type' },
                { data: 'provider_name' },
                {
                    data: 'inventory.quantity',
                    defaultContent: '0'
                },
                {
                    data: 'inventory.minimum_stock',
                    defaultContent: '0'
                },
                {
                    data: 'inventory.location',
                    defaultContent: '-'
                },
                { data: 'actions' }
            ],
            columnDefs: [
                {
                    className: 'control',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 2,
                    targets: 0,
                    render: function () {
                        return '';
                    }
                },
                {
                    targets: -1,
                    title: 'Acciones',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, full) {
                        return (
                            '<div class="d-flex align-items-center">' +
                            '<a href="javascript:editinventory(' + full.id + ');" class="dropdown-item">' +
                            '<i class="ti ti-edit ti-sm me-2"></i>Editar Inventario' +
                            '</a>' +
                            '</div>'
                        );
                    }
                }
            ],
            order: [[1, 'asc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                sLengthMenu: '_MENU_',
                search: 'Buscar',
                searchPlaceholder: 'Buscar..'
            },
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Detalles de ' + data.name;
                        }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        const data = $.map(columns, function (col, i) {
                            return col.title !== '' ? '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                '<td>' + col.title + ':' + '</td> ' +
                                '<td>' + col.data + '</td>' +
                                '</tr>' : '';
                        }).join('');

                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                    }
                }
            }
        });
    }
});
