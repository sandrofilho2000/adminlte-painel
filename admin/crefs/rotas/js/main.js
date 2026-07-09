var tabelaRotas = null;

function escaparHtmlRota(valor) {
    return $("<div>").text(valor ?? "").html()
}

function definirModoFormularioRota(idRota = null) {
    const cartao = $("#cartaoFormularioRota")
    const titulo = $("#tituloFormularioRota")
    const indicador = $("#indicadorModoFormularioRota")
    const botaoSalvar = $("#botaoSalvarRota")
    const botaoLimpar = $("#botaoLimparRota")
    const idAtual = Number(idRota)
    const editando = Number.isInteger(idAtual) && idAtual > 0

    cartao.toggleClass("card-primary", !editando)
    cartao.toggleClass("card-warning", editando)

    if (editando) {
        titulo.text("Editar rota")
        indicador.html(`<i class="fas fa-pen mr-1"></i> Editando ID #${idAtual}`)
        botaoSalvar
            .removeClass("btn-primary")
            .addClass("btn-warning")
            .html('<i class="fas fa-save mr-1"></i> Salvar alteracoes')
        botaoLimpar.text("Cancelar edicao")
        return
    }

    titulo.text("Nova rota")
    indicador.html('<i class="fas fa-plus-circle mr-1"></i> Modo de criacao')
    botaoSalvar
        .removeClass("btn-warning")
        .addClass("btn-primary")
        .html('<i class="fas fa-plus mr-1"></i> Criar rota')
    botaoLimpar.text("Limpar")
}

function renderizarStatusRota(valor) {
    return Number(valor) === 1
        ? '<span class="badge badge-success">Ativa</span>'
        : '<span class="badge badge-danger">Inativa</span>'
}

function renderizarUrlRota(valor) {
    const caminho = String(valor || "").trim()

    if (!caminho) {
        return '<span class="text-muted">-</span>'
    }

    return `<code>${escaparHtmlRota(caminho)}</code>`
}

function obterDadosLinhaRota(botao) {
    const linha = $(botao).closest("tr")
    const linhaPrincipal = linha.hasClass("child") ? linha.prev() : linha
    return tabelaRotas ? tabelaRotas.row(linhaPrincipal).data() : null
}

function carregarRotaNoFormulario(rota) {
    if (!rota) {
        return
    }

    $("#formRotas input[name='id']").val(rota.id || "")
    $("#rotaNome").val(rota.nome || "")
    $("#rotaUrl").val(rota.url || "")
    $("#rotaAtivo").prop("checked", Number(rota.ativo) === 1)
    definirModoFormularioRota(rota.id)
    document.getElementById("cartaoFormularioRota")?.scrollIntoView({ behavior: "smooth", block: "start" })
}

function renderizarAcoesRota(row) {
    const id = escaparHtmlRota(row && row.id ? row.id : "")

    return `
        <div class="d-flex">
            <button type="button" class="btn btn-primary mr-1 btn-editar-rota" data-id="${id}" title="Editar rota">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-danger btn-remover-rota" data-id="${id}" title="Remover rota">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `
}

function renderizarTabelaRotas(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaRotas")) {
        tabelaRotas = $("#tabelaRotas").DataTable({
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 20,
            lengthChange: false,
            order: [[0, "asc"]],
            displayStart: (pagina - 1) * 20,
            ajax: {
                url: "/adminlte-painel/controle/controle_default.php",
                type: "POST",
                data: function (dados) {
                    dados.objeto = "Rotas"
                    dados.metodo = "getRotas"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "r.nome", data: "nome" },
                {
                    name: "r.url",
                    data: "url",
                    render: function (data) {
                        return renderizarUrlRota(data)
                    }
                },
                {
                    name: "r.ativo",
                    data: "ativo",
                    render: function (data) {
                        return renderizarStatusRota(data)
                    }
                },
                {
                    data: null,
                    className: "dt-center no-search all always-visible td-edicoes",
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return renderizarAcoesRota(row)
                    }
                },
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 3 },
            ],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaRotas.ajax.reload(null, false)
}

$(function () {
    renderizarTabelaRotas()

    $("#formRotas").on("submit", function (e) {
        e.preventDefault()

        const form = this
        const formData = new FormData(form)

        requestAjax(formData, function (resultado) {
            if (resultado === true || resultado?.tipo === "success" || resultado?.success) {
                form.reset()
                definirModoFormularioRota()
                tabelaRotas.ajax.reload(null, false)
            }
        })
    })

    $("#formRotas").on("reset", function () {
        window.setTimeout(definirModoFormularioRota, 0)
    })

    $("#formAtribuirRotas").on("submit", function (e) {
        e.preventDefault()

        const formData = {
            objeto: 'PortaisRotas',
            metodo: 'upsertPortalRotaEmMassa',
            id_portal: $("#id_portal").val() || "",
            rotas: $(".checkbox-rota-atribuicao").map(function () {
                return {
                    id: $(this).data("id"),
                    selecionada: $(this).prop("checked")
                }
            }).get()
        }

        requestAjax(formData, function (result) {
            console.log("🚀 ~ result:", result)
        })
    })

    $(document).on("click", ".btn-editar-rota", function () {
        carregarRotaNoFormulario(obterDadosLinhaRota(this))
    })

    $(document).on("click", ".btn-remover-rota", function () {
        exibirMensagemBootstrap("Remocao ainda nao implementada no backend.", "warning")
    })

    $("#rota_atribuicao_todos").on("change", function () {
        $(".checkbox-rota-atribuicao").prop("checked", this.checked)
    })

    $(document).on("change", ".checkbox-rota-atribuicao", function () {
        const totalRotas = $(".checkbox-rota-atribuicao").length
        const totalMarcadas = $(".checkbox-rota-atribuicao:checked").length

        $("#rota_atribuicao_todos").prop("checked", totalRotas > 0 && totalRotas === totalMarcadas)
    })

    $(document).on("change", "#id_portal", function () {
        requestAjax(
            {
                objeto: 'PortaisRotas',
                metodo: 'getPortalRotaPorIdPortal',
                id_portal: $(this).val()
            },
            function (result) {

                const rotasPorId = new Map(result.map(item => [Number(item.id_rota), item]));

                $("[id*=rota_atribuicao]").each(function () {
                    const id = Number($(this).data("id"));
                    const rota = rotasPorId.get(id);
                    $(this).prop("checked", rota?.ativo == 1);
                });
            }
        );
    })
})
