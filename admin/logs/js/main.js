const language_url = "/adminlte-painel/templates/AdminLTE-3.2.0/dist/js/pt-BR.json"
var tabelaLogs = $("#tabelaLogs")
var tabelaLogsIgnorar = $("#tabelaLogsIgnorar")
var verLogsIgnorados = false

function extrairTotalLogsIgnorados(result) {
    if (typeof result === "number") {
        return result
    }

    if (typeof result === "string" && $.isNumeric(result)) {
        return Number(result)
    }

    if (result && typeof result === "object") {
        if (typeof result.total !== "undefined" && $.isNumeric(result.total)) {
            return Number(result.total)
        }

        if (typeof result.count !== "undefined" && $.isNumeric(result.count)) {
            return Number(result.count)
        }
    }

    return 0
}

function atualizarHtmlBotaoVerLogsIgnorados(total) {
    const totalIgnorados = Number.isFinite(Number(total)) ? Number(total) : 0
    const titulo = verLogsIgnorados ? "Ocultar logs ignorados" : "Ver logs ignorados"
    const classeBotao = verLogsIgnorados ? "btn-info" : "btn-outline-info"

    $("#btnVerLogsIgnorados")
        .removeClass("btn-outline-info btn-info")
        .addClass(classeBotao)
        .html(`${titulo} <span class="badge badge-light ml-1">${totalIgnorados}</span>`)
}

function atualizarBotaoVerLogsIgnorados() {
    requestAjax(
        {
            objeto: "Logs",
            metodo: "contarLogsIgnorados"
        },
        function (result) {
            atualizarHtmlBotaoVerLogsIgnorados(extrairTotalLogsIgnorados(result))
        }
    )
}

function posicionarBotaoVerLogsIgnorados() {
    const $acoesWrapper = $("#logsAcoesWrapper")
    const $btnVerLogsIgnorados = $("#btnVerLogsIgnorados")
    const $btnAtualizarLogs = $("#btnAtualizarLogs")
    const $wrapper = $("#tabelaLogs_wrapper")

    if (!$acoesWrapper.length) {
        return
    }

    $acoesWrapper.removeClass("d-none")
    $btnVerLogsIgnorados.add($btnAtualizarLogs).removeClass("d-none")

    if (!$wrapper.length) {
        return
    }

    const $search = $wrapper.find("div.dt-search, div.dataTables_filter").first()
    if (!$search.length) {
        $acoesWrapper.detach().prependTo($wrapper)
        return
    }

    const $row = $search.closest(".dt-layout-row, .row").first()
    if (!$row.length) {
        return
    }

    let $target = $row.find(".dt-layout-cell.dt-layout-start").first()

    if (!$target.length) {
        $target = $row.find("> .col-sm-12.col-md-6").filter(function () {
            return $(this).find("div.dt-search, div.dataTables_filter").length === 0
        }).first()
    }

    if (!$target.length) {
        if ($row.hasClass("dt-layout-row")) {
            $target = $('<div class="dt-layout-cell dt-layout-start d-flex align-items-center"></div>')
            $row.prepend($target)
        } else if ($row.hasClass("row")) {
            $target = $('<div class="col-sm-12 col-md-6 d-flex align-items-center"></div>')
            $row.prepend($target)
        } else {
            $target = $('<div class="d-flex align-items-center"></div>')
            $row.prepend($target)
        }
    }

    if ($target.hasClass("col-sm-12") || $target.hasClass("col-md-6")) {
        $target.addClass("d-flex align-items-center")
    }

    $acoesWrapper.detach().prependTo($target)
}

