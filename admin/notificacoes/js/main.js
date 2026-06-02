const language_url = "/webconfef/templates/AdminLTE-3.2.0/dist/js/pt-BR.json"
var tblNotificacoes = null
var verNotificacoesNaoLidas = false
var totalNotificacoesNaoLidas = 0

function obterCorNotificacao(cor) {
    const valor = String(cor || "").trim().toLowerCase()
    const mapa = {
        warning: "#ffc107",
        info: "#17a2b8",
        danger: "#dc3545",
        success: "#28a745",
        primary: "#007bff",
        secondary: "#6c757d",
        maroon: "#d81b60",
        orange: "#ff851b",
        "gray-dark": "#343a40",
        gray_dark: "#343a40"
    }

    if (!valor) {
        return ""
    }

    if (valor.startsWith("#") || valor.startsWith("rgb")) {
        return valor
    }

    return mapa[valor] || ""
}

function extrairTotalNaoLidas(result) {
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

function atualizarHtmlBotaoVerNotificacoesNaoLidas(total) {
    if (typeof total !== "undefined") {
        totalNotificacoesNaoLidas = Number.isFinite(Number(total)) ? Number(total) : 0
    }

    const totalNaoLidas = Number.isFinite(Number(totalNotificacoesNaoLidas)) ? Number(totalNotificacoesNaoLidas) : 0
    const titulo = verNotificacoesNaoLidas ? "Ocultar notificações não lidas" : "Ver notificações não lidas"
    const classeBotao = verNotificacoesNaoLidas ? "btn-info" : "btn-outline-info"

    $("#btnVerNotificacoesNaoLidas")
        .removeClass("btn-outline-info btn-info")
        .addClass(classeBotao)
        .html(`${titulo} <span class="badge badge-light ml-1">${totalNaoLidas}</span>`)
}

function contarNaoLidas() {
    requestAjax(
        {
            objeto: "Notificacoes",
            metodo: "contarNaoLidas"
        },
        function (result) {
            atualizarHtmlBotaoVerNotificacoesNaoLidas(extrairTotalNaoLidas(result))
        }
    )
}

function posicionarBotoesNotificacoes() {
    const $acoesWrapper = $("#notificacoesAcoesWrapper")
    const $btnVerNaoLidas = $("#btnVerNotificacoesNaoLidas")
    const $btnAtualizar = $("#btnAtualizarNotificacoes")
    const $btnLerTodas = $("#btnLerTodasNotificacoes")
    const $wrapper = $("#tblNotificacoes_wrapper")

    if (!$acoesWrapper.length) {
        return
    }

    $acoesWrapper.removeClass("d-none")
    $btnVerNaoLidas.add($btnAtualizar).add($btnLerTodas).removeClass("d-none")

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

function marcarNotificacaoComoLida(idNotificacaoUsuario, callback) {
    const id = String(idNotificacaoUsuario || "").trim()
    if (!id) {
        return
    }

    requestAjax(
        {
            objeto: "Notificacoes",
            metodo: "marcarNotificacaoComoLida",
            id_notificacao_usuario: id
        },
        function () {
            if (typeof callback === "function") {
                callback()
            }
            contarNaoLidas()
        }, false
    )
}

function removerDestaqueNotificacaoLida($botao) {
    const $linha = $botao.closest("tr")
    if (!$linha.length) {
        return
    }

    const $texto = $linha.find("td").eq(0).find("a").first()
    $texto.removeClass("font-weight-bold")
}

function marcarTodasNotificacoesComoLidasNaTela() {
    $("#tblNotificacoes a.font-weight-bold").removeClass("font-weight-bold")
    $("#tblNotificacoes .btn-marcar-notificacao-lida").prop("disabled", true)
    $("#bell-badge").text("0")
    atualizarHtmlBotaoVerNotificacoesNaoLidas(0)
}

function lerTodasNotificacoes() {
    requestAjax(
        {
            'objeto': "Notificacoes",
            'metodo': "lerNotificacoes"
        }, function(result){
            console.log("🚀 ~ result:", result)
            marcarTodasNotificacoesComoLidasNaTela()
            renderTblNotificacoes()
        }, false
    )
}

function renderTblNotificacoes(page = 1) {
    if (!$.fn.DataTable.isDataTable('#tblNotificacoes')) {
        tblNotificacoes = $('#tblNotificacoes').DataTable({
            serverSide: true,
            autoWidth: false,
            pageLength: 20,
            lengthChange: false,
            order: [[1, "desc"]],
            displayStart: (page - 1) * 20,
            responsive: true,
            ajax: {
                url: "/webconfef/controle/controle_default.php",
                type: "POST",
                data: function (d) {
                    d.objeto = 'Notificacoes'
                    d.metodo = 'getNotificacoesTable'
                    d.ver_notificacoes_nao_lidas = verNotificacoesNaoLidas ? 1 : 0
                    d.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                {
                    name: "n.texto",
                    data: "texto",
                    render: (data, type, row) => {
                        const texto = escapeHtml(data || "")
                        const url = escapeHtml(String(row?.botao_url || "#"))
                        const idNotificacao = escapeHtml(String(row?.id_notificacao_usuario || ""))
                        const classeLida = Number(row?.lida) === 1 ? "" : "font-weight-bold"

                        return `
                            <a href="${url}" data-url="${url}" data-id="${idNotificacao}" onclick="return lerNotificacao(this.dataset.url, this.dataset.id)" class="d-block text-body text-decoration-none ${classeLida}">
                                ${texto}
                            </a>
                        `
                    }
                },
                {
                    name: "n.criado_em",
                    data: "criado_em",
                    render: (data) => formatarDataHora(data)
                },
                {
                    name: "nu.lida",
                    data: "lida",
                    className: "text-center align-middle",
                    width: "50px",
                    createdCell: function (td) {
                        $(td).css({
                            "max-width": "50px",
                            "width": "50px",
                            "min-width": "50px",
                            "padding": "0.25rem",
                            "text-align": "center"
                        })
                    },
                    render: (data, type, row) => {
                        const idNotificacao = escapeHtml(String(row?.id_notificacao_usuario || ""))
                        return `
                            <button
                                type="button"
                                class="btn btn-success btn-marcar-notificacao-lida mx-auto d-flex justify-content-center align-items-center"
                                data-id-notificacao-usuario="${idNotificacao}"
                                ${ row.lida ? " disabled " : "" }
                                style="width: 36px !important; min-width: 36px !important; max-width: 36px !important; height: 36px; padding: 0;"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        `
                    }
                },
            ],
            language: { url: language_url },
            rowCallback: function (row, data) {
                const cor = obterCorNotificacao(data?.cor ?? data?.color)
                const $celulaTexto = $("td", row).eq(0)

                $celulaTexto.css({
                    "border-left": cor ? `4px solid ${cor}` : "",
                    "padding-left": cor ? "12px" : ""
                })
            },
            initComplete: function () {
                posicionarBotoesNotificacoes()
                contarNaoLidas()
            },
            drawCallback: function () {
                posicionarBotoesNotificacoes()
            }
        })

        posicionarBotoesNotificacoes()
    } else {
        tblNotificacoes
            .ajax.reload()
            .columns.adjust()
            .responsive.recalc()

        posicionarBotoesNotificacoes()
        contarNaoLidas()
    }
}

$(document).ready(function () {
    renderTblNotificacoes()

    $(document).on("click", "#btnVerNotificacoesNaoLidas", function () {
        verNotificacoesNaoLidas = !verNotificacoesNaoLidas
        atualizarHtmlBotaoVerNotificacoesNaoLidas()
        renderTblNotificacoes()
    })

    $(document).on("click", "#btnAtualizarNotificacoes", function () {
        verNotificacoesNaoLidas = false
        atualizarHtmlBotaoVerNotificacoesNaoLidas()
        renderTblNotificacoes()
    })

    $(document).on("click", "#btnLerTodasNotificacoes", function () {
        lerTodasNotificacoes()
    })

    $(document).on("click", ".btn-marcar-notificacao-lida", function () {
        const $botao = $(this)
        marcarNotificacaoComoLida($botao.data("idNotificacaoUsuario"), function () {
            removerDestaqueNotificacaoLida($botao)
        })
    })
})
