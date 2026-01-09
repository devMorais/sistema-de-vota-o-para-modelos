$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    var url = $('table').attr('url');

    $.extend($.fn.dataTable.defaults, {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.3/i18n/pt-BR.json'
        },
        initComplete: function (settings, json) {
            $('[tooltip="tooltip"]').tooltip();
        }
    });

    // ---------------------------------------------------------
    // TABELA CATEGORIAS
    // ---------------------------------------------------------
    if ($.fn.DataTable.isDataTable('#tabelaCategorias')) {
        $('#tabelaCategorias').DataTable().destroy();
    }
    $('#tabelaCategorias').DataTable({
        paging: false,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']]
    });

    // ---------------------------------------------------------
    // TABELA POSTS
    // ---------------------------------------------------------
    if ($.fn.DataTable.isDataTable('#tabelaPosts')) {
        $('#tabelaPosts').DataTable().destroy();
    }
    $('#tabelaPosts').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/admin/posts/datatable',
            type: 'POST',
            cache: false,
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },
        columns: [
            null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[1]) {
                        return '<a data-fancybox data-caption="Capa" class="overflow zoom" href="' + url + '/uploads/imagens/thumbs/' + row[1] + '"><img class="thumb" src=" ' + url + '/uploads/imagens/thumbs/' + row[1] + ' " /></a>';
                    } else {
                        return '<i class="fa-regular fa-images fs-1 text-secondary"></i>';
                    }
                }
            },
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[5] === 1) {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';
                    html += ' <a href=" ' + url + '/admin/posts/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';
                    html += '<a href="javascript:void(0)" class="btn-deletar" data-url="' + url + '/admin/posts/deletar/' + row[0] + '"><i class="fa-solid fa-trash m-1 text-danger" tooltip="tooltip" title="Deletar"></i></a>';
                    return html;
                }
            }
        ],
        columnDefs: [
            { className: 'dt-body-left', targets: [0] },
            { className: 'dt-center', targets: [1, 2, 3, 4, 5, 6] },
            { orderable: false, targets: [1, -1] }
        ]
    });

    // ---------------------------------------------------------
    // TABELA USUÁRIOS
    // ---------------------------------------------------------
    if ($.fn.DataTable.isDataTable('#tabelaUsuarios')) {
        $('#tabelaUsuarios').DataTable().destroy();
    }
    $('#tabelaUsuarios').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/admin/usuarios/datatable',
            type: 'POST',
            cache: false,
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },
        columns: [
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[3] === 3) {
                        return '<span class="text-danger">Administrador</span>';
                    } else {
                        return '<span class="text-secondary">Usuário</span>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row[4] === 1) {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';
                    html += ' <a href=" ' + url + '/admin/usuarios/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';
                    html += '<a href="javascript:void(0)" class="btn-deletar" data-url="' + url + '/admin/usuarios/deletar/' + row[0] + '"><i class="fa-solid fa-trash m-1 text-danger" tooltip="tooltip" title="Deletar"></i></a>';
                    return html;
                }
            }
        ],
        columnDefs: [
            { className: 'dt-body-left', targets: [1, 2] },
            { className: 'dt-center', targets: [3, 4, 5] },
            { orderable: false, targets: [-1] }
        ]
    });

    // ---------------------------------------------------------
    // TABELA INGRESSOS
    // ---------------------------------------------------------
    if ($.fn.DataTable.isDataTable('#tabelaIngressos')) {
        $('#tabelaIngressos').DataTable().destroy();
    }
    $('#tabelaIngressos').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/admin/ingressos/datatable',
            type: 'POST',
            cache: false,
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },
        columns: [
            null,
            null,
            {
                data: null,
                render: function (data, type, row) {
                    return '<span class="fw-bold">' + row[2] + '</span>';
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    return 'R$ ' + row[3];
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    return 'R$ ' + row[4];
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row[5] == 1) {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';
                    html += ' <a href=" ' + url + '/admin/ingressos/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';
                    html += '<a href="javascript:void(0)" class="btn-deletar" data-url="' + url + '/admin/ingressos/deletar/' + row[0] + '"><i class="fa-solid fa-trash m-1 text-danger" tooltip="tooltip" title="Deletar"></i></a>';
                    return html;
                }
            }
        ],
        columnDefs: [
            { className: 'dt-center', targets: [0, 1, 2, 3, 4, 5, 6] },
            { orderable: false, targets: [-1] }
        ]
    });

    // ---------------------------------------------------------
    // TABELA PEDIDOS
    // ---------------------------------------------------------
    if ($.fn.DataTable.isDataTable('#tabelaPedidos')) {
        $('#tabelaPedidos').DataTable().destroy();
    }
    $('#tabelaPedidos').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/admin/pedidos/datatable',
            type: 'POST',
            cache: false,
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            {
                data: null,
                render: function (data, type, row) {
                    return '<strong>R$ ' + row[2] + '</strong>';
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var status = row[3];
                    if (status === 'PAGO') {
                        return '<span class="badge bg-success">PAGO</span>';
                    } else if (status === 'AGUARDANDO') {
                        return '<span class="badge bg-warning text-dark">AGUARDANDO</span>';
                    } else if (status === 'ERRO') {
                        return '<span class="badge bg-danger">ERRO</span>';
                    } else {
                        return '<span class="badge bg-secondary">' + status + '</span>';
                    }
                }
            },
            { data: 4 },
            {
                data: 5,
                orderable: false
            }
        ],
        columnDefs: [
            { className: 'dt-center', targets: [0, 2, 3, 4, 5] },
            { orderable: false, targets: [5] }
        ]
    });

    // ---------------------------------------------------------
    // LÓGICA DE CONFIRMAÇÃO (JBOX)
    // ---------------------------------------------------------
    $('body').on('click', '.btn-deletar', function (e) {
        e.preventDefault();
        var urlParaDeletar = $(this).data('url');

        if (!urlParaDeletar) {
            console.error('URL não encontrada!');
            return;
        }

        new jBox('Confirm', {
            attach: $(this),
            minWidth: 300,
            confirmButton: 'Sim, excluir',
            cancelButton: 'Cancelar',
            content: 'Você tem certeza que deseja excluir este registro?',
            closeOnConfirm: false,
            confirm: function () {
                window.location.href = urlParaDeletar;
            },
            onCloseComplete: function () {
                this.destroy();
            }
        }).open();
    });
});