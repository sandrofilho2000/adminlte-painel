const formularioRotinas = $("#formRotinas")
let tabelaRotinas = null

function escaparHtml(valor) {
    return $("<div>").text(valor ?? "").html()
}

function montarOpcaoIcone(opcao) {
    if (!opcao.id || !opcao.element) {
        return opcao.text
    }

    const classes = String($(opcao.element).data("classes") || "")
        .split(/\s+/)
        .filter((classe) => /^[a-zA-Z0-9_-]+$/.test(classe))
        .join(" ")
    const nome = String($(opcao.element).data("nome") || opcao.text)
    const conteudo = $("<span>")
    const icone = $("<i>", {
        class: `${classes} icone-opcao-select2`,
        "aria-hidden": "true"
    })

    icone.appendTo(conteudo)
    $("<span>").text(nome).appendTo(conteudo)
    $("<small>").addClass("text-muted ml-2").text(classes).appendTo(conteudo)

    return conteudo
}

function iniciarSeletorIcones() {
    const campoIcone = $("#rotinaIcone")

    if (!campoIcone.length || typeof campoIcone.select2 !== "function") {
        return
    }

    campoIcone.select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Pesquise pelo nome do ícone",
        allowClear: true,
        templateResult: montarOpcaoIcone,
        templateSelection: montarOpcaoIcone
    })
}

function selecionarIconeRotina(valorIcone) {
    const campoIcone = $("#rotinaIcone")
    const classesIcone = String(valorIcone || "").trim()

    if (!campoIcone.length) {
        return
    }

    if (classesIcone === "") {
        campoIcone.val(null).trigger("change")
        return
    }

    const opcaoExistente = campoIcone.find("option").filter(function () {
        return String(this.value) === classesIcone
    })

    if (!opcaoExistente.length) {
        const novaOpcao = $("<option>", {
            value: classesIcone,
            text: classesIcone
        })
            .attr("data-classes", classesIcone)
            .attr("data-nome", classesIcone)

        campoIcone.append(novaOpcao)
    }

    campoIcone.val(classesIcone).trigger("change")
}

function restaurarOpcoesRotinaPai() {
    $("#rotinaPai option[data-ocultada-edicao='1']")
        .prop("disabled", false)
        .removeAttr("hidden")
        .removeAttr("data-ocultada-edicao")
}

function ocultarRotinaAtualDoCampoPai(idRotina) {
    const campoRotinaPai = $("#rotinaPai")
    const idAtual = String(idRotina ?? "")

    restaurarOpcoesRotinaPai()

    if (!campoRotinaPai.length || idAtual === "") {
        return
    }

    campoRotinaPai.find("option").filter(function () {
        return String(this.value) === idAtual
    })
        .prop("disabled", true)
        .attr("hidden", "hidden")
        .attr("data-ocultada-edicao", "1")

    campoRotinaPai.trigger("change")
}

function definirModoFormulario(idRotina = null) {
    const cartao = $("#cartaoFormularioRotina")
    const titulo = $("#tituloFormularioRotina")
    const indicador = $("#indicadorModoFormulario")
    const botaoSalvar = $("#botaoSalvarRotina")
    const botaoLimpar = $("#botaoLimparRotina")
    const idAtual = Number(idRotina)
    const editando = Number.isInteger(idAtual) && idAtual > 0

    cartao.toggleClass("card-primary", !editando)
    cartao.toggleClass("card-warning", editando)

    if (editando) {
        titulo.text("Editar rotina")
        indicador.html(`<i class="fas fa-pen mr-1"></i> Editando ID #${idAtual}`)
        botaoSalvar
            .removeClass("btn-primary")
            .addClass("btn-warning")
            .html('<i class="fas fa-save mr-1"></i> Salvar alterações')
        botaoLimpar.text("Cancelar edição")
        return
    }

    titulo.text("Nova rotina")
    indicador.html('<i class="fas fa-plus-circle mr-1"></i> Modo de criação')
    botaoSalvar
        .removeClass("btn-warning")
        .addClass("btn-primary")
        .html('<i class="fas fa-plus mr-1"></i> Criar rotina')
    botaoLimpar.text("Limpar")
}

function renderizarSituacao(valor, textoAtivo, textoInativo) {
    return Number(valor) === 1
        ? `<span class="badge badge-success">${textoAtivo}</span>`
        : `<span class="badge badge-secondary">${textoInativo}</span>`
}

function renderizarTabelaRotinas(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaRotinas")) {
        tabelaRotinas = $("#tabelaRotinas").DataTable({
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 20,
            lengthChange: false,
            order: [[0, "desc"]],
            displayStart: (pagina - 1) * 20,
            ajax: {
                url: "/adminlte-painel/controle/controle_default.php",
                type: "POST",
                data: function (dados) {
                    dados.objeto = "Rotinas"
                    dados.metodo = "getRotinas"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "r.id", data: "id" },
                { name: "r.Rotina", data: "Rotina" },
                { name: "r.Descricao", data: "Descricao" },
                { name: "r.tipo_sistema", data: "tipo_sistema" },
                { name: "r.rota", data: "rota" },
                {
                    name: "r.icon",
                    data: "icon",
                    render: function (valor) {
                        const icone = escaparHtml(valor)
                        return icone ? `<i class="${icone} mr-1"></i> ${icone}` : ""
                    }
                },
                { name: "r.id_pai", data: "rotina_pai", defaultContent: "" },
                {
                    name: "r.status",
                    data: "status",
                    render: (valor) => renderizarSituacao(valor, "Ativa", "Inativa")
                },
                {
                    name: "r.em_manutencao",
                    data: "em_manutencao",
                    render: (valor) => renderizarSituacao(valor, "Sim", "Não")
                },
                {
                    data: null,
                    className: 'dt-center no-search all always-visible td-edicoes',
                    render: (data, type, row) => `
                        <div class="d-flex">
                            <button type="button" class="btn btn-primary mr-1 btn-edit" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `
                }
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 3 },
                { responsivePriority: 2, targets: 1 },
            ],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaRotinas.ajax.reload(null, false)
}

formularioRotinas.on("submit", function (evento) {
    evento.preventDefault()
    gravarFrm("formRotinas", tabelaRotinas)
})

formularioRotinas.on("reset", function () {
    restaurarOpcoesRotinaPai()
    definirModoFormulario()
    const $placeholder_original = $("#rotinaCodigo").attr("placeholder_original")
    $("#rotinaCodigo").attr("placeholder", $placeholder_original)
    window.setTimeout(() => $("#rotinaIcone").val(null).trigger("change"), 0)
})

$(function () {
    iniciarSeletorIcones()
    renderizarTabelaRotinas()

    $(document).on("click", ".btn-edit", function(e){
        const id = $(this).data("id")
        requestAjax(
            {
                'objeto': "Rotinas",
                'metodo': "getRotinas",
                'filtros[IGUAL][r.id]': id
            }, function(result){
                const [rotina] = result
                carregarForm("formRotinas", rotina, function(){
                    definirModoFormulario(rotina.id)
                    ocultarRotinaAtualDoCampoPai(rotina.id)
                    selecionarIconeRotina(rotina.icon)
                    $("#rotinaCodigo").attr("placeholder", rotina.Rotina)
                })
            }
        )
    })
})
