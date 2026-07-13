var tabelaRotas = null;
var caminhoRotaPaiSelecionada = "";

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
            .html('<i class="fas fa-save mr-1"></i> Salvar alterações')
        botaoLimpar.text("Cancelar edição")
        return
    }

    titulo.text("Nova rota")
    indicador.html('<i class="fas fa-plus-circle mr-1"></i> Modo de criação')
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

function renderizarUrlRota(valor, rota = {}) {
    const caminho = String(valor || "").trim()
    const caminhoAscendente = String(rota.rota_ascendentes || "").trim()

    if (!caminho && !caminhoAscendente) {
        return '<span class="text-muted">-</span>'
    }

    return `
        <code>
            ${caminhoAscendente ? `<span class="text-muted font-italic">${escaparHtmlRota(caminhoAscendente)}</span>` : ""}
            ${caminho ? `<span class="text-primary font-weight-bold">${escaparHtmlRota(caminho)}</span>` : ""}
        </code>
    `
}

function normalizarCaminhoPreviewRota(valor) {
    const caminho = String(valor || "").trim().replace(/^\/+|\/+$/g, "")

    return caminho ? `${caminho}/` : ""
}

function limparCaminhoRotaDigitado(valor) {
    const caminho = String(valor || "")
        .trim()
        .split(/[?#]/, 1)[0]
        .trim()
        .replace(/^\/+|\/+$/g, "")

    return caminho ? `${caminho}/` : ""
}

function aplicarLimpezaCampoRota() {
    const campo = $("#rotaUrl")
    const caminhoLimpo = limparCaminhoRotaDigitado(campo.val())

    campo.val(caminhoLimpo)
    atualizarPreviewRotaFinal()

    return caminhoLimpo
}

function obterErroCaminhoRota(valor) {
    const caminho = String(valor || "").trim()

    if (!caminho) {
        return null
    }

    if (/\s/.test(caminho)) {
        return "A URL da rota não pode conter espaços."
    }

    if (/^[a-z][a-z0-9+\-.]*:\/\//i.test(caminho) || caminho.startsWith("//") || /^www\./i.test(caminho) || /^https?(\/|$)/i.test(caminho)) {
        return "Informe apenas o caminho da rota, sem https, domínio ou link completo."
    }

    if (/^[a-z0-9-]+(\.[a-z0-9-]+)*\.(com|org|net|gov|edu|br|info)(\.[a-z]{2})?(\/|$)/i.test(caminho)) {
        return "Informe apenas o caminho interno da rota, sem domínio."
    }

    if (caminho.includes("\\") || caminho.includes("?") || caminho.includes("#")) {
        return "A URL da rota deve ser um caminho limpo, sem parâmetros, âncora ou barras invertidas."
    }

    if (!/^[A-Za-z0-9._~/-]+$/.test(caminho)) {
        return "A URL da rota possui caracteres inválidos. Use apenas letras, números, hífen, underline, ponto e barra."
    }

    if (caminho.includes("//")) {
        return "A URL da rota não pode conter barras duplicadas."
    }

    const partes = caminho.replace(/^\/+|\/+$/g, "").split("/").filter(Boolean)
    const possuiSegmentoInvalido = partes.some(function (parte) {
        return parte === "." || parte === ".."
    })

    if (possuiSegmentoInvalido) {
        return 'A URL da rota não pode conter segmentos "." ou "..".'
    }

    return null
}

function validarFormularioRota() {
    const erroCaminho = obterErroCaminhoRota($("#rotaUrl").val())

    if (erroCaminho) {
        exibirMensagemBootstrap(erroCaminho, "warning")
        $("#rotaUrl").focus()
        return false
    }

    return true
}

function atualizarPreviewRotaFinal() {
    const caminhoDigitado = normalizarCaminhoPreviewRota($("#rotaUrl").val())

    $("#previewRotaPai").text(caminhoRotaPaiSelecionada)
    $("#previewRotaDigitada").text(caminhoDigitado)
}

function obterCaminhoRotaPai(rotaPai) {
    if (!rotaPai) {
        return ""
    }

    const caminhosAscendentes = Array.isArray(rotaPai.rotas_ascendentes)
        ? rotaPai.rotas_ascendentes.map(function (rota) {
            return normalizarCaminhoPreviewRota(rota.url)
        }).join("")
        : ""
    const caminhoPai = normalizarCaminhoPreviewRota(rotaPai.url)

    return `${caminhosAscendentes}${caminhoPai}`
}

function carregarPreviewRotaPai(idRotaPai) {
    const id = Number(idRotaPai || 0)

    caminhoRotaPaiSelecionada = ""
    atualizarPreviewRotaFinal()

    if (!id) {
        return
    }

    requestAjax({
        objeto: "Rotas",
        metodo: "getRota",
        id_rota: id
    }, function (rotaPai) {
        caminhoRotaPaiSelecionada = obterCaminhoRotaPai(rotaPai)
        atualizarPreviewRotaFinal()
    }, false)
}

function montarOpcaoRotaPai(opcao) {
    if (!opcao.id || !opcao.element) {
        return opcao.text
    }

    const nivel = Math.max(0, Number($(opcao.element).data("nivel") || 0))
    const rotaFinal = String($(opcao.element).data("rota-final") || "")
    const conteudo = $("<span>")
        .addClass("d-block")
        .css("padding-left", `${nivel * 18}px`)

    $("<span>").text(opcao.text).appendTo(conteudo)

    if (rotaFinal) {
        $("<small>")
            .addClass("d-block text-muted")
            .text(rotaFinal)
            .appendTo(conteudo)
    }

    return conteudo
}

function montarSelecaoRotaPai(opcao) {
    if (!opcao.id || !opcao.element) {
        return opcao.text
    }

    const rotaFinal = String($(opcao.element).data("rota-final") || "")

    return rotaFinal ? `${opcao.text} - ${rotaFinal}` : opcao.text
}

function criarOpcaoRotaPai(rota) {
    const id = String(rota?.id || "")
    const nome = String(rota?.nome || "")
    const url = String(rota?.url || "")
    const rotulo = nome || url
    const nivel = Math.max(0, Number(rota?.nivel || 0))
    const rotaFinal = `${String(rota?.rota_ascendentes || "")}${url}`

    if (!id || !rotulo) {
        return null
    }

    return $("<option>", {
        value: id,
        text: rotulo
    })
        .attr("data-nivel", nivel)
        .attr("data-rota-final", rotaFinal)
}

function atualizarSelectRotaPai(valorSelecionado = null) {
    const campoRotaPai = $("#rotaPai")

    if (!campoRotaPai.length) {
        return
    }

    const valorAtual = valorSelecionado !== null ? String(valorSelecionado || "") : String(campoRotaPai.val() || "")

    requestAjax({
        objeto: "Rotas",
        metodo: "getRotas",
        hierarquico: 1
    }, function (rotas) {
        campoRotaPai.empty()
        campoRotaPai.append($("<option>", {
            value: "",
            text: "Nenhuma"
        }))

        ;(Array.isArray(rotas) ? rotas : []).forEach(function (rota) {
            const opcao = criarOpcaoRotaPai(rota)

            if (opcao) {
                campoRotaPai.append(opcao)
            }
        })

        const valorExiste = valorAtual && campoRotaPai.find(`option[value="${valorAtual}"]`).length > 0
        campoRotaPai.val(valorExiste ? valorAtual : "").trigger("change")
    }, false)
}

function iniciarSelectRotaPai() {
    const campoRotaPai = $("#rotaPai")

    if (!campoRotaPai.length || typeof campoRotaPai.select2 !== "function") {
        return
    }

    campoRotaPai.select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Selecione uma rota pai",
        allowClear: true,
        templateResult: montarOpcaoRotaPai,
        templateSelection: montarSelecaoRotaPai
    })
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
    $("#rotaPai").val(rota.id_pai || "").trigger("change")
    atualizarPreviewRotaFinal()
    $("#rotaAtivo").prop("checked", Number(rota.ativo) === 1)
    $("#rotinaCodigo").attr("placeholder", rota.rotina || "")
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

function obterEstadoAtualAtribuicaoRotas() {
    const estado = new Map()

    $(".checkbox-rota-atribuicao").each(function () {
        estado.set(String($(this).data("id")), $(this).prop("checked"))
    })

    return estado
}

function criarItemAtribuicaoRota(rota, estadoAtual) {
    const id = String(rota?.id || "")
    const nome = String(rota?.nome || "")
    const url = String(rota?.url || "")
    const rotulo = nome || url
    const nivel = Math.max(0, Number(rota?.nivel || 0))
    const rotaFinal = `${String(rota?.rota_ascendentes || "")}${url}`

    if (!id || !rotulo) {
        return null
    }

    const idCheckbox = `rota_atribuicao_${id.replace(/[^A-Za-z0-9_-]/g, "_")}`
    const item = $("<div>").addClass("item-atribuicao-rota")
    const controle = $("<div>")
        .addClass("custom-control custom-checkbox mb-2")
        .css("padding-left", `${1.5 + (nivel * 1.25)}rem`)
        .appendTo(item)

    $("<input>", {
        type: "checkbox",
        class: "custom-control-input checkbox-rota-atribuicao",
        id: idCheckbox,
        name: "rotas[]",
        value: id
    })
        .attr("data-id", id)
        .prop("checked", estadoAtual.get(id) === true)
        .appendTo(controle)

    const label = $("<label>", {
        class: "custom-control-label",
        for: idCheckbox
    }).appendTo(controle)

    $("<span>").addClass("d-block").text(rotulo).appendTo(label)

    if (rotaFinal.trim()) {
        $("<small>").addClass("d-block text-muted").text(rotaFinal).appendTo(label)
    }

    return item
}

function atualizarListaAtribuicaoRotas() {
    const lista = $(".lista-atribuicao-rotas")

    if (!lista.length) {
        return
    }

    const estadoAtual = obterEstadoAtualAtribuicaoRotas()
    const idPortal = $("#id_portal").val() || ""

    requestAjax({
        objeto: "Rotas",
        metodo: "getRotas",
        hierarquico: 1
    }, function (rotas) {
        lista.empty()

        ;(Array.isArray(rotas) ? rotas : []).forEach(function (rota) {
            const item = criarItemAtribuicaoRota(rota, estadoAtual)

            if (item) {
                lista.append(item)
            }
        })

        if (idPortal) {
            $("#id_portal").trigger("change")
            return
        }

        $("#rota_atribuicao_todos").prop("checked", false)
    }, false)
}

function renderizarTabelaRotas(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaRotas")) {
        tabelaRotas = $("#tabelaRotas").DataTable({
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 50,
            lengthChange: false,
            order: [],
            displayStart: (pagina - 1) * 50,
            ajax: {
                url: "/adminlte-painel/controle/controle_default.php",
                type: "POST",
                data: function (dados) {
                    dados.objeto = "Rotas"
                    dados.metodo = "getRotas"
                    dados.hierarquico = 1
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                {
                    name: "r.id",
                    data: "id",
                    visible: false,
                },
                {
                    name: "r.nome",
                    data: "nome",
                    orderable: false,
                    render: function (data, type, row) {
                        console.log("🚀 ~ renderizarTabelaRotas ~ row:", row)
                        const nivel = Math.max(0, Number(row?.nivel || 0))
                        const nome = escaparHtmlRota(data)
                        const icone = nivel > 0 ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>' : ''

                        return `<span class="d-inline-block" style="padding-left: ${nivel * 20}px;">${icone}${nome}</span>`
                    }
                },
                {
                    name: "r.url",
                    data: "url",
                    render: function (data, type, row) {
                        return renderizarUrlRota(data, row)
                    }
                },
                {
                    name: "r.rotina",
                    data: "rotina",
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
    iniciarSelectRotaPai()
    renderizarTabelaRotas()
    atualizarPreviewRotaFinal()

    $("#rotaUrl").on("input", atualizarPreviewRotaFinal)
    $("#rotaUrl").on("blur", aplicarLimpezaCampoRota)

    $("#rotaPai").on("change", function () {
        carregarPreviewRotaPai($(this).val())
    })

    $("#formRotas").on("submit", function (e) {
        e.preventDefault()

        aplicarLimpezaCampoRota()

        if (!validarFormularioRota()) {
            return
        }

        const form = this
        const formData = new FormData(form)

        requestAjax(formData, function (resultado) {
            if (resultado === true || resultado?.tipo === "success" || resultado?.success) {
                form.reset()
                $("#rotaPai").val("").trigger("change")
                definirModoFormularioRota()
                tabelaRotas.ajax.reload(null, false)
                atualizarSelectRotaPai("")
                atualizarListaAtribuicaoRotas()
            }
        })
    })

    $("#formRotas").on("reset", function () {
        const placeholderOriginal = $("#rotinaCodigo").attr("placeholder_original")

        $("#rotinaCodigo").attr("placeholder", placeholderOriginal)
        window.setTimeout(() => $("#rotaPai").val("").trigger("change"), 0)
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
        exibirMensagemBootstrap("Remoção ainda não implementada no backend.", "warning")
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