function renderTabelaLogs(page = 1) {
    if (!$.fn.DataTable.isDataTable('#tabelaLogs')) {
        tabelaLogs = $('#tabelaLogs').DataTable({
            columnControl: [['search']],
            serverSide: true,
            pageLength: 20,
            lengthChange: false,
            order: [[1, "desc"]],
            displayStart: (page - 1) * 20,
            ajax: {
                "url": "/adminlte-painel/controle/controle_default.php",
                "type": "POST",
                "data": function (d) {
                    d.objeto = 'Logs';
                    d.metodo = 'getLogs';
                    d.ver_logs_ignorados = verLogsIgnorados ? 1 : 0;
                    d.aplicarPaginacaoNoResultado = 1;
                }
            },
            columns: [
                {
                    "name": "l.id",
                    "data": "id",
                },
                {
                    "name": "l.criado_em",
                    "data": "criado_em",
                    "render": (data) => formatarDataHora(data)

                },
                {
                    "name": "l.mensagem",
                    "data": "mensagem",
                },
                {
                    "name": "u.apresentacao",
                    "data": "nome_usuario",
                },
                {
                    "name": "u.estado_conselho",
                    "data": "estado_conselho",
                },
                {
                    "name": "l.trace",
                    "data": "trace",
                },
                {
                    "data": "linha",
                },
                {
                    "name": "l.payload",
                    "data": "payload",
                },
                {
                    "name": "l.objeto_metodo",
                    "data": "objeto_metodo",
                },
                {
                    "name": "l.mensagem",
                    "data": "erro",
                },
                {
                    data: null,
                    className: 'dt-center no-search all always-visible',
                    render: (data, type, row) => `
                        <button class="btn btn-danger delete-log-btn" data-id="${row.id}" title="Excluir erro"><i class="fas fa-trash"></i></button>
                    `
                }
            ],
            language: { url: language_url },
            initComplete: function () {
                posicionarBotaoVerLogsIgnorados()
            },
            drawCallback: function () {
                posicionarBotaoVerLogsIgnorados()
            }
        });

        posicionarBotaoVerLogsIgnorados()
    } else {
        tabelaLogs
            .ajax.reload()
            .columns.adjust()
            .responsive.recalc();

        posicionarBotaoVerLogsIgnorados()
    }
}

function renderTabelaLogsIgnorar(page = 1) {
    const operadores = {
        'LIKE': 'Contém',
        'IGUAL': 'Igual',
        'LIKE_START': 'Inicia com',
        'LIKE_END': 'Termina com',
    }
    if (!$.fn.DataTable.isDataTable('#tabelaLogsIgnorar')) {
        tabelaLogsIgnorar = $('#tabelaLogsIgnorar').DataTable({
            columnControl: [['search']],
            serverSide: true,
            pageLength: 10,
            searching: false,
            lengthChange: false,
            order: [[4, "desc"]],
            displayStart: (page - 1) * 10,
            ajax: {
                url: "/adminlte-painel/controle/controle_default.php",
                type: "POST",
                data: function (d) {
                    d.objeto = 'LogsIgnorar';
                    d.metodo = 'getRegras';
                    d.aplicarPaginacaoNoResultado = 1;
                }
            },
            columns: [
                {
                    name: "l.campo",
                    data: "campo"
                },
                {
                    name: "l.operador",
                    data: "operador",
                    render: (data) => operadores[data] || ''
                },
                {
                    name: "l.valor",
                    data: "valor"
                },
                {
                    name: "l.ativo",
                    data: "ativo",
                    render: (data) => data == 1
                        ? '<span class="badge badge-success">Ativo</span>'
                        : '<span class="badge badge-secondary">Inativo</span>'
                },
                {
                    name: "l.criado_em",
                    data: "criado_em",
                    render: (data) => formatarDataHora(data)
                },
                {
                    name: "u.apresentacao",
                    data: "nome_usuario"
                },
                {
                    data: null,
                    className: 'dt-center no-search all always-visible',
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="d-flex">
                            <button class="btn btn-primary edit-regra-log-btn mr-1" data-id="${row.id}" title="Editar regra">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger delete-regra-log-btn" data-id="${row.id}" title="Excluir regra">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `
                }
            ],
            language: { url: language_url },
        });
    } else {
        tabelaLogsIgnorar
            .ajax.reload()
            .columns.adjust()
            .responsive.recalc();
    }
}

function exibirFeedbackRegra(texto, tipo = "info") {
    const classesPorTipo = {
        success: "alert alert-success",
        danger: "alert alert-danger",
        warning: "alert alert-warning",
        info: "alert alert-info"
    }

    const $feedback = $("#regrasExclusaoFeedback")
    $feedback
        .removeClass("d-none alert alert-success alert-danger alert-warning alert-info")
        .addClass(classesPorTipo[tipo] || classesPorTipo.info)
        .text(texto)
}

function abrirAbaPeloHash() {
    const hashValido = ["#tab-erros", "#tab-regras"]
    const hashAtual = window.location.hash

    if (!hashValido.includes(hashAtual)) return

    const $aba = $(`#logsTab a[href="${hashAtual}"]`)
    if ($aba.length) {
        $aba.tab("show")
    }
}

function renderTabelaPorAba(hashAba) {
    if (hashAba === "#tab-regras") {
        renderTabelaLogsIgnorar()
        return
    }

    atualizarBotaoVerLogsIgnorados()
    renderTabelaLogs()
}


$(document).ready(function () {
    abrirAbaPeloHash()
    const abaAtiva = $("#logsTab .nav-link.active").attr("href") || "#tab-erros"
    renderTabelaPorAba(abaAtiva)

    $(document).on("click", "#btnVerLogsIgnorados", function () {
        verLogsIgnorados = !verLogsIgnorados
        atualizarBotaoVerLogsIgnorados()
        renderTabelaLogs()
    })

    $(document).on("click", "#btnAtualizarLogs", function () {
        atualizarBotaoVerLogsIgnorados()
        renderTabelaLogs()
    })

    $(document).on("click", ".delete-log-btn", function (e) {
        let $id = $(this).data("id")
        let $currPage = $("#tabelaLogs_wrapper .dt-paging-button.current").text();
        $currPage = parseInt($currPage, 10) || 1;

        requestAjax(
            {
                "objeto": "Logs",
                "metodo": "excluirLog",
                "id": $id
            }, function (result) {
                renderTabelaLogs($currPage)
                atualizarBotaoVerLogsIgnorados()
            }
        )
    })

    $(document).on("click", ".delete-regra-log-btn", function (e) {
        let $id = $(this).data("id")
        let $currPage = $("#tabelaLogs_wrapper .dt-paging-button.current").text();
        $currPage = parseInt($currPage, 10) || 1;

        requestAjax(
            {
                "objeto": "LogsIgnorar",
                "metodo": "excluirRegraLog",
                "id": $id
            }, function (result) {
                if (result?.status == "success") {
                    renderTabelaLogsIgnorar($currPage)
                    atualizarBotaoVerLogsIgnorados()
                }
            }
        )
    })

    $(document).on("shown.bs.tab", "#logsTab a[data-toggle='tab']", function () {
        const hashAba = $(this).attr("href")
        if (hashAba) {
            window.location.hash = hashAba
        }

        renderTabelaPorAba(hashAba)
    })

    $(document).on("click", ".edit-regra-log-btn", function (e) {
        let $id = $(this).data("id")
        let $currPage = $("#tabelaLogs_wrapper .dt-paging-button.current").text();
        $currPage = parseInt($currPage, 10) || 1;

        requestAjax(
            {
                "objeto": "LogsIgnorar",
                "metodo": "getRegra",
                "id": $id
            }, function (result) {
                carregarForm("formRegrasExclusaoLogs", result)
            }
        )

    })

    $(document).on("submit", "#formRegrasExclusaoLogs", function (event) {
        event.preventDefault()
        const form = this

        if (!form.checkValidity()) {
            form.reportValidity()
            return
        }

        const regra = {
            id: $("#regraId").val() || null,
            campo: $("#regraCampo").val() || "",
            operador: $("#regraOperador").val() || "",
            valor: $.trim($("#regraValor").val() || ""),
            ativo: $("#regraAtivo").is(":checked") ? 1 : 0
        }

        requestAjax(
            {
                objeto: "LogsIgnorar",
                metodo: $("#regraId").val() ? "editarRegrar" : "criaRegrar",
                ...regra
            },
            function (result) {
                if ($("#regraId").val()) {
                    if (result.row_count == 1) {
                        exibirFeedbackRegra("Regra salva.", "success")
                        renderTabelaLogsIgnorar()
                        atualizarBotaoVerLogsIgnorados()
                    }
                } else {
                    if (result.id) {
                        exibirFeedbackRegra("Regra salva.", "success")
                        renderTabelaLogsIgnorar()
                        atualizarBotaoVerLogsIgnorados()
                    }
                }
                form.reset()
            }
        )
    })

    $(document).on("click", "#btnLimparRegraExclusao", function () {
        $("#formRegrasExclusaoLogs")[0].reset()
        $("#regraAtivo").prop("checked", true)
        $("#regrasExclusaoFeedback").addClass("d-none").text("")
    })
});
