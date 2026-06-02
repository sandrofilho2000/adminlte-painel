const COLUNA_ARQUIVADOS = "arquivados"
const COLUNA_PADRAO = "backlog"
const USUARIO_ATUAL_ID = String(window.CHAMADOS_USUARIO_ID || "").trim()
const USUARIO_ATUAL = String(window.CHAMADOS_USUARIO_ATUAL || "Usuário").trim() || "Usuário"
const USUARIO_ATUAL_EH_TI = Boolean(window.CHAMADOS_USUARIO_EH_TI)

const moduloLabel = {
    'suporte-para-maquinas': 'Suporte para Máquinas',
    'suporte-para-programas-e-aplicativos': 'Suporte para Programas e Aplicativos',
    'suporte-para-programas-e-aplicativos': 'Suporte para Programas e Aplicativos',
    'suporte-para-perifericos': 'Suporte para Periféricos',
    'suporte-para-duvidas-no-geral': 'Suporte para duvidas no geral',
    'liberacao-de-acesso-para-internos': 'Liberação de acesso para internos',
    'liberacao-de-acesso-para-externos': 'Liberação de acesso para externos',
}

const colunasKanban = ["backlog", "andamento", "pausadas", "em_validacao", "concluidas", "retorno", COLUNA_ARQUIVADOS]

const nomesColuna = {
    backlog: "Em fila",
    andamento: "Em andamento",
    pausadas: "Pausados",
    em_validacao: "Em validação",
    concluidas: "Concluídas",
    retorno: "Retorno",
    arquivados: "Arquivados"
}

const dadosKanban = {}

const classesTituloPorColuna = {
    backlog: "kanban-title-backlog",
    andamento: "kanban-title-andamento",
    pausadas: "kanban-title-pausadas",
    em_validacao: "kanban-title-em-validacao",
    concluidas: "kanban-title-concluidas",
    retorno: "kanban-title-retorno",
    arquivados: "kanban-title-arquivados"
}

const classesCardPorColuna = {
    backlog: "kanban-card-backlog",
    andamento: "kanban-card-andamento",
    pausadas: "kanban-card-pausadas",
    em_validacao: "kanban-card-em-validacao",
    concluidas: "kanban-card-concluidas",
    retorno: "kanban-card-retorno",
    arquivados: "kanban-card-arquivados"
}

const classesBgCabecalhoModal = ["bg-primary", "bg-info", "bg-warning", "bg-gray-dark", "bg-orange", "bg-success", "bg-danger", "bg-secondary"]

const classesBgCabecalhoModalPorColuna = {
    backlog: "bg-info",
    andamento: "bg-warning",
    pausadas: "bg-gray-dark",
    em_validacao: "bg-orange",
    concluidas: "bg-success",
    retorno: "bg-danger",
    arquivados: "bg-secondary"
}

const CHAMADOS_COLUMN_ORDER_STORAGE_KEY = "webconfef:chamados:kanban-column-order"
const CHAMADOS_COLUMN_COLLAPSED_STORAGE_KEY = "webconfef:chamados:kanban-column-collapsed"

const indiceCards = {}
const indiceObservadoresPorNome = {}
const indiceObservadoresPorId = {}
const estadoComplementarCards = {}

let cardSendoArrastado = false
let modoFormularioCard = "editar"
let colunaCriacaoAtual = COLUNA_PADRAO
let proximoNumeroId = 1
let termoPesquisaAtual = ""
let debounceBuscaObservadores = null
let timeoutFeedbackArrasteModal = null
let atributosEditaveisModal = Object.assign({}, ATRIBUTOS_EDITAVEIS)
let colunasColapsadasNoInicioArrasteCard = new Set()
let colunasAutoAbertasDuranteArrasteCard = new Set()
const LIMITE_AUTO_SCROLL_PX = 72
const VELOCIDADE_MAX_AUTO_SCROLL_PX = 24
const LIMITE_TITULO_CARD = 200
const LIMITE_TEXTO_ATUALIZACAO = 220
const LIMITE_EXPANSAO_COMENTARIO = 280
const LIMITE_EXPANSAO_ATUALIZACAO = 140
const ID_CHAMADO_URL = obterIdChamadoDaUrl()
let idChamadoUrlJaProcessado = false
let kanbanInicializacaoConcluida = false
let comentarioMarcacoesSelecionadas = []
let comentarioMencionaveisDisponiveis = []
let comentarioMencionaveisCarregando = false
let comentarioMencionaveisErro = false
let comentarioMencionaveisRequisicaoSerial = 0

function obterIdChamadoDaUrl() {
    if (typeof window === "undefined" || !window.location || !window.location.search || typeof URLSearchParams !== "function") {
        return ""
    }

    const queryString = new URLSearchParams(window.location.search)
    return String(queryString.get("id_chamado") || "").trim()
}

function abrirChamadoDaUrlSeNecessario() {
    if (!ID_CHAMADO_URL || idChamadoUrlJaProcessado) {
        return
    }

    idChamadoUrlJaProcessado = true
    abrirModalEdicaoCard(ID_CHAMADO_URL)
}

function normalizarTextoBuscaMarcacao(texto) {
    return String(texto || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
}

function escaparRegexMarcacao(texto) {
    return String(texto || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
}

function obterContextoMarcacaoComentario(valor, posicaoCursor) {
    const texto = String(valor || "")
    const cursor = Math.max(0, Math.min(Number(posicaoCursor) || 0, texto.length))
    const textoAntes = texto.slice(0, cursor)
    const indiceArroba = textoAntes.lastIndexOf("@")

    if (indiceArroba < 0) {
        return null
    }

    const caractereAnterior = indiceArroba > 0 ? texto[indiceArroba - 1] : " "
    if (indiceArroba > 0 && /[A-Za-z0-9_.-]/.test(caractereAnterior)) {
        return null
    }

    const termo = texto.slice(indiceArroba + 1, cursor)
    if (/\s/.test(termo)) {
        return null
    }

    return {
        inicio: indiceArroba,
        fim: cursor,
        termo: termo
    }
}

function normalizarMencionavelComentario(usuario) {
    if (!usuario || typeof usuario !== "object") {
        return null
    }

    const idBruto = usuario.id !== undefined && usuario.id !== null && usuario.id !== ""
        ? usuario.id
        : (usuario.id_usuario !== undefined && usuario.id_usuario !== null ? usuario.id_usuario : "")
    const nome = String(usuario.apresentacao || usuario.nome_usuario || usuario.nome || "").trim()
    if (!nome) {
        return null
    }

    const id = String(idBruto || "").trim()
    if (USUARIO_ATUAL_ID && id && id === USUARIO_ATUAL_ID) {
        return null
    }

    return {
        id: id,
        nome: nome,
        apresentacao: nome,
        nome_setor: String(usuario.nome_setor || usuario.setor || "").trim(),
        nome_cargo: String(usuario.nome_cargo || usuario.cargo || "").trim(),
        email: String(usuario.email || "").trim()
    }
}

function normalizarListaMencionaveisComentario(listaMencionaveis = []) {
    const lista = Array.isArray(listaMencionaveis) ? listaMencionaveis : []
    const chavesVistas = new Set()

    return lista
        .map(normalizarMencionavelComentario)
        .filter(Boolean)
        .filter(function (item) {
            const chave = item.id
                ? `id:${item.id}`
                : `nome:${normalizarTextoBuscaMarcacao(item.nome)}`

            if (chavesVistas.has(chave)) {
                return false
            }

            chavesVistas.add(chave)
            return true
        })
}

function definirMencionaveisComentario(lista = []) {
    comentarioMencionaveisDisponiveis = normalizarListaMencionaveisComentario(lista)
    comentarioMencionaveisCarregando = false
    comentarioMencionaveisErro = false
}

function obterMencionaveisComentarioDisponiveis() {
    return Array.isArray(comentarioMencionaveisDisponiveis)
        ? comentarioMencionaveisDisponiveis.slice()
        : []
}

function atualizarMencionaveisComentarioDoModal() {
    return carregarMencionaveisComentario()
}

function carregarMencionaveisComentario(idChamado = null) {
    const id = String(idChamado || $("#editCardId").val() || "").trim()
    if (!id) {
        comentarioMencionaveisDisponiveis = []
        comentarioMencionaveisCarregando = false
        comentarioMencionaveisErro = false
        atualizarPainelMarcacaoComentario()
        return null
    }

    const responsavelId = String($("#editCardResponsavel").val() || "").trim()
    const observadoresIds = obterIdsObservadoresSelecionadosModal()
    const serial = ++comentarioMencionaveisRequisicaoSerial
    comentarioMencionaveisDisponiveis = []
    comentarioMencionaveisCarregando = true
    comentarioMencionaveisErro = false
    atualizarPainelMarcacaoComentario()

    const requisicao = requestAjax(
        {
            objeto: "ChamadosComentarios",
            metodo: "getMencionaveis",
            id_chamado: id,
            responsavel_id: responsavelId,
            observadores_json: JSON.stringify(observadoresIds)
        },
        function (result) {
            if (serial !== comentarioMencionaveisRequisicaoSerial) {
                return
            }

            const lista = Array.isArray(result) ? result : []
            definirMencionaveisComentario(lista)
            atualizarPainelMarcacaoComentario()

            const cardAtual = obterCardOuRascunhoPorId(id)
            if (cardAtual) {
                renderizarComentariosModal(cardAtual)
            }
        },
        false
    )

    if (requisicao && typeof requisicao.fail === "function") {
        requisicao.fail(function () {
            if (serial !== comentarioMencionaveisRequisicaoSerial) {
                return
            }

            comentarioMencionaveisCarregando = false
            comentarioMencionaveisErro = true
            atualizarPainelMarcacaoComentario()
        })
    }

    return requisicao
}

function mostrarPainelMarcacaoComentarioCarregando() {
    const $painel = $("#modalCommentMentionsPanel")
    if (!$painel.length) {
        return
    }

    $painel
        .removeClass("d-none")
        .html(`
            <div class="kanban-comment-mention-panel-header">Marcar pessoas</div>
            <div class="kanban-comment-mention-panel-empty">Carregando pessoas para menções...</div>
        `)
}

function mostrarPainelMarcacaoComentarioErro() {
    const $painel = $("#modalCommentMentionsPanel")
    if (!$painel.length) {
        return
    }

    $painel
        .removeClass("d-none")
        .html(`
            <div class="kanban-comment-mention-panel-header">Marcar pessoas</div>
            <div class="kanban-comment-mention-panel-empty">Nao foi possivel carregar as pessoas para mencao.</div>
        `)
}

function normalizarTextoComentarioEditor(texto) {
    return String(texto || "")
        .replace(/\u00a0/g, " ")
        .replace(/\u200b/g, "")
        .replace(/\r\n?/g, "\n")
}

function obterEditorComentarioModal() {
    return $("#modalCommentInput").get(0) || null
}

function editorComentarioEstaHabilitado() {
    const editor = obterEditorComentarioModal()
    if (!editor) {
        return false
    }

    return String(editor.getAttribute("contenteditable") || "").toLowerCase() === "true"
}

function normalizarEditorComentarioModalVazio() {
    const editor = obterEditorComentarioModal()
    if (!editor) {
        return
    }

    const texto = normalizarTextoComentarioEditor(editor.innerText || editor.textContent || "")
    if (!texto.trim()) {
        editor.innerHTML = ""
    }
}

function obterTextoComentarioModal() {
    const editor = obterEditorComentarioModal()
    if (!editor) {
        return ""
    }

    return $.trim(normalizarTextoComentarioEditor(editor.innerText || editor.textContent || ""))
}

function limparEditorComentarioModal() {
    const editor = obterEditorComentarioModal()
    if (!editor) {
        return
    }

    editor.innerHTML = ""
    editor.classList.remove("is-mentioning")
}

function obterSelecaoEditorComentario() {
    const editor = obterEditorComentarioModal()
    const selection = window.getSelection ? window.getSelection() : null
    if (!editor || !selection || selection.rangeCount === 0) {
        return null
    }

    const range = selection.getRangeAt(0)
    if (!editor.contains(range.commonAncestorContainer)) {
        return null
    }

    return {
        editor: editor,
        selection: selection,
        range: range
    }
}

function obterTextoAntesDoCursorComentario() {
    const contexto = obterSelecaoEditorComentario()
    if (!contexto) {
        return ""
    }

    const range = document.createRange()
    range.selectNodeContents(contexto.editor)

    try {
        range.setEnd(contexto.selection.focusNode, contexto.selection.focusOffset)
    } catch (erro) {
        return ""
    }

    return normalizarTextoComentarioEditor(range.toString())
}

function obterContextoMarcacaoComentarioEditor() {
    const textoAntes = obterTextoAntesDoCursorComentario()
    if (!textoAntes) {
        return null
    }

    return obterContextoMarcacaoComentario(textoAntes, textoAntes.length)
}

function obterSugestoesMarcacaoComentario(termo = "") {
    const pesquisa = normalizarTextoBuscaMarcacao(termo)
    const mencionaveis = obterMencionaveisComentarioDisponiveis()

    return mencionaveis
        .filter(function (pessoa) {
            if (!pesquisa) {
                return true
            }

            return normalizarTextoBuscaMarcacao(pessoa.nome).indexOf(pesquisa) !== -1
                || normalizarTextoBuscaMarcacao(pessoa.nome_setor).indexOf(pesquisa) !== -1
                || normalizarTextoBuscaMarcacao(pessoa.nome_cargo).indexOf(pesquisa) !== -1
        })
        .slice(0, 8)
}

function ocultarPainelMarcacaoComentario() {
    const $painel = $("#modalCommentMentionsPanel")
    if (!$painel.length) {
        return
    }

    $painel.addClass("d-none").empty()
    $("#modalCommentInput").removeClass("is-mentioning")
}

function sincronizarMarcacoesComentarioComTexto() {
    comentarioMarcacoesSelecionadas = obterMarcacoesComentarioAtivas()
}

function obterMarcacoesComentarioAtivas() {
    const editor = obterEditorComentarioModal()
    if (!editor) {
        return []
    }

    return Array.from(editor.querySelectorAll(".kanban-comment-mention-token[data-mention-id]")).map(function (elemento) {
        const cargo = String($(elemento).data("mention-cargo") || $(elemento).attr("data-mention-cargo") || "").trim()
        const setor = String($(elemento).data("mention-setor") || $(elemento).attr("data-mention-setor") || "").trim()
        const nome = String($(elemento).data("mention-name") || $(elemento).attr("data-mention-name") || $(elemento).text() || "").replace(/^@/, "").trim()

        return {
            id: String($(elemento).data("mention-id") || $(elemento).attr("data-mention-id") || "").trim(),
            nome: nome,
            apresentacao: nome,
            nome_cargo: cargo,
            cargo: cargo,
            nome_setor: setor,
            setor: setor
        }
    })
}

function criarNodoMarcacaoComentario(pessoa) {
    const nome = $.trim(String(pessoa && (pessoa.nome || pessoa.apresentacao) ? (pessoa.nome || pessoa.apresentacao) : ""))
    if (!nome) {
        return null
    }

    const nomeCargo = $.trim(String(pessoa && (pessoa.nome_cargo || pessoa.cargo) ? (pessoa.nome_cargo || pessoa.cargo) : ""))
    const nomeSetor = $.trim(String(pessoa && (pessoa.nome_setor || pessoa.setor) ? (pessoa.nome_setor || pessoa.setor) : ""))

    const token = document.createElement("span")
    token.className = "kanban-comment-mention-token"
    token.setAttribute("contenteditable", "false")
    token.setAttribute("spellcheck", "false")
    token.setAttribute("tabindex", "-1")
    token.setAttribute("data-mention-id", String(pessoa.id || "").trim())
    token.setAttribute("data-mention-name", nome)
    token.setAttribute("data-mention-cargo", nomeCargo)
    token.setAttribute("data-mention-setor", nomeSetor)
    const detalhes = [nomeCargo, nomeSetor].filter(Boolean).join(" / ")
    token.title = detalhes ? `${nome} - ${detalhes}` : nome
    token.textContent = `@${nome}`

    return token
}

function posicionarCursorAposNodo(nodo) {
    const editor = obterEditorComentarioModal()
    if (!editor || !nodo || typeof document.createRange !== "function") {
        return
    }

    const selection = window.getSelection ? window.getSelection() : null
    if (!selection) {
        return
    }

    const range = document.createRange()
    range.setStartAfter(nodo)
    range.collapse(true)
    selection.removeAllRanges()
    selection.addRange(range)
    editor.focus()
}

function inserirTextoNoEditorComentario(texto) {
    const editor = obterEditorComentarioModal()
    const contexto = obterSelecaoEditorComentario()
    if (!editor || !contexto || !editorComentarioEstaHabilitado()) {
        return false
    }

    const range = contexto.selection.getRangeAt(0)
    range.deleteContents()

    const nodoTexto = document.createTextNode(String(texto || ""))
    range.insertNode(nodoTexto)
    posicionarCursorAposNodo(nodoTexto)
    normalizarEditorComentarioModalVazio()
    return true
}

function inserirMarcacaoComentario(pessoa) {
    const editor = obterEditorComentarioModal()
    const contexto = obterSelecaoEditorComentario()
    if (!editor || !pessoa || !contexto || !editorComentarioEstaHabilitado()) {
        return
    }

    const selecao = contexto.selection
    const range = selecao.getRangeAt(0)
    const focusNode = selecao.focusNode
    const focusOffset = Number(selecao.focusOffset) || 0
    const token = criarNodoMarcacaoComentario(pessoa)
    if (!token) {
        return
    }

    let inserido = false

    if (focusNode && focusNode.nodeType === Node.TEXT_NODE) {
        const valorAtual = String(focusNode.nodeValue || "")
        const textoAntes = valorAtual.slice(0, focusOffset)
        const contextoTexto = obterContextoMarcacaoComentario(textoAntes, textoAntes.length)

        if (contextoTexto && focusNode.parentNode) {
            const antes = valorAtual.slice(0, contextoTexto.inicio)
            const depois = valorAtual.slice(focusOffset)
            const fragmento = document.createDocumentFragment()

            if (antes) {
                fragmento.appendChild(document.createTextNode(antes))
            }

            fragmento.appendChild(token)
            fragmento.appendChild(document.createTextNode(" "))

            if (depois) {
                fragmento.appendChild(document.createTextNode(depois))
            }

            focusNode.parentNode.replaceChild(fragmento, focusNode)
            inserido = true
        }
    }

    if (!inserido) {
        range.deleteContents()
        const fragmento = document.createDocumentFragment()
        const espaco = document.createTextNode(" ")

        fragmento.appendChild(token)
        fragmento.appendChild(espaco)
        range.insertNode(fragmento)
        posicionarCursorAposNodo(espaco)
    } else {
        posicionarCursorAposNodo(token.nextSibling || token)
    }

    ocultarPainelMarcacaoComentario()
    sincronizarMarcacoesComentarioComTexto()
    normalizarEditorComentarioModalVazio()
    editor.focus()
}

function atualizarPainelMarcacaoComentario() {
    const editor = obterEditorComentarioModal()
    const $painel = $("#modalCommentMentionsPanel")

    if (!editor || !$painel.length || !editorComentarioEstaHabilitado()) {
        ocultarPainelMarcacaoComentario()
        return
    }

    const contexto = obterContextoMarcacaoComentarioEditor()

    if (!contexto) {
        ocultarPainelMarcacaoComentario()
        return
    }

    if (comentarioMencionaveisErro) {
        mostrarPainelMarcacaoComentarioErro()
        return
    }

    if (comentarioMencionaveisCarregando) {
        mostrarPainelMarcacaoComentarioCarregando()
        return
    }

    const sugestoes = obterSugestoesMarcacaoComentario(contexto.termo)
    $("#modalCommentInput").addClass("is-mentioning")

    if (!sugestoes.length) {
        $painel
            .removeClass("d-none")
            .html(`
                <div class="kanban-comment-mention-panel-header">Marcar pessoas</div>
                <div class="kanban-comment-mention-panel-empty">Nenhuma pessoa encontrada</div>
            `)
        return
    }

    $painel
        .removeClass("d-none")
        .html(`
            <div class="kanban-comment-mention-panel-header">Marcar pessoas</div>
            <div class="kanban-comment-mention-panel-list">
                ${sugestoes.map(function (pessoa) {
                    const setor = String(pessoa.nome_setor || "").trim()
                    const cargo = String(pessoa.nome_cargo || "").trim()
                    return `
                        <button type="button" class="kanban-mention-item" data-id="${escaparHtml(pessoa.id)}" data-nome="${escaparHtml(pessoa.nome)}" data-cargo="${escaparHtml(cargo)}" data-setor="${escaparHtml(setor)}">
                            <span class="kanban-mention-item-content">
                                <span class="kanban-mention-item-name">${escaparHtml(pessoa.nome)}</span>
                                ${setor ? `<span class="kanban-mention-item-sector">${escaparHtml(setor)}</span>` : ""}
                            </span>
                            ${cargo ? `<span class="kanban-mention-item-role">${escaparHtml(cargo)}</span>` : (setor ? `<span class="kanban-mention-item-role">${escaparHtml(setor)}</span>` : "")}
                        </button>
                    `
                }).join("")}
            </div>
        `)
}

function limparMarcacoesComentario() {
    comentarioMarcacoesSelecionadas = []
    ocultarPainelMarcacaoComentario()
}

function finalizarInicializacaoKanban() {
    if (kanbanInicializacaoConcluida) {
        return
    }

    kanbanInicializacaoConcluida = true
    $(".kanban-board-wrapper").first().removeClass("kanban-board-loading")
}

function obterAtributosEditaveisModal() {
    return atributosEditaveisModal || ATRIBUTOS_EDITAVEIS
}

function definirAtributosEditaveisModal(atributos = ATRIBUTOS_EDITAVEIS) {
    atributosEditaveisModal = Object.assign({}, ATRIBUTOS_EDITAVEIS, atributos || {})
}

function sincronizarBotaoEnviarComentarioModal() {
    const $botao = $("#modalAddCommentBtn")
    if (!$botao.length) {
        return
    }

    const atributos = obterAtributosEditaveisModal()
    const habilitado = Boolean(atributos && atributos.criar_comentarios)

    $botao.prop("disabled", !habilitado)
    $botao.attr("aria-disabled", habilitado ? "false" : "true")

    if (habilitado) {
        $botao.removeAttr("disabled")
    }
}

function obterAtributosCriacaoCard() {
    return Object.assign({}, ATRIBUTOS_EDITAVEIS, {
        titulo: true,
        descricao: true,
        observadores: true,
        anexar_arquivos: true,
        gerenciar_checklist: true,
        tipo_chamado: true,
        modulo: true,
        tipo_chamado: true,
    })
}

function limparTempIdArquivoModal() {
    $("#addArquivoTempId").val("")
}

function obterChaveRascunhoCriacaoCard(tempId = null) {
    const identificador = $.trim(tempId || $("#addArquivoTempId").val() || "")
    return identificador ? `temp:${identificador}` : ""
}

function obterChaveChecklistModal(cardId = null) {
    const chaveCard = String(cardId || $("#editCardId").val() || "").trim()

    if (chaveCard) {
        return chaveCard
    }

    if (modoFormularioCard !== "criar") {
        return ""
    }

    const tempId = obterTempIdArquivoModal()
    return tempId ? obterChaveRascunhoCriacaoCard(tempId) : ""
}

function definirTempIdArquivoModal() {
    const tempId = gerarId()
    $("#addArquivoTempId").val(tempId)
    return tempId
}

function obterTempIdArquivoModal() {
    const tempIdAtual = $.trim($("#addArquivoTempId").val() || "")

    if (tempIdAtual) {
        return tempIdAtual
    }

    if (modoFormularioCard !== "criar") {
        return ""
    }

    return definirTempIdArquivoModal()
}

function limparEstadoComplementarCard(cardId) {
    const chave = String(cardId || "").trim()
    if (!chave) {
        return
    }

    delete estadoComplementarCards[chave]
}

function obterCardOuRascunhoPorId(cardId) {
    const chave = obterChaveChecklistModal(cardId)
    if (!chave) {
        return null
    }

    const card = obterCardPorId(chave)
    if (card) {
        const estado = obterEstadoComplementarCard(chave)
        const checklist = estado && Array.isArray(estado.checklist) && estado.checklist.length
            ? estado.checklist
            : (Array.isArray(card.checklist) ? card.checklist : [])
        card.checklist = normalizarItensChecklist(checklist)
        return card
    }

    const estado = obterEstadoComplementarCard(chave)
    if (!estado) {
        return null
    }

    return {
        id: chave,
        comentarios: normalizarComentarios(estado.comentarios),
        arquivos: normalizarArquivos(estado.arquivos),
        checklist: normalizarItensChecklist(estado.checklist)
    }
}

function calcularVelocidadeAutoScroll(distancia) {
    if (!Number.isFinite(distancia) || distancia <= 0) {
        return 0
    }

    const intensidade = Math.min(1, distancia / LIMITE_AUTO_SCROLL_PX)
    return Math.max(6, Math.ceil(intensidade * VELOCIDADE_MAX_AUTO_SCROLL_PX))
}

function aplicarAutoScrollHorizontal(pageX) {
    if (!Number.isFinite(pageX)) {
        return
    }

    const $wrapper = $(".kanban-board-wrapper").first()
    if (!$wrapper.length) {
        return
    }

    const offset = $wrapper.offset()
    if (!offset) {
        return
    }

    const bordaEsquerda = offset.left + LIMITE_AUTO_SCROLL_PX
    const bordaDireita = offset.left + $wrapper.outerWidth() - LIMITE_AUTO_SCROLL_PX

    if (pageX < bordaEsquerda) {
        const velocidade = calcularVelocidadeAutoScroll(bordaEsquerda - pageX)
        $wrapper.scrollLeft($wrapper.scrollLeft() - velocidade)
    } else if (pageX > bordaDireita) {
        const velocidade = calcularVelocidadeAutoScroll(pageX - bordaDireita)
        $wrapper.scrollLeft($wrapper.scrollLeft() + velocidade)
    }
}

function aplicarAutoScrollVerticalNaLista($lista, pageY) {
    if (!$lista || !$lista.length || !Number.isFinite(pageY)) {
        return
    }

    const offset = $lista.offset()
    if (!offset) {
        return
    }

    const bordaTopo = offset.top + LIMITE_AUTO_SCROLL_PX
    const bordaBase = offset.top + $lista.outerHeight() - LIMITE_AUTO_SCROLL_PX

    if (pageY < bordaTopo) {
        const velocidade = calcularVelocidadeAutoScroll(bordaTopo - pageY)
        $lista.scrollTop($lista.scrollTop() - velocidade)
    } else if (pageY > bordaBase) {
        const velocidade = calcularVelocidadeAutoScroll(pageY - bordaBase)
        $lista.scrollTop($lista.scrollTop() + velocidade)
    }
}

function aplicarAutoScrollDuranteArraste(event, ui) {
    if (!event) {
        return
    }

    const pageX = Number(event.pageX)
    const pageY = Number(event.pageY)

    aplicarAutoScrollHorizontal(pageX)

    const $listaAtual = ui && ui.placeholder && ui.placeholder.parent().hasClass("kanban-cards")
        ? ui.placeholder.parent()
        : (ui && ui.item ? ui.item.closest(".kanban-cards") : $())

    aplicarAutoScrollVerticalNaLista($listaAtual, pageY)
}

function getChaveOrdemColunasKanban() {
    const usuario = String(USUARIO_ATUAL_ID || "").trim() || "anon"
    return `${CHAMADOS_COLUMN_ORDER_STORAGE_KEY}:${window.location.pathname}:${usuario}`
}

function getChaveColunasColapsadasKanban() {
    const usuario = String(USUARIO_ATUAL_ID || "").trim() || "anon"
    return `${CHAMADOS_COLUMN_COLLAPSED_STORAGE_KEY}:${window.location.pathname}:${usuario}`
}

function normalizarOrdemColunasKanban(ordem) {
    if (!Array.isArray(ordem) || !ordem.length) {
        return null
    }

    const indicePadraoPorColuna = colunasKanban.reduce(function (acumulador, coluna, indice) {
        acumulador[coluna] = indice
        return acumulador
    }, {})

    const normalizada = ordem
        .map(function (valor) {
            return String(valor || "").trim()
        })
        .filter(function (valor) {
            return Boolean(valor) && colunasKanban.includes(valor)
        })

    if (!normalizada.length) {
        return null
    }

    const semDuplicadas = []
    normalizada.forEach(function (coluna) {
        if (!semDuplicadas.includes(coluna)) {
            semDuplicadas.push(coluna)
        }
    })

    colunasKanban.forEach(function (coluna) {
        if (semDuplicadas.includes(coluna)) {
            return
        }

        const indicePadrao = indicePadraoPorColuna[coluna]
        let posicaoInsercao = semDuplicadas.findIndex(function (colunaExistente) {
            return indicePadraoPorColuna[colunaExistente] > indicePadrao
        })

        if (posicaoInsercao === -1) {
            posicaoInsercao = semDuplicadas.length
        }

        semDuplicadas.splice(posicaoInsercao, 0, coluna)
    })

    return semDuplicadas.length === colunasKanban.length ? semDuplicadas : null
}

function obterOrdemColunasKanbanSalva() {
    const chave = getChaveOrdemColunasKanban()

    try {
        const bruto = window.localStorage.getItem(chave)
        if (!bruto) {
            return null
        }

        const parseado = JSON.parse(bruto)
        return normalizarOrdemColunasKanban(parseado)
    } catch (erro) {
        return null
    }
}

function salvarOrdemColunasKanban() {
    const chave = getChaveOrdemColunasKanban()
    const ordemAtual = $(".kanban-board")
        .first()
        .children(".kanban-column")
        .map(function () {
            return String($(this).data("column") || "").trim()
        })
        .get()

    const ordemNormalizada = normalizarOrdemColunasKanban(ordemAtual)
    if (!ordemNormalizada) {
        return
    }

    try {
        window.localStorage.setItem(chave, JSON.stringify(ordemNormalizada))
    } catch (erro) {
    }
}

function aplicarOrdemColunasKanban() {
    const $board = $(".kanban-board").first()
    if (!$board.length) {
        return
    }

    const ordem = obterOrdemColunasKanbanSalva() || colunasKanban.slice()

    ordem.forEach(function (coluna) {
        const $coluna = $board.children(`.kanban-column[data-column="${coluna}"]`).first()
        if ($coluna.length) {
            $board.append($coluna)
        }
    })
}

function normalizarColunasColapsadasKanban(colapsadas) {
    if (!Array.isArray(colapsadas)) {
        return []
    }

    return colapsadas
        .map(function (valor) {
            return String(valor || "").trim()
        })
        .filter(function (valor, indice, lista) {
            return Boolean(valor) && colunasKanban.includes(valor) && lista.indexOf(valor) === indice
        })
}

function obterColunasColapsadasKanbanSalvas() {
    const chave = getChaveColunasColapsadasKanban()

    try {
        const bruto = window.localStorage.getItem(chave)
        if (!bruto) {
            return []
        }

        const parseado = JSON.parse(bruto)
        return normalizarColunasColapsadasKanban(parseado)
    } catch (erro) {
        return []
    }
}

function salvarColunasColapsadasKanban() {
    const chave = getChaveColunasColapsadasKanban()
    const colapsadas = normalizarColunasColapsadasKanban(
        $(".kanban-column.collapsed-card")
            .map(function () {
                return String($(this).data("column") || "").trim()
            })
            .get()
    )

    try {
        window.localStorage.setItem(chave, JSON.stringify(colapsadas))
    } catch (erro) {
    }
}

function atualizarEstadoColunaColapsadaKanban(coluna, estaColapsada) {
    const chave = getChaveColunasColapsadasKanban()
    const colunaNormalizada = String(coluna || "").trim()

    if (!colunaNormalizada || !colunasKanban.includes(colunaNormalizada)) {
        return
    }

    const colapsadas = new Set(obterColunasColapsadasKanbanSalvas())
    if (estaColapsada) {
        colapsadas.add(colunaNormalizada)
    } else {
        colapsadas.delete(colunaNormalizada)
    }

    try {
        window.localStorage.setItem(chave, JSON.stringify(normalizarColunasColapsadasKanban(Array.from(colapsadas))))
    } catch (erro) {
    }
}

function aplicarColunasColapsadasKanban() {
    const colapsadas = obterColunasColapsadasKanbanSalvas()
    if (!colapsadas.length) {
        return
    }

    colapsadas.forEach(function (coluna) {
        const $coluna = $(`.kanban-column[data-column="${coluna}"]`).first()
        const $botao = $coluna.find(".kanban-column-toggle[data-card-widget='collapse']").first()

        if ($coluna.length && !$coluna.hasClass("collapsed-card") && $botao.length) {
            $botao.trigger("click")
        }
    })
}

function obterColunasKanbanColapsadasAtuais() {
    return $(".kanban-column.collapsed-card")
        .map(function () {
            return String($(this).data("column") || "").trim()
        })
        .get()
        .filter(function (valor) {
            return Boolean(valor) && colunasKanban.includes(valor)
        })
}

function pontoDentroElemento(pageX, pageY, $elemento) {
    if (!$elemento || !$elemento.length || !Number.isFinite(pageX) || !Number.isFinite(pageY)) {
        return false
    }

    const offset = $elemento.offset()
    if (!offset) {
        return false
    }

    const largura = $elemento.outerWidth()
    const altura = $elemento.outerHeight()

    if (!Number.isFinite(largura) || !Number.isFinite(altura) || largura <= 0 || altura <= 0) {
        return false
    }

    return pageX >= offset.left
        && pageX <= offset.left + largura
        && pageY >= offset.top
        && pageY <= offset.top + altura
}

function atualizarSortableCardsPosicoes() {
    $(".kanban-cards").each(function () {
        if ($(this).data("ui-sortable")) {
            try {
                $(this).sortable("refreshPositions")
            } catch (erro) {
            }
        }
    })
}

function alternarColunaKanban(coluna, expandir) {
    const $coluna = $(`.kanban-column[data-column="${coluna}"]`).first()
    if (!$coluna.length) {
        return false
    }

    const estaColapsada = $coluna.hasClass("collapsed-card")
    if (expandir === true && !estaColapsada) {
        return false
    }

    if (expandir === false && estaColapsada) {
        return false
    }

    const $botao = $coluna.find(".kanban-column-toggle[data-card-widget='collapse']").first()
    if (!$botao.length) {
        return false
    }

    $botao.trigger("click")
    return true
}

function gerenciarColunasDuranteArrasteCard(event) {
    const pageX = Number(event && event.pageX)
    const pageY = Number(event && event.pageY)

    if (!Number.isFinite(pageX) || !Number.isFinite(pageY)) {
        return
    }

    colunasColapsadasNoInicioArrasteCard.forEach(function (coluna) {
        const $coluna = $(`.kanban-column[data-column="${coluna}"]`).first()
        if (!$coluna.length) {
            return
        }

        const estaDentro = pontoDentroElemento(pageX, pageY, $coluna)

        if (estaDentro) {
            if ($coluna.hasClass("collapsed-card")) {
                alternarColunaKanban(coluna, true)
                colunasAutoAbertasDuranteArrasteCard.add(coluna)
                atualizarSortableCardsPosicoes()
            }
            return
        }

        if (colunasAutoAbertasDuranteArrasteCard.has(coluna) && !$coluna.hasClass("collapsed-card")) {
            alternarColunaKanban(coluna, false)
            colunasAutoAbertasDuranteArrasteCard.delete(coluna)
            atualizarSortableCardsPosicoes()
        }
    })
}

function atualizarCorCabecalhoModal(coluna) {
    const classeBg = classesBgCabecalhoModalPorColuna[coluna] || "bg-primary"
    const classeTexto = ["andamento", "em_validacao"].includes(coluna) ? "text-dark" : "text-white"

    const $header = $("#modalEditarCardHeader")
    const $titulo = $("#modalEditarCardLabel")
    const $botaoFechar = $("#modalEditarCardClose")

    if (!$header.length) {
        return
    }

    $header.removeClass(classesBgCabecalhoModal.join(" "))
    $header.addClass(classeBg)

    $titulo.removeClass("text-white text-dark")
    $titulo.addClass(classeTexto)

    $botaoFechar.removeClass("text-white text-dark")
    $botaoFechar.addClass(classeTexto)
}

function escaparHtml(valor) {
    return String(valor || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
}


function sanitizarRichText(html) {
    return String(html || "").replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, "")
}

function removerTagsHtml(html) {
    return String(html || "").replace(/<[^>]*>/g, " ")
}

function normalizarTextoPesquisa(texto) {
    const valor = String(texto || "").toLowerCase().trim()

    if (!valor) {
        return ""
    }

    if (typeof valor.normalize === "function") {
        return valor.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
    }

    return valor
}

function formatarDataBr(dataIso) {
    if (!dataIso || dataIso.indexOf("-") === -1) {
        return ""
    }

    const partes = dataIso.split("-")
    if (partes.length !== 3) {
        return dataIso
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`
}

function formatarDataHoraBr(dataIso) {
    if (!dataIso) {
        return "-"
    }

    const data = new Date(dataIso)
    if (Number.isNaN(data.getTime())) {
        return escaparHtml(dataIso)
    }

    const doisDigitos = function (valor) {
        return String(valor).padStart(2, "0")
    }

    return `${doisDigitos(data.getDate())}/${doisDigitos(data.getMonth() + 1)}/${data.getFullYear()} ${doisDigitos(data.getHours())}:${doisDigitos(data.getMinutes())}`
}

function obterDataHoraAtualIso() {
    return new Date().toISOString()
}

function criarAtualizacao(descricao, apresentacao = USUARIO_ATUAL, criado_em = obterDataHoraAtualIso()) {
    return {
        descricao: String(descricao || "").trim(),
        apresentacao: String(apresentacao || USUARIO_ATUAL).trim() || USUARIO_ATUAL,
        criado_em: criado_em || obterDataHoraAtualIso()
    }
}

function normalizarAtualizacoes(listaAtualizacoes) {
    const lista = Array.isArray(listaAtualizacoes) ? listaAtualizacoes : []

    return lista
        .map(function (item) {
            const descricao = item && item.descricao ? item.descricao : ""
            const apresentacao = item && item.apresentacao ? item.apresentacao : "Sistema"
            const criado_em = item && item.criado_em ? item.criado_em : obterDataHoraAtualIso()
            return criarAtualizacao(descricao, apresentacao, criado_em)
        })
        .filter(function (item) {
            return item.descricao.length > 0
        })
}

function gerarIdLocal(prefixo = "item") {
    return `${prefixo}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
}

function obterTimestampSeguro(dataIso) {
    const timestamp = new Date(dataIso || "").getTime()
    return Number.isNaN(timestamp) ? 0 : timestamp
}

function ordenarItensPorDataAsc(itemA, itemB) {
    return obterTimestampSeguro(itemA && itemA.criado_em) - obterTimestampSeguro(itemB && itemB.criado_em)
}

function ordenarItensPorDataDesc(itemA, itemB) {
    return obterTimestampSeguro(itemB && itemB.criado_em) - obterTimestampSeguro(itemA && itemA.criado_em)
}

function criarComentario(comentario, apresentacao = USUARIO_ATUAL, criado_em = obterDataHoraAtualIso(), id = null, criadoPorId = USUARIO_ATUAL_ID) {
    return {
        id: String(id || gerarIdLocal("comentario")),
        comentario: String(comentario || "").trim(),
        apresentacao: String(apresentacao || USUARIO_ATUAL).trim() || USUARIO_ATUAL,
        criado_em: criado_em || obterDataHoraAtualIso(),
        criado_por_id: String(criadoPorId || "").trim()
    }
}

function normalizarComentarios(listaComentarios) {
    const lista = Array.isArray(listaComentarios) ? listaComentarios : []

    return lista
        .map(function (item) {
            const comentario = item && item.comentario ? item.comentario : ""
            const apresentacao = item && item.apresentacao ? item.apresentacao : "Sistema"
            const criado_em = item && item.criado_em ? item.criado_em : obterDataHoraAtualIso()
            const id = item && item.id ? item.id : gerarIdLocal("comentario")
            const criadoPorId = item && item.criado_por_id !== undefined && item.criado_por_id !== null ? item.criado_por_id : ""
            return {
                id: String(id),
                comentario: String(comentario || "").trim(),
                apresentacao: String(apresentacao || "Sistema").trim() || "Sistema",
                criado_em: criado_em || obterDataHoraAtualIso(),
                criado_por_id: String(criadoPorId || "").trim()
            }
        })
        .filter(function (item) {
            return item.comentario.length > 0
        })
        .sort(ordenarItensPorDataAsc)
}

function obterExtensaoArquivo(nomeArquivo, mime = "") {
    const nome = String(nomeArquivo || "").trim()
    const partes = nome.split(".")
    if (partes.length > 1) {
        return String(partes.pop() || "").toLowerCase()
    }

    const mimeNormalizado = String(mime || "")
        .split(";")[0]
        .trim()
        .toLowerCase()

    if (MIME_PARA_EXTENSAO_ARQUIVO[mimeNormalizado]) {
        return MIME_PARA_EXTENSAO_ARQUIVO[mimeNormalizado]
    }

    if (/^[a-z0-9]{1,15}$/.test(mimeNormalizado)) {
        return mimeNormalizado
    }

    return ""
}

function arquivoEhPermitidoParaAnexo(arquivo) {
    if (!arquivo || typeof arquivo.name !== "string") {
        return false
    }

    const mime = String(arquivo.type || "").split(";")[0].trim().toLowerCase()
    const extensao = obterExtensaoArquivo(arquivo.name, mime)

    return Boolean(extensao && EXTENSOES_ANEXO_PERMITIDAS.has(extensao))
}

function criarAnexo(nome, mime = "", tamanho = 0, criado_em = obterDataHoraAtualIso(), id = null, criadoPorId = USUARIO_ATUAL_ID) {
    return {
        id: String(id || gerarIdLocal("anexo")),
        nome: String(nome || "").trim(),
        mime: String(mime || "").trim().toLowerCase(),
        tamanho: Math.max(0, Number(tamanho) || 0),
        criado_em: criado_em || obterDataHoraAtualIso(),
        criado_por_id: String(criadoPorId || "").trim()
    }
}

function normalizarArquivos(listaArquivos) {
    const lista = Array.isArray(listaArquivos) ? listaArquivos : []

    return lista
        .map(function (item) {
            const nome = item && item.nome ? item.nome : item && item.filename ? item.filename : ""
            const mime = item && item.mime ? item.mime : item && item.tipo ? item.tipo : item && item.type ? item.type : ""
            const tamanho = item && item.tamanho !== undefined ? item.tamanho : item && item.size !== undefined ? item.size : 0
            const criado_em = item && item.criado_em ? item.criado_em : obterDataHoraAtualIso()
            const id = item && item.id ? item.id : gerarIdLocal("anexo")
            const criadoPorId = item && item.criado_por_id !== undefined && item.criado_por_id !== null ? item.criado_por_id : ""

            return {
                id: String(id),
                nome: String(nome || "").trim(),
                mime: String(mime || "").trim().toLowerCase(),
                tamanho: Math.max(0, Number(tamanho) || 0),
                criado_em: criado_em || obterDataHoraAtualIso(),
                criado_por_id: String(criadoPorId || "").trim()
            }
        })
        .filter(function (item) {
            return item.nome.length > 0
        })
        .sort(ordenarItensPorDataDesc)
}

function formatarTamanhoArquivo(tamanhoBytes) {
    const tamanho = Math.max(0, Number(tamanhoBytes) || 0)
    if (tamanho < 1024) {
        return `${tamanho} B`
    }
    if (tamanho < 1024 * 1024) {
        return `${(tamanho / 1024).toFixed(1)} KB`
    }
    return `${(tamanho / (1024 * 1024)).toFixed(1)} MB`
}

const MIME_PARA_EXTENSAO_ARQUIVO = {
    "application/pdf": "pdf",
    "application/msword": "doc",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
    "application/vnd.ms-excel": "xls",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet": "xlsx",
    "application/vnd.ms-powerpoint": "ppt",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation": "pptx",
    "application/vnd.oasis.opendocument.text": "odt",
    "application/vnd.oasis.opendocument.spreadsheet": "ods",
    "text/plain": "txt",
    "text/csv": "csv",
    "application/zip": "zip",
    "application/x-rar-compressed": "rar",
    "application/vnd.rar": "rar",
    "application/x-7z-compressed": "7z",
    "image/jpeg": "jpg",
    "image/png": "png",
    "image/gif": "gif",
    "image/webp": "webp",
    "image/bmp": "bmp",
    "image/svg+xml": "svg"
}

const EXTENSOES_ANEXO_PERMITIDAS = new Set([
    "jpg",
    "jpeg",
    "png",
    "gif",
    "webp",
    "bmp",
    "svg",
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx",
    "ppt",
    "pptx",
    "txt",
    "zip",
    "rar",
    "7z",
    "csv",
    "ods"
])

const EXTENSOES_ANEXO_PREVIEW = new Set([
    "jpg",
    "jpeg",
    "png",
    "gif",
    "webp",
    "bmp",
    "svg",
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx",
    "csv",
    "ods",
    "txt"
])

function formatarRotuloExtensao(extensao) {
    const valor = String(extensao || "").trim().toLowerCase()
    return valor ? `.${valor}` : "arquivo"
}

function obterTipoVisualAnexo(anexo) {
    const mime = String(anexo && anexo.mime ? anexo.mime : "").toLowerCase()
    const extensao = obterExtensaoArquivo(anexo && anexo.nome, mime)
    const previewavel = EXTENSOES_ANEXO_PREVIEW.has(extensao)

    if (mime.indexOf("image/") === 0 || ["png", "jpg", "jpeg", "gif", "webp", "bmp", "svg"].includes(extensao)) {
        return {
            classe: "is-image",
            icone: "fas fa-file-image",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: true,
            tipoPreview: "image"
        }
    }

    if (mime.indexOf("pdf") !== -1 || extensao === "pdf") {
        return {
            classe: "is-pdf",
            icone: "fas fa-file-pdf",
            rotulo: formatarRotuloExtensao("pdf"),
            previewavel: true,
            tipoPreview: "iframe"
        }
    }

    if (["doc", "docx", "odt", "rtf"].includes(extensao)) {
        return {
            classe: "is-doc",
            icone: "fas fa-file-word",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: previewavel,
            tipoPreview: "iframe"
        }
    }

    if (["xls", "xlsx", "csv", "ods"].includes(extensao)) {
        return {
            classe: "is-sheet",
            icone: "fas fa-file-excel",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: previewavel,
            tipoPreview: "iframe"
        }
    }

    if (["ppt", "pptx"].includes(extensao)) {
        return {
            classe: "",
            icone: "fas fa-file-powerpoint",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: false,
            tipoPreview: "iframe"
        }
    }

    if (["zip", "rar", "7z"].includes(extensao)) {
        return {
            classe: "",
            icone: "fas fa-file-archive",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: false,
            tipoPreview: "iframe"
        }
    }

    if (["txt", "md", "log"].includes(extensao)) {
        return {
            classe: "",
            icone: "fas fa-file-alt",
            rotulo: formatarRotuloExtensao(extensao),
            previewavel: previewavel,
            tipoPreview: "iframe"
        }
    }

    return {
        classe: "",
        icone: "fas fa-file",
        rotulo: formatarRotuloExtensao(extensao),
        previewavel: previewavel,
        tipoPreview: "iframe"
    }
}

function obterUrlAnexoChamado(anexo, preview = false) {
    const id = String(anexo && anexo.id ? anexo.id : "").trim()
    if (!id) {
        return ""
    }

    const urlBase = `/webconfef/admin/content/chamados/download.php?id=${encodeURIComponent(id)}`
    return preview ? `${urlBase}&preview=1` : urlBase
}

function abrirVisualizacaoAnexo(url, tipo, nome) {
    const urlNormalizada = String(url || "").trim()
    if (!urlNormalizada) {
        return
    }

    const tipoFancybox = String(tipo || "").toLowerCase() === "image" ? "image" : "iframe"

    if ($.fancybox && typeof $.fancybox.open === "function") {
        $.fancybox.open([{
            src: urlNormalizada,
            type: tipoFancybox,
            opts: {
                caption: String(nome || "").trim()
            }
        }])
        return
    }

    window.open(urlNormalizada, "_blank", "noopener")
}

function obterEstadoComplementarCard(cardId) {
    const chave = String(cardId || "").trim()
    if (!chave) {
        return null
    }

    if (!estadoComplementarCards[chave]) {
        estadoComplementarCards[chave] = {
            comentarios: [],
            arquivos: [],
            checklist: []
        }
    } else if (!Array.isArray(estadoComplementarCards[chave].checklist)) {
        estadoComplementarCards[chave].checklist = []
    }

    return estadoComplementarCards[chave]
}

function sincronizarEstadoComplementarCard(card) {
    if (!card || !card.id) {
        return
    }

    const estado = obterEstadoComplementarCard(card.id)
    if (!estado) {
        return
    }

    estado.comentarios = normalizarComentarios(card.comentarios)
    estado.arquivos = normalizarArquivos(card.arquivos)
    if (Array.isArray(card.checklist)) {
        estado.checklist = normalizarItensChecklist(card.checklist)
    } else if (!Array.isArray(estado.checklist)) {
        estado.checklist = []
    }
}

function usuarioPodeGerenciarChecklistModal() {
    if (modoFormularioCard === "criar") {
        return true
    }

    const atributos = obterAtributosEditaveisModal()
    return Boolean(atributos && atributos.gerenciar_checklist)
}

function usuarioEhCriadorCardModal(card) {
    const cardCriadorId = String(card && card.criado_por_id !== undefined && card.criado_por_id !== null ? card.criado_por_id : "").trim()
    return Boolean(USUARIO_ATUAL_ID && cardCriadorId && cardCriadorId === USUARIO_ATUAL_ID)
}

function usuarioEhObservadorCardModal(card) {
    const observadores = Array.isArray(card && card.observadores) ? card.observadores : []

    if (!USUARIO_ATUAL_ID || !observadores.length) {
        return false
    }

    return observadores.some(function (observador) {
        const idObservador = String(
            observador && observador.id !== undefined && observador.id !== null
                ? observador.id
                : (observador && observador.id_usuario !== undefined && observador.id_usuario !== null ? observador.id_usuario : "")
        ).trim()

        return Boolean(idObservador && idObservador === USUARIO_ATUAL_ID)
    })
}

function usuarioPodeVerChecklistModal(card) {
    if (modoFormularioCard === "criar") {
        return true
    }

    const atributos = obterAtributosEditaveisModal()
    return Boolean(atributos && atributos.gerenciar_checklist)
}

function obterChecklistEstadoCard(cardId) {
    const chaveCard = obterChaveChecklistModal(cardId)
    if (!chaveCard) {
        return null
    }

    const estado = obterEstadoComplementarCard(chaveCard)
    if (!estado) {
        return null
    }

    if (!Array.isArray(estado.checklist)) {
        estado.checklist = []
    }

    return estado.checklist
}

function sincronizarChecklistEstadoCard(cardId, listaItens = []) {
    const chaveCard = String(cardId || "").trim()
    if (!chaveCard) {
        return []
    }

    const checklistNormalizado = normalizarItensChecklist(listaItens)
    const estado = obterEstadoComplementarCard(chaveCard)

    if (estado) {
        estado.checklist = checklistNormalizado
    }

    const cardAtual = obterCardPorId(chaveCard)
    if (cardAtual) {
        cardAtual.checklist = checklistNormalizado
    }

    return checklistNormalizado
}

function atualizarChecklistItemNaMemoria(cardId, itemAtualizado) {
    const chaveCard = String(cardId || "").trim()
    const chaveItem = String(itemAtualizado && itemAtualizado.id ? itemAtualizado.id : "").trim()

    if (!chaveCard || !chaveItem) {
        return []
    }

    const itensAtuais = Array.isArray(obterChecklistEstadoCard(chaveCard))
        ? obterChecklistEstadoCard(chaveCard).slice()
        : []

    let itemEncontrado = false
    const itensMesclados = itensAtuais.map(function (item) {
        const idItem = String(item && item.id ? item.id : "").trim()
        if (idItem !== chaveItem) {
            return item
        }

        itemEncontrado = true
        return Object.assign({}, item, itemAtualizado)
    })

    if (!itemEncontrado) {
        itensMesclados.push(itemAtualizado)
    }

    return sincronizarChecklistEstadoCard(chaveCard, itensMesclados)
}

function normalizarBooleanoChecklist(valor) {
    if (typeof valor === "boolean") {
        return valor
    }

    if (typeof valor === "number") {
        return valor !== 0
    }

    const texto = String(valor || "").trim().toLowerCase()
    if (!texto) {
        return false
    }

    return ["1", "true", "on", "sim", "yes"].includes(texto)
}

function normalizarItensChecklist(listaItens) {
    const lista = Array.isArray(listaItens) ? listaItens : []

    return lista.map(function (item, indice) {
        const texto = String(item && (item.texto !== undefined ? item.texto : (item.label !== undefined ? item.label : "")) || "").trimEnd()
        const id = String(item && item.id ? item.id : gerarIdLocal("checklist")).trim()
        const ordemBruta = item && item.ordem !== undefined && item.ordem !== null
            ? item.ordem
            : (indice + 1)
        const valorConcluido = item && item.concluido !== undefined && item.concluido !== null
            ? item.concluido
            : (item && item.checked !== undefined && item.checked !== null
                ? item.checked
                : (item && item.completo !== undefined && item.completo !== null
                    ? item.completo
                    : (item && item.done !== undefined && item.done !== null ? item.done : false)))

        return {
            id: id || gerarIdLocal("checklist"),
            texto: texto,
            concluido: normalizarBooleanoChecklist(valorConcluido),
            ordem: Number(ordemBruta) > 0 ? Number(ordemBruta) : (indice + 1),
            criado_por_id: item && item.criado_por_id !== undefined ? item.criado_por_id : null,
            apresentacao: String(item && item.apresentacao ? item.apresentacao : "").trim(),
            criado_em: String(item && item.criado_em ? item.criado_em : obterDataHoraAtualIso()),
            atualizado_em: String(item && item.atualizado_em ? item.atualizado_em : obterDataHoraAtualIso())
        }
    }).sort(function (a, b) {
        const ordemA = Number(a && a.ordem ? a.ordem : 0) || 0
        const ordemB = Number(b && b.ordem ? b.ordem : 0) || 0
        if (ordemA !== ordemB) {
            return ordemA - ordemB
        }

        const criadoEmA = String(a && a.criado_em ? a.criado_em : "")
        const criadoEmB = String(b && b.criado_em ? b.criado_em : "")
        if (criadoEmA !== criadoEmB) {
            return criadoEmA.localeCompare(criadoEmB)
        }

        return String(a && a.id ? a.id : "").localeCompare(String(b && b.id ? b.id : ""))
    })
}

function obterResumoChecklistModal(listaItens = []) {
    const itens = Array.isArray(listaItens) ? listaItens : []
    const total = itens.length
    const concluidos = itens.filter(function (item) {
        return Boolean(item && item.concluido)
    }).length
    const percentual = total > 0 ? Math.round((concluidos / total) * 100) : 0

    return {
        itens: itens,
        total: total,
        concluidos: concluidos,
        percentual: percentual
    }
}

function atualizarResumoChecklistModal(listaItens = []) {
    const resumo = obterResumoChecklistModal(listaItens)
    const $count = $("#modalChecklistCount")
    const $texto = $("#modalChecklistProgressText")
    const $barra = $("#modalChecklistProgressBar")

    if ($count.length) {
        $count.text(`${resumo.concluidos}/${resumo.total}`)
    }

    if ($texto.length) {
        $texto.text(`${resumo.percentual}%`)
    }

    if ($barra.length) {
        $barra
            .css("width", `${resumo.percentual}%`)
            .attr("aria-valuenow", resumo.percentual)
            .removeClass("bg-primary bg-success")
            .addClass(resumo.total > 0 && resumo.percentual === 100 ? "bg-success" : "bg-primary")
    }

    return resumo
}

function renderizarItemChecklistModal(item, podeEditar = usuarioPodeGerenciarChecklistModal()) {
    const itemId = String(item && item.id ? item.id : gerarIdLocal("checklist")).trim()
    const texto = String(item && item.texto !== undefined ? item.texto : "").trimEnd()
    const concluido = Boolean(item && item.concluido)

    return `
        <div class="kanban-checklist-item ${concluido ? "is-complete" : ""}${podeEditar ? "" : " is-readonly"}" data-checklist-item-id="${escaparHtml(itemId)}">
            <input type="checkbox" class="kanban-checklist-item-toggle" ${concluido ? "checked" : ""} ${podeEditar ? "" : "disabled"} aria-label="Marcar item como concluído">
            <input type="text" class="form-control form-control-sm kanban-checklist-item-input" value="${escaparHtml(texto)}" maxlength="220" placeholder="Digite um item" ${podeEditar ? "" : "readonly aria-readonly=\"true\""}>
            ${podeEditar ? `
            <button type="button" class="kanban-checklist-item-remove" data-checklist-item-remove="${escaparHtml(itemId)}" title="Remover item" aria-label="Remover item">
                <i class="fas fa-trash"></i>
            </button>
            ` : ""}
        </div>
    `
}

function renderizarChecklistModal(card) {
    const cardId = obterChaveChecklistModal(card && card.id ? card.id : $("#editCardId").val() || "")
    const $section = $("#modalChecklistSection")
    const $list = $("#modalChecklistList")
    const cardBase = card || obterCardOuRascunhoPorId(cardId)

    if (!$section.length || !$list.length) {
        return
    }

    if (!cardId) {
        $section.toggleClass("d-none", true)
        $list.html('<div class="kanban-empty-state kanban-checklist-empty">Nenhum item no checklist</div>')
        atualizarResumoChecklistModal([])
        return
    }

    const estado = obterChecklistEstadoCard(cardId)
    if (!estado) {
        alternarSecaoChecklistModal(false, false)
        atualizarResumoChecklistModal([])
        return
    }

    const podeVerChecklist = usuarioPodeVerChecklistModal(cardBase)
    const podeGerenciarChecklist = podeVerChecklist && usuarioPodeGerenciarChecklistModal()

    if (!podeVerChecklist) {
        alternarSecaoChecklistModal(false, false)
        atualizarResumoChecklistModal([])
        return
    }

    const itensOriginais = Array.isArray(estado) && estado.length
        ? estado
        : (Array.isArray(cardBase && cardBase.checklist) ? cardBase.checklist : [])
    const itens = sincronizarChecklistEstadoCard(cardId, itensOriginais)

    alternarSecaoChecklistModal(true, podeGerenciarChecklist)
    $list.html(itens.length ? itens.map(function (item) {
        return renderizarItemChecklistModal(item, podeGerenciarChecklist)
    }).join("") : '<div class="kanban-empty-state kanban-checklist-empty">Nenhum item no checklist</div>')
    atualizarResumoChecklistModal(itens)
}

function atualizarTextoChecklistItemModal(cardId, itemId, texto) {
    if (!usuarioPodeGerenciarChecklistModal()) {
        return
    }

    const chaveCard = obterChaveChecklistModal(cardId)
    const chaveItem = String(itemId || "").trim()

    if (!chaveCard || !chaveItem) {
        return
    }

    const itens = obterChecklistEstadoCard(chaveCard)
    if (!itens) {
        return
    }

    const item = itens.find(function (registro) {
        return String(registro && registro.id ? registro.id : "") === chaveItem
    })

    if (!item) {
        return
    }

    const textoNormalizado = String(texto || "").trimEnd()
    if (!$.trim(textoNormalizado)) {
        return
    }

    item.texto = textoNormalizado
    item.atualizado_em = obterDataHoraAtualIso()
    const checklistNormalizado = sincronizarChecklistEstadoCard(chaveCard, itens)
    atualizarResumoChecklistModal(checklistNormalizado)
}

function adicionarItemChecklistModal() {
    if (!usuarioPodeGerenciarChecklistModal()) {
        return
    }

    const cardId = obterChaveChecklistModal()
    const $input = $("#modalChecklistInput")
    const texto = $.trim($input.val() || "")

    if (!cardId || !texto) {
        return
    }

    const itens = Array.isArray(obterChecklistEstadoCard(cardId))
        ? obterChecklistEstadoCard(cardId).slice()
        : []
    if (!itens) {
        return
    }

    const agora = obterDataHoraAtualIso()
    const proximaOrdem = itens.reduce(function (maior, item) {
        const ordemItem = Number(item && item.ordem ? item.ordem : 0) || 0
        return ordemItem > maior ? ordemItem : maior
    }, 0) + 1

    if (modoFormularioCard === "criar") {
        itens.push({
            id: gerarIdLocal("checklist"),
            texto: texto,
            concluido: false,
            ordem: proximaOrdem,
            criado_em: agora,
            atualizado_em: agora
        })

        const checklistNormalizado = sincronizarChecklistEstadoCard(cardId, itens)
        $input.val("")
        renderizarChecklistModal(obterCardOuRascunhoPorId(cardId) || { id: cardId })
        atualizarResumoChecklistModal(checklistNormalizado)
        const cardAtual = obterCardPorId(cardId)
        if (cardAtual) {
            atualizarCardNoDOM(cardAtual)
        }
        window.requestAnimationFrame(function () {
            $input.trigger("focus")
        })
        return
    }

    requestAjax(
        {
            'objeto': "ChamadosChecklist",
            'metodo': "criaChecklistItem",
            'id_chamado': cardId,
            'texto': texto,
            'concluido': 0,
            'ordem': proximaOrdem
        }, function (result) {
            const itemServidor = result && (result.item || result)
            if (!itemServidor) {
                return
            }

            const checklistNormalizado = atualizarChecklistItemNaMemoria(cardId, itemServidor)
            $input.val("")
            renderizarChecklistModal(obterCardOuRascunhoPorId(cardId) || { id: cardId })
            atualizarResumoChecklistModal(checklistNormalizado)
            const cardAtual = obterCardPorId(cardId)
            if (cardAtual) {
                atualizarCardNoDOM(cardAtual)
            }
            window.requestAnimationFrame(function () {
                $input.trigger("focus")
            })
        },
        false
    )
}

function alternarConcluidoChecklistModal(cardId, itemId, concluido) {
    if (!usuarioPodeGerenciarChecklistModal()) {
        return
    }

    const chaveCard = obterChaveChecklistModal(cardId)
    const chaveItem = String(itemId || "").trim()

    if (!chaveCard || !chaveItem) {
        return
    }

    const itens = obterChecklistEstadoCard(chaveCard)
    if (!itens) {
        return
    }

    const item = itens.find(function (registro) {
        return String(registro && registro.id ? registro.id : "") === chaveItem
    })

    if (!item) {
        return
    }

    item.concluido = Boolean(concluido)
    item.atualizado_em = obterDataHoraAtualIso()
    const checklistNormalizado = sincronizarChecklistEstadoCard(chaveCard, itens)
    renderizarChecklistModal(obterCardOuRascunhoPorId(chaveCard) || { id: chaveCard })
    atualizarResumoChecklistModal(checklistNormalizado)
    const cardAtual = obterCardPorId(chaveCard)
    if (cardAtual) {
        atualizarCardNoDOM(cardAtual)
    }

    if (modoFormularioCard === "criar") {
        return
    }

    salvarChecklistItemModal(chaveCard, chaveItem)
}

function removerItemChecklistModal(cardId, itemId) {
    if (!usuarioPodeGerenciarChecklistModal()) {
        return
    }

    const chaveCard = obterChaveChecklistModal(cardId)
    const chaveItem = String(itemId || "").trim()

    if (!chaveCard || !chaveItem) {
        return
    }

    const itens = obterChecklistEstadoCard(chaveCard)
    if (!itens) {
        return
    }

    const itensFiltrados = itens.filter(function (registro) {
        return String(registro && registro.id ? registro.id : "") !== chaveItem
    })

    const checklistNormalizado = sincronizarChecklistEstadoCard(chaveCard, itensFiltrados)
    renderizarChecklistModal(obterCardOuRascunhoPorId(chaveCard) || { id: chaveCard })
    atualizarResumoChecklistModal(checklistNormalizado)
    const cardAtual = obterCardPorId(chaveCard)
    if (cardAtual) {
        atualizarCardNoDOM(cardAtual)
    }

    if (modoFormularioCard === "criar") {
        return
    }

    requestAjax(
        {
            'objeto': "ChamadosChecklist",
            'metodo': "deletaChecklistItem",
            'id': chaveItem
        }, function () {
            sincronizarChecklistEstadoCard(chaveCard, itensFiltrados)
            renderizarChecklistModal(obterCardOuRascunhoPorId(chaveCard) || { id: chaveCard })
            atualizarResumoChecklistModal(checklistNormalizado)
            const cardAtual = obterCardPorId(chaveCard)
            if (cardAtual) {
                atualizarCardNoDOM(cardAtual)
            }
        },
        false
    )
}

function salvarChecklistItemModal(cardId, itemId) {
    if (modoFormularioCard === "criar") {
        return
    }

    const chaveCard = String(cardId || "").trim()
    const chaveItem = String(itemId || "").trim()

    if (!chaveCard || !chaveItem) {
        return
    }

    const itens = obterChecklistEstadoCard(chaveCard)
    if (!itens) {
        return
    }

    const item = itens.find(function (registro) {
        return String(registro && registro.id ? registro.id : "") === chaveItem
    })

    if (!item) {
        return
    }

    if (!$.trim(item.texto || "")) {
        return
    }

    requestAjax(
        {
            'objeto': "ChamadosChecklist",
            'metodo': "atualizaChecklistItem",
            'id': chaveItem,
            'id_chamado': chaveCard,
            'texto': item.texto,
            'concluido': item.concluido ? 1 : 0,
            'ordem': item.ordem
        }, function (result) {
            const itemServidor = result && (result.item || result)
            if (itemServidor) {
                atualizarChecklistItemNaMemoria(chaveCard, itemServidor)
            }
            renderizarChecklistModal(obterCardOuRascunhoPorId(chaveCard) || { id: chaveCard })
        },
        false
    )
}

function exibirAvisoKanban(tipo, mensagem) {
    if (window.toastr && typeof window.toastr[tipo] === "function") {
        window.toastr[tipo](mensagem)
    }
}

function calcularProximoNumeroId() {
    let maior = 0

    Object.keys(indiceCards).forEach(function (id) {
        const match = String(id).match(/(\d+)/)
        if (match && Number(match[1]) > maior) {
            maior = Number(match[1])
        }
    })

    return maior + 1
}

function gerarNovoIdCard() {
    const novoId = `T-${proximoNumeroId}`
    proximoNumeroId += 1
    return novoId
}

function normalizarUsuarioObservador(usuario) {
    if (!usuario || typeof usuario !== "object") {
        return null
    }

    const apresentacao = String(usuario.apresentacao || usuario.nome || "").trim()
    if (!apresentacao) {
        return null
    }

    const chaveNome = normalizarTextoPesquisa(apresentacao)
    let idBruto = usuario.id_usuario

    if (idBruto === undefined || idBruto === null || idBruto === "") {
        idBruto = usuario.id
    }

    if ((idBruto === undefined || idBruto === null || idBruto === "") && chaveNome && indiceObservadoresPorNome[chaveNome]) {
        idBruto = indiceObservadoresPorNome[chaveNome].id
    }

    const idNumerico = Number(idBruto)
    const id = Number.isFinite(idNumerico) && idNumerico > 0 ? idNumerico : null

    return Object.assign({}, usuario, {
        id: id,
        id_usuario: id,
        apresentacao: apresentacao,
        nome_setor: String(usuario.nome_setor || "").trim(),
        nome_cargo: String(usuario.nome_cargo || usuario.cargo || "").trim()
    })
}

function normalizarListaUsuarios(listaUsuarios) {
    const lista = Array.isArray(listaUsuarios) ? listaUsuarios : []
    const chavesVistas = new Set()

    return lista
        .map(function (observador) {
            return normalizarUsuarioObservador(observador)
        })
        .filter(function (observador) {
            if (!observador || !observador.apresentacao) {
                return false
            }

            const chave = observador.id
                ? `id:${observador.id}`
                : `nome:${normalizarTextoPesquisa(observador.apresentacao)}`

            if (chavesVistas.has(chave)) {
                return false
            }

            chavesVistas.add(chave)
            return true
        })
}

function inicializarSelectObservadores() {
    const $container = $("#editCardObservadores")
    if (!$container.length) {
        return
    }

    $container.data("selecionados", [])
    popularSelectObservadores([])
}

function renderizarListaObservadoresSelecionados(observadoresSelecionados = []) {
    const $container = $("#editCardObservadores")
    if (!$container.length) {
        return
    }

    const selecionados = normalizarListaUsuarios(observadoresSelecionados)
    $container.data("selecionados", selecionados)
    $container.empty()

    if (!selecionados.length) {
        $container.append('<span class="kanban-observador-vazio text-muted">Nenhum observador adicionado</span>')
        return
    }

    selecionados.forEach(function (observador) {
        const observadorId = Number(observador.id)
        const temIdValido = Number.isFinite(observadorId) && observadorId > 0
        let chip = `<span class="kanban-observador-chip">
                <span class="kanban-observador-chip-text">${escaparHtml(observador.apresentacao)}</span>`

        if (obterAtributosEditaveisModal().observadores && temIdValido) {
            chip += `<button type="button" class="kanban-observador-chip-remove" data-observador="${observadorId}" aria-label="Remover ${escaparHtml(observador.apresentacao)}">&times;</button>`
        }

        chip += `<input type="hidden" name="observadores[]" value="${temIdValido ? escaparHtml(observadorId) : ""}">
            </span>`

        $container.append(chip)
    })
}

function ocultarBuscaObservadores() {
    $("#editCardObservadoresPesquisa").addClass("d-none")
    $("#editCardObservadoresSkeleton").addClass("d-none")
}

function limparBuscaObservadores() {
    ocultarBuscaObservadores()
    $("#editCardObservadoresPesquisa").empty()
}

function marcarResultadosBuscaObservadores() {
    const selecionadosIds = new Set(obterIdsObservadoresSelecionadosModal())

    $("#editCardObservadoresPesquisa li[user-id]").each(function () {
        const id = Number($(this).data("id"))
        $(this).toggleClass("added", selecionadosIds.has(id))
    })
}

function renderizarResultadosBuscaObservadores(listaUsuarios = []) {
    const $lista = $("#editCardObservadoresPesquisa")
    if (!$lista.length) {
        return
    }

    const usuarios = Array.isArray(listaUsuarios) ? listaUsuarios : []
    registrarUsuariosObservadores(usuarios)
    $("#editCardObservadoresSkeleton").addClass("d-none")
    $lista.empty()
    $lista.removeClass("d-none")

    if (!usuarios.length) {
        $lista.html('<li class="list-group-item small text-muted">Usuário não encontrado.</li>')
        return
    }

    let html = ""

    usuarios.forEach(function (item) {
        const usuario = normalizarUsuarioObservador(item)
        const id = usuario && usuario.id ? usuario.id : ""
        const nome = String(usuario && usuario.apresentacao ? usuario.apresentacao : "").trim()
        const setor = String(usuario && usuario.nome_setor ? usuario.nome_setor : "").trim()

        if (!nome) {
            return
        }

        html += `
            <li user-id="${escaparHtml(id)}" data-id="${escaparHtml(id)}" data-apresentacao="${escaparHtml(nome)}" data-setor="${escaparHtml(setor)}" role="button" class="list-group-item d-flex justify-content-between align-items-center px-2 position-relative">
                <span>${escaparHtml(nome)}${setor ? ` <small class="text-muted">(${escaparHtml(setor)})</small>` : ""}</span>
                <div class="icon"><i class="fas fa-check"></i></div>
            </li>
        `
    })

    $lista.html(html || '<li class="list-group-item small text-muted">Usuário não encontrado.</li>')
    marcarResultadosBuscaObservadores()
}

function buscarObservadoresPorTermo(termoBusca) {
    const termo = String(termoBusca || "").trim()

    window.clearTimeout(debounceBuscaObservadores)

    if (!termo) {
        limparBuscaObservadores()
        return
    }

    $("#editCardObservadoresSkeleton").removeClass("d-none")
    $("#editCardObservadoresPesquisa").addClass("d-none").empty()

    debounceBuscaObservadores = window.setTimeout(function () {
        requestAjax(
            {
                objeto: "Chamados",
                metodo: "pesquisarUsuariosObservadores",
                termo: termo
            },
            function (result) {
                const termoAtual = String($("#editCardObservadoresBusca").val() || "").trim()
                if (termoAtual !== termo) {
                    return
                }

                renderizarResultadosBuscaObservadores(result)
            },
            false
        )
    }, 350)
}

function encontrarObservadorDisponivel(id) {
    const valorBruto = String(id || "").trim()
    if (!valorBruto) {
        return null
    }

    const idNumerico = Number(valorBruto)
    if (Number.isFinite(idNumerico) && idNumerico > 0 && indiceObservadoresPorId[String(idNumerico)]) {
        return Object.assign({}, indiceObservadoresPorId[String(idNumerico)])
    }

    const itensResultado = $("#editCardObservadoresPesquisa li[user-id]").map(function () {
        return normalizarUsuarioObservador({
            id: $(this).data("id"),
            apresentacao: $(this).data("apresentacao"),
            nome_setor: $(this).data("setor")
        })
    }).get()

    const correspondenciaPorId = Number.isFinite(idNumerico) && idNumerico > 0
        ? itensResultado.find(function (item) {
            return item && item.id === idNumerico
        })
        : null

    if (correspondenciaPorId) {
        return correspondenciaPorId
    }

    const valorNormalizado = normalizarTextoPesquisa(valorBruto)
    if (!valorNormalizado) {
        return null
    }

    const correspondenciaExata = itensResultado.find(function (item) {
        return item && normalizarTextoPesquisa(item.apresentacao) === valorNormalizado
    })

    if (correspondenciaExata) {
        return correspondenciaExata
    }

    if (indiceObservadoresPorNome[valorNormalizado]) {
        return Object.assign({}, indiceObservadoresPorNome[valorNormalizado])
    }

    const candidatos = itensResultado.filter(function (item) {
        return item && normalizarTextoPesquisa(item.apresentacao).indexOf(valorNormalizado) !== -1
    })

    if (candidatos.length === 1) {
        return candidatos[0]
    }

    return null
}

function adicionarObservadorModal(id) {
    const observador = encontrarObservadorDisponivel(id)
    if (!observador) {
        return false
    }

    const selecionados = obterObservadoresSelecionadosModal()
    const selecionados_ids = selecionados.map(item => item.id)

    if (selecionados_ids.includes(observador.id)) {
        marcarResultadosBuscaObservadores()
        return false
    }

    selecionados.push(observador)
    popularSelectObservadores(selecionados)
    atualizarMencionaveisComentarioDoModal()
    marcarResultadosBuscaObservadores()
    return true
}

function removerObservadorModal(id) {
    const observador = Number(id || "")
    if (!observador) {
        return
    }

    const selecionados = obterObservadoresSelecionadosModal().filter(function (item) {
        return item.id !== observador
    })

    popularSelectObservadores(selecionados)
    atualizarMencionaveisComentarioDoModal()
    marcarResultadosBuscaObservadores()
}

function alternarObservadorPorItemPesquisa($item) {
    const id = String($item.data("id") || "").trim()
    if (!id) {
        return
    }

    if ($item.hasClass("added")) {
        removerObservadorModal(id)
    } else {
        adicionarObservadorModal(id)
    }

    marcarResultadosBuscaObservadores()
}

function popularSelectObservadores(observadoresSelecionados = []) {
    const $container = $("#editCardObservadores")
    if (!$container.length) {
        return
    }

    const selecionados = normalizarListaUsuarios(observadoresSelecionados)
    registrarUsuariosObservadores(selecionados)
    renderizarListaObservadoresSelecionados(selecionados)
    marcarResultadosBuscaObservadores()
}

function obterObservadoresSelecionadosModal() {
    let selecionados = $("#editCardObservadores").data("selecionados") || []
    selecionados = normalizarListaUsuarios(selecionados)
    return selecionados
}

function obterIdsObservadoresSelecionadosModal() {
    return Array.from(new Set(obterObservadoresSelecionadosModal()
        .map(function (nome) {
            let id = Number(nome.id)
            return id
        })
        .filter(function (id) {
            return Number.isFinite(id) && id > 0
        })))
}

function filtrarOpcoesObservadores(termoBusca) {
    buscarObservadoresPorTermo(termoBusca)
}

function renderizarBadgesObservadores(card) {
    const observadores = card && Array.isArray(card.observadores) ? card.observadores : []
    if (!observadores.length) {
        return '<span class="text-muted">Sem observadores</span>'
    }

    return observadores
        .map(function (nome) {
            return `<span class="badge badge-light border kanban-observador-badge">${escaparHtml(nome.apresentacao)}</span>`
        })
        .join(" ")
}

function registrarUsuariosObservadores(listaUsuarios = []) {
    const lista = normalizarListaUsuarios(listaUsuarios)

    lista.forEach(function (item) {
        const nome = String(item && item.apresentacao ? item.apresentacao : "").trim()
        const chaveNome = normalizarTextoPesquisa(nome)
        const id = Number(item && item.id)

        if (!nome || !chaveNome || !Number.isFinite(id) || id <= 0) {
            return
        }

        const usuarioNormalizado = Object.assign({}, item, { id: id, id_usuario: id, apresentacao: nome })
        indiceObservadoresPorNome[chaveNome] = usuarioNormalizado
        indiceObservadoresPorId[String(id)] = usuarioNormalizado
    })
}

function mapearObservadoresPadrao(listaUsuarios = []) {
    const lista = Array.isArray(listaUsuarios) ? listaUsuarios : []
    registrarUsuariosObservadores(lista)

    return normalizarListaUsuarios(lista.map(function (item) {
        if (item && item.apresentacao) {
            return item
        }
        return ""
    }))
}



function criarEstruturaDadosKanbanVazia() {
    return colunasKanban.reduce(function (estrutura, coluna) {
        estrutura[coluna] = []
        return estrutura
    }, {})
}

function obterValorTextoRequest(item, campos, fallback = "") {
    const chamado = item && typeof item === "object" ? item : {}

    for (let i = 0; i < campos.length; i += 1) {
        const valor = chamado[campos[i]]
        if (valor !== null && valor !== undefined && String(valor).trim() !== "") {
            return String(valor).trim()
        }
    }

    return fallback
}

function montarDadosKanbanDaRequest(listaChamados = []) {
    const agrupado = criarEstruturaDadosKanbanVazia()
    const lista = Array.isArray(listaChamados) ? listaChamados : []

    lista.forEach(function (item) {
        const chamado = item && typeof item === "object" ? item : {}
        const coluna = colunasKanban.includes(chamado.coluna) ? chamado.coluna : COLUNA_PADRAO

        agrupado[coluna].push({
            id: String(chamado.id || ""),
            titulo: chamado.titulo || "",
            descricao: chamado.descricao || "",
            proximo_retorno: chamado.proximo_retorno || "",
            responsavel_id: chamado.responsavel_id,
            responsavel: chamado.responsavel,
            modulo: chamado.modulo,
            observadores: Array.isArray(chamado.observadores) ? chamado.observadores : [],
            coluna: coluna,
            tipo_chamado: chamado.tipo_chamado,
            posicao: Number(chamado.posicao) || 0,
            ultima_coluna: chamado.ultima_coluna || (coluna === COLUNA_ARQUIVADOS ? COLUNA_PADRAO : coluna),
            atualizacoes: Array.isArray(chamado.atualizacoes) ? chamado.atualizacoes : [],
            comentarios: Array.isArray(chamado.comentarios) ? chamado.comentarios : [],
            arquivos: Array.isArray(chamado.arquivos) ? chamado.arquivos : [],
            checklist: Array.isArray(chamado.checklist) ? chamado.checklist : [],
            criado_por_id: chamado.criado_por_id || "",
            criado_por: obterValorTextoRequest(chamado, ["criado_por", "criado_por_nome", "criado_por_id"], "Sistema"),
            criado_em: chamado.criado_em || "",
            arquivado: coluna === COLUNA_ARQUIVADOS
        })
    })

    return agrupado
}

function preencherDadosKanban(listaChamados = []) {
    const agrupado = montarDadosKanbanDaRequest(listaChamados)

    colunasKanban.forEach(function (coluna) {
        dadosKanban[coluna] = agrupado[coluna]
    })

    inicializarIndiceCards()
}

function inicializarIndiceCards() {
    Object.keys(indiceCards).forEach(function (id) {
        delete indiceCards[id]
    })

    colunasKanban.forEach(function (coluna) {
        const lista = Array.isArray(dadosKanban[coluna]) ? dadosKanban[coluna] : []

        dadosKanban[coluna] = lista.map(function (card) {
            const atualizacoesNormalizadas = normalizarAtualizacoes(card.atualizacoes)
            const estadoComplementar = estadoComplementarCards[String(card.id || "")] || null
            const criado_emOriginal = card && card.criado_em ? card.criado_em : (atualizacoesNormalizadas[0] && atualizacoesNormalizadas[0].criado_em ? atualizacoesNormalizadas[0].criado_em : obterDataHoraAtualIso())
            const criado_porOriginal = card && card.criado_por ? card.criado_por : (card && card.responsavel ? card.responsavel : "Sistema")

            const normalizado = {
                id: String(card.id || ""),
                proximo_retorno: card.proximo_retorno || "",
                titulo: card.titulo || "",
                descricao: card.descricao || "",
                tipo_chamado: card.tipo_chamado || "",
                modulo: card.modulo || "",
                classificacao: card.classificacao || "",
                responsavel_id: card.responsavel_id || "",
                responsavel: card.responsavel || "",
                observadores: normalizarListaUsuarios(card.observadores),
                coluna: coluna,
                posicao: Number(card.posicao) || 0,
                ultima_coluna: card.ultima_coluna || (coluna === COLUNA_ARQUIVADOS ? COLUNA_PADRAO : coluna),
                atualizacoes: atualizacoesNormalizadas,
                comentarios: normalizarComentarios(estadoComplementar && estadoComplementar.comentarios ? estadoComplementar.comentarios : card.comentarios),
                arquivos: normalizarArquivos(estadoComplementar && estadoComplementar.arquivos ? estadoComplementar.arquivos : card.arquivos),
                checklist: normalizarItensChecklist(estadoComplementar && estadoComplementar.checklist ? estadoComplementar.checklist : card.checklist),
                criado_por: String(criado_porOriginal || "Sistema"),
                criado_em: criado_emOriginal,
                arquivado: coluna === COLUNA_ARQUIVADOS
            }

            if (normalizado.coluna !== COLUNA_ARQUIVADOS) {
                normalizado.ultima_coluna = normalizado.coluna
            } else if (normalizado.ultima_coluna === COLUNA_ARQUIVADOS) {
                normalizado.ultima_coluna = COLUNA_PADRAO
            }

            indiceCards[normalizado.id] = normalizado
            return normalizado
        })
    })

    proximoNumeroId = calcularProximoNumeroId()
}

function renderizarLinhasAtualizacoes(card) {
    const atualizacoes = card && Array.isArray(card.atualizacoes) ? card.atualizacoes : []

    if (!atualizacoes.length) {
        return `<tr><td class="kanban-updates-empty" colspan="3">Sem atualizações</td></tr>`
    }

    return atualizacoes
        .map(function (item) {
            return `
                <tr>
                    <td>${renderizarDescricaoAtualizacao(item.descricao)}</td>
                    <td>${escaparHtml(item.apresentacao)}</td>
                    <td>${escaparHtml(formatarDataHoraBr(item.criado_em))}</td>
                </tr>
            `
        })
        .join("")
}

function renderizarAtualizacoesModal(card) {
    $("#modalCardUpdatesBody").html(renderizarLinhasAtualizacoes(card))
}

function limitarTexto(texto, limite) {
    return String(texto || "").slice(0, Math.max(0, Number(limite) || 0))
}

function truncarTextoComReticencias(texto, limite) {
    const conteudo = String(texto || "")
    const limiteNumerico = Math.max(0, Number(limite) || 0)

    if (!limiteNumerico || conteudo.trim().length <= limiteNumerico) {
        return conteudo
    }

    return `${conteudo.slice(0, Math.max(0, limiteNumerico - 3)).trimEnd()}...`
}

function codificarTextoExpandivel(texto) {
    return encodeURIComponent(String(texto || ""))
}

function decodificarTextoExpandivel(textoCodificado) {
    try {
        return decodeURIComponent(String(textoCodificado || ""))
    } catch (erro) {
        return String(textoCodificado || "")
    }
}

function renderizarTextoExpandivel(texto, opcoes = {}) {
    const conteudo = String(texto || "")
    const limite = Number(opcoes.limite) || 0
    const classeCorpo = String(opcoes.classeCorpo || "").trim()
    const usarResumoNoRecolhido = Boolean(opcoes.usarResumoNoRecolhido)
    const renderizador = typeof opcoes.renderizador === "function" ? opcoes.renderizador : null
    const temIndicadorExpandir = Boolean(opcoes.temIndicadorExpandir)
    const contextoMarcacoes = Array.isArray(opcoes.contextoMarcacoes) ? opcoes.contextoMarcacoes : []
    const precisaExpansao = conteudo.trim().length > limite
    const textoResumido = truncarTextoComReticencias(conteudo, limite)
    const textoInicial = usarResumoNoRecolhido ? textoResumido : conteudo
    const textoEscapado = renderizador ? renderizador(textoInicial) : escaparHtml(textoInicial)
    const atributoMarcacoes = contextoMarcacoes.length
        ? ` data-expand-mention-names="${escapeHtml(codificarTextoExpandivel(JSON.stringify(contextoMarcacoes)))}"`
        : ""

    if (!classeCorpo) {
        return textoEscapado
    }

    if (!precisaExpansao) {
        return `<div class="${classeCorpo}"${atributoMarcacoes}>${textoEscapado}</div>`
    }

    return `
        <div class="kanban-expandable-content">
            <div
                class="${classeCorpo} kanban-expandable-body is-collapsed"
                data-expand-full="${escapeHtml(codificarTextoExpandivel(conteudo))}"
                data-expand-preview="${escapeHtml(codificarTextoExpandivel(textoResumido))}"
                data-expand-mention-indicator="${temIndicadorExpandir ? "1" : "0"}"
                ${atributoMarcacoes ? atributoMarcacoes.trimStart() : ""}
            >${textoEscapado}</div>
            ${renderizarBotaoExpandivelComentario(false, temIndicadorExpandir)}
        </div>
    `
}

function obterNomesMarcacoesComentarioDoCard(card = {}) {
    const nomes = []

    const adicionarNome = function (valor) {
        const nome = String(valor || "").trim()
        if (nome) {
            nomes.push(nome)
        }
    }

    adicionarNome(card && card.criado_por ? card.criado_por : "")
    adicionarNome(card && card.responsavel ? card.responsavel : "")

    const observadores = card && Array.isArray(card.observadores) ? card.observadores : []
    observadores.forEach(function (observador) {
        adicionarNome(observador && (observador.apresentacao || observador.nome || observador.nome_usuario || observador.nome_usuario_completo))
    })

    obterMencionaveisComentarioDisponiveis().forEach(function (mencionavel) {
        adicionarNome(mencionavel && (mencionavel.apresentacao || mencionavel.nome || mencionavel.nome_usuario || mencionavel.nome_usuario_completo))
    })

    return nomes
        .filter(function (nome) {
            return nome.length > 0
        })
        .filter(function (nome, indice, lista) {
            return lista.indexOf(nome) === indice
        })
        .filter(function (nome) {
            return nome !== "@"
        })
        .sort(function (a, b) {
            return b.length - a.length
        })
}

function obterNomesMarcacoesComentarioDoElemento($elemento) {
    if (!$elemento || !$elemento.length) {
        return []
    }

    const nomesCodificados = decodificarTextoExpandivel($elemento.attr("data-expand-mention-names"))
    if (!nomesCodificados) {
        return []
    }

    try {
        const nomes = JSON.parse(nomesCodificados)
        return Array.isArray(nomes)
            ? nomes
                .map(function (nome) {
                    return String(nome || "").trim()
                })
                .filter(function (nome) {
                    return nome.length > 0
                })
            : []
    } catch (erro) {
        return []
    }
}

function obterRegexMarcacoesComentario_legacy(nomesMarcacoes = []) {
    void nomesMarcacoes

    const conectoresMenorCase = "(?:de|da|do|das|dos|e|del|van|von|di|du|la|le)"
    const palavraMarcacao = "[A-ZÀ-ÖØ-Ý0-9][A-Za-zÀ-ÖØ-öø-ÿ0-9'’._-]*"
    const trechoMarcacao = `${palavraMarcacao}(?:\\s+(?:${palavraMarcacao}|${conectoresMenorCase}))*`

    return new RegExp(`(^|\\s)@(${trechoMarcacao})(?=\\s|$|[.,;:!?])`, "gi")
}

function obterRegexMarcacoesComentario(nomesMarcacoes = []) {
    const listaNomes = Array.isArray(nomesMarcacoes) ? nomesMarcacoes : []
    const nomesValidos = listaNomes
        .map(function (nome) {
            return String(nome || "").trim()
        })
        .filter(function (nome) {
            return nome.length > 0
        })
        .sort(function (a, b) {
            return b.length - a.length
        })

    if (!nomesValidos.length) {
        return null
    }

    return new RegExp(`(^|\\s)@(${nomesValidos.map(escaparRegexMarcacao).join("|")})(?=\\s|$|[.,;:!?])`, "gi")
}

function renderizarBotaoExpandivelComentario(vaiExpandir, temIndicadorExpandir = false) {
    const rotulo = vaiExpandir ? "Ver menos" : "Ver mais"
    const indicador = !vaiExpandir && temIndicadorExpandir
        ? `<span class="kanban-comment-expand-indicator" title="Há menção oculta" aria-hidden="true">@</span>`
        : ""

    return `
        <button type="button" class="kanban-expand-toggle" aria-expanded="${vaiExpandir ? "true" : "false"}">
            <span class="kanban-expand-toggle-label">${rotulo}</span>
            ${indicador}
        </button>
    `
}

function comentarioTemMarcacaoOculta(textoComentario, nomesMarcacoes = []) {
    const conteudo = String(textoComentario || "")
    if (!conteudo || conteudo.trim().length <= LIMITE_EXPANSAO_COMENTARIO) {
        return false
    }

    const textoResumido = truncarTextoComReticencias(conteudo, LIMITE_EXPANSAO_COMENTARIO)
    const regexMarcacoes = obterRegexMarcacoesComentario(nomesMarcacoes)
    if (!regexMarcacoes) {
        return false
    }

    let temMarcacaoOculta = false
    conteudo.replace(regexMarcacoes, function (match, prefixo, nome, indice) {
        const fimMarcacao = indice + match.length
        if (fimMarcacao > textoResumido.length) {
            temMarcacaoOculta = true
        }
        return match
    })

    return temMarcacaoOculta
}

function renderizarTextoComentarioComMarcacoes(textoComentario, nomesMarcacoes = []) {
    const conteudo = String(textoComentario || "")
    if (!conteudo) {
        return ""
    }

    const regexMarcacoes = obterRegexMarcacoesComentario(nomesMarcacoes)
    if (!regexMarcacoes) {
        return escaparHtml(conteudo)
    }

    let html = ""
    let indiceAnterior = 0

    conteudo.replace(regexMarcacoes, function (match, prefixo, nome, indice) {
        html += escaparHtml(conteudo.slice(indiceAnterior, indice))
        html += escaparHtml(prefixo)
        html += `<span class="kanban-comment-mention-text">${escaparHtml(`@${nome}`)}</span>`
        indiceAnterior = indice + match.length
        return match
    })

    html += escaparHtml(conteudo.slice(indiceAnterior))
    return html
}

function renderizarCorpoComentario(textoComentario, nomesMarcacoes = []) {
    const temMarcacaoOculta = comentarioTemMarcacaoOculta(textoComentario, nomesMarcacoes)
    return renderizarTextoExpandivel(textoComentario, {
        limite: LIMITE_EXPANSAO_COMENTARIO,
        classeCorpo: "kanban-comment-body",
        renderizador: function (texto) {
            return renderizarTextoComentarioComMarcacoes(texto, nomesMarcacoes)
        },
        temIndicadorExpandir: temMarcacaoOculta,
        contextoMarcacoes: nomesMarcacoes
    })
}

function renderizarDescricaoAtualizacao(textoAtualizacao) {
    return renderizarTextoExpandivel(textoAtualizacao, {
        limite: LIMITE_EXPANSAO_ATUALIZACAO,
        classeCorpo: "kanban-update-description",
        usarResumoNoRecolhido: true
    })
}

function renderizarComentariosModal(card) {
    const comentarios = card && Array.isArray(card.comentarios) ? normalizarComentarios(card.comentarios) : []
    const cardArquivado = cardEstaArquivado(card)
    const nomesMarcacoes = obterNomesMarcacoesComentarioDoCard(card)

    $("#modalCommentsCount").text(comentarios.length)

    if (!comentarios.length) {
        $("#modalCardCommentsList").html('<div class="kanban-empty-state">Nenhum comentário</div>')
        return
    }

    $("#modalCardCommentsList").html(comentarios.map(function (item) {
        const podeExcluir = !cardArquivado && USUARIO_ATUAL_ID.length > 0 && String(item.criado_por_id || "").trim() === USUARIO_ATUAL_ID
        return `
            <article class="kanban-comment-item">
                <div class="kanban-comment-item-header">
                    <span class="kanban-comment-author">${escaparHtml(item.apresentacao)}</span>
                    <div class="kanban-comment-item-meta">
                        <span class="kanban-comment-date">${escaparHtml(formatarDataHoraBr(item.criado_em))}</span>
                        ${podeExcluir ? `<button type="button" class="kanban-comment-delete" data-id="${escaparHtml(item.id)}">Excluir</button>` : ""}
                    </div>
                </div>
                <div class="kanban-comment-content">
                    ${renderizarCorpoComentario(item.comentario, nomesMarcacoes)}
                </div>
            </article>
        `
    }).join(""))
}

function renderizarArquivosModal(card) {
    const arquivos = card && Array.isArray(card.arquivos) ? normalizarArquivos(card.arquivos) : []
    const cardArquivado = cardEstaArquivado(card)

    $("#modalAttachmentsCount").text(arquivos.length)

    if (!arquivos.length) {
        $("#modalCardAttachmentsList").html('<div class="kanban-empty-state">Nenhum arquivo anexado</div>')
        return
    }

    $("#modalCardAttachmentsList").html(arquivos.map(function (item) {
        const visual = obterTipoVisualAnexo(item)
        const podeVisualizar = Boolean(visual.previewavel)
        const podeExcluir = !cardArquivado && USUARIO_ATUAL_ID.length > 0 && String(item.criado_por_id || "").trim() === USUARIO_ATUAL_ID
        const urlVisualizacao = podeVisualizar ? obterUrlAnexoChamado(item, true) : ""
        const urlDownload = obterUrlAnexoChamado(item, false)
        const tipoVisualizacao = visual.tipoPreview || "iframe"

        return `
            <div class="kanban-attachment-item">
                <div class="kanban-attachment-icon ${escaparHtml(visual.classe)}">
                    <i class="${escaparHtml(visual.icone)}"></i>
                </div>
                <div class="kanban-attachment-content">
                    <span class="kanban-attachment-name" title="${escaparHtml(item.nome)}">${escaparHtml(item.nome)}</span>
                    <div class="kanban-attachment-meta">
                        <span class="kanban-attachment-type">${escaparHtml(visual.rotulo)}</span>
                        <span>${escaparHtml(formatarTamanhoArquivo(item.tamanho))}</span>
                        <span>${escaparHtml(formatarDataHoraBr(item.criado_em))}</span>
                    </div>
                    <div class="kanban-attachment-actions">
                        ${urlVisualizacao ? `<a class="btn btn-sm btn-primary kanban-attachment-action kanban-attachment-action-preview" href="${escaparHtml(urlVisualizacao)}" target="_blank" rel="noopener" data-preview-type="${escaparHtml(tipoVisualizacao)}" data-preview-name="${escaparHtml(item.nome)}"><i class="bi bi-eye"></i></a>` : ""}
                        ${urlDownload ? `<a class="btn btn-sm btn-info kanban-attachment-action kanban-attachment-action-download" href="${escaparHtml(urlDownload)}" target="_blank" rel="noopener"><i class="bi bi-download"></i></a>` : ""}
                        ${podeExcluir ? `<button type="button" class="btn btn-sm btn-danger kanban-attachment-action kanban-attachment-action-delete" data-id="${escaparHtml(item.id)}"><i class="bi bi-trash"></i></button>` : ""}
                    </div>
                </div>
            </div>
        `
    }).join(""))
}

function atualizarMetaCriacaoModal(card) {
    if (!card) {
        $("#editCardcriado_por").text("-")
        $("#editCardcriado_em").text("-")
        return
    }

    $("#editCardcriado_por").text(card.criado_por || "Sistema")
    $("#editCardcriado_em").text(formatarDataHoraBr(card.criado_em))
}

function alternarAtualizacoesModal(habilitado) {
    $("#modalUpdateInput").prop("disabled", !habilitado)
    $("#modalAddUpdateBtn").prop("disabled", !habilitado)
}

function alternarComentariosModal(habilitado) {
    const editor = obterEditorComentarioModal()
    if (editor) {
        editor.setAttribute("contenteditable", habilitado ? "true" : "false")
        editor.setAttribute("aria-disabled", habilitado ? "false" : "true")
        editor.setAttribute("tabindex", habilitado ? "0" : "-1")
        $(editor).toggleClass("is-disabled", !habilitado)
    }

    sincronizarBotaoEnviarComentarioModal()

    if (!habilitado) {
        ocultarPainelMarcacaoComentario()
    }
}

function alternarSecaoAtualizacoesModal(habilitado) {
    $("#modalUpdatesSection").toggleClass("d-none", !habilitado)
}

function alternarSecaoChecklistModal(habilitado, podeGerenciar = habilitado) {
    const habilitarComposer = Boolean(habilitado) && Boolean(podeGerenciar)
    const $subtitulo = $("#modalChecklistSubtitle")
    $("#modalChecklistSection").toggleClass("d-none", !habilitado)
    $("#modalChecklistInput").prop("disabled", !habilitarComposer)
    $("#modalChecklistAddBtn").prop("disabled", !habilitarComposer)
    $(".kanban-checklist-composer").toggleClass("d-none", !habilitarComposer)

    if ($subtitulo.length && habilitado) {
        $subtitulo.text(habilitarComposer
            ? "Gerencie o andamento interno do chamado sem sair do card."
            : "Acompanhe o andamento interno do chamado.")
    }
}

function alternarSecaoComentariosModal(habilitado) {
    $("#modalCommentsColumn").toggleClass("d-none", !habilitado)
    $("#modalAttachmentsColumn")
        .toggleClass("col-lg-5", habilitado)
        .toggleClass("col-lg-12", !habilitado)
}

function alternarSecaoArquivadoModal(habilitado) {
    $("#modalArquivadoSection").toggleClass("d-none", !habilitado)
}

function alternarTituloModal(habilitado) {
    $("#editCardTitulo").prop("disabled", !habilitado)
}

function alternarCamposPlanejamentoModal(exibirProximoRetorno, exibirResponsavel) {
    $("#editCardproximo_retorno").closest(".form-group").toggleClass("d-none", !exibirProximoRetorno)
    $("#editCardResponsavel").closest(".form-group").toggleClass("d-none", !exibirResponsavel)
}

function obterValorTipoChamadoModal() {
    const $campoTipoPrincipal = $("#editCardTipoChamado").first()
    const valorPrincipal = String($campoTipoPrincipal.val() || "").trim().toLowerCase()

    if (valorPrincipal === "desenvolvimento" || valorPrincipal === "suporte") {
        return valorPrincipal
    }

    const $campoTipoLegado = $('select[name="tipo_chamado"]').first()
    const valorLegado = String($campoTipoLegado.val() || "").trim().toLowerCase()

    if (valorLegado === "desenvolvimento" || valorLegado === "suporte") {
        return valorLegado
    }

    return ""
}

function obterValorModuloChamadoModal() {
    const tipoChamado = obterValorTipoChamadoModal()

    if (tipoChamado === "suporte") {
        return $("#editCardSuporteClassificacao").val() || ""
    }

    return $("#editCardDesenvolvimentoClassificacao").val() || ""
}

function obterValorClassificacaoChamadoModal() {
    let checked = $('input[name="classificacao"]:checked').val() || ""
    return checked
}

function preencherClassificacaoChamadoModal(classificacao = "") {
    const valor = String(classificacao || "").trim()
    const $opcoes = $('input[name="classificacao"]')

    $opcoes.prop("checked", false)

    if (!valor) {
        return
    }

    $opcoes.filter(`[value="${valor}"]`).prop("checked", true)
}

function sincronizarSelect2CampoModal($campo) {
    if (
        !$campo
        || !$campo.length
        || typeof $campo.select2 !== "function"
        || !$campo.data("select2")
    ) {
        return
    }

    $campo.trigger("change.select2")
}

function alternarClassificacaoComplementarModal() {
    const atributos = obterAtributosEditaveisModal()
    const habilitado = Boolean(atributos.classificacao) && obterValorTipoChamadoModal() === "desenvolvimento"
    $('input[name="classificacao"]').prop("disabled", !habilitado)
    if (!habilitado) {
        $('input[name="classificacao"]').closest(".form-row").addClass("d-none")
    } else {
        $('input[name="classificacao"]').closest(".form-row").removeClass("d-none")
    }
}

function alternarClassificacaoChamadoModal() {
    const atributos = obterAtributosEditaveisModal()
    const $campoDesenvolvimento = $("#editCardDesenvolvimentoClassificacao")
    const $campoSuporte = $("#editCardSuporteClassificacao")
    const $grupoDesenvolvimento = $("#desenvolvimentoClassificacao")
    const $grupoSuporte = $("#suporteClassificacao")

    if ((!$campoDesenvolvimento.length && !$grupoDesenvolvimento.length) || (!$campoSuporte.length && !$grupoSuporte.length)) {
        return
    }

    const tipoChamado = obterValorTipoChamadoModal()
    const exibirSuporte = tipoChamado === "suporte"
    const exibirDesenvolvimento = !exibirSuporte
    const podeEditarTipoChamado = Boolean(atributos.tipo_chamado)
    const podeEditarModulo = Boolean(atributos.modulo)

    $grupoDesenvolvimento.toggleClass("d-none", !exibirDesenvolvimento)
    $grupoSuporte.toggleClass("d-none", !exibirSuporte)

    $("#editCardTipoChamado").prop("disabled", !podeEditarTipoChamado)

    // Ambos compartilham name="modulo"; apenas o campo ativo deve participar do submit.
    $campoDesenvolvimento.prop(
        "disabled",
        !exibirDesenvolvimento || !podeEditarModulo
    )
    $campoSuporte.prop(
        "disabled",
        !exibirSuporte || !podeEditarModulo
    )

    alternarClassificacaoComplementarModal()
    sincronizarSelect2CampoModal($campoDesenvolvimento)
}

function alternarDescricaoModal(habilitado) {
    const $descricao = $("#editCardDescricao")

    if (!$descricao.length) {
        return
    }

    const $editor = $descricao.next(".note-editor")

    $descricao.summernote(habilitado ? "enable" : "disable")

    if ($editor.length) {
        $editor.find(".note-toolbar, .note-statusbar").toggle(Boolean(habilitado))
        $editor.find(".note-editable").attr("aria-readonly", habilitado ? "false" : "true")
    }
}

function aplicarPermissoesModal() {
    const atributos = obterAtributosEditaveisModal()
    const modoEdicao = modoFormularioCard === "editar"
    const exibirProximoRetorno = modoEdicao || Boolean(atributos.proximo_retorno)
    const exibirResponsavel = modoEdicao || Boolean(atributos.responsavel_id)
    const cardAtual = obterCardOuRascunhoPorId($("#editCardId").val())
    const podeVerChecklist = usuarioPodeVerChecklistModal(cardAtual)
    const podeChecklist = podeVerChecklist && usuarioPodeGerenciarChecklistModal()

    alternarTituloModal(atributos.titulo)
    alternarDescricaoModal(atributos.descricao)
    alternarArquivosModal(Boolean(atributos.anexar_arquivos))
    alternarCamposPlanejamentoModal(exibirProximoRetorno, exibirResponsavel)
    $("#editCardproximo_retorno").prop("disabled", !atributos.proximo_retorno)
    $("#editCardResponsavel").prop("disabled", !atributos.responsavel_id)
    $("#editCardObservadoresBusca").prop("disabled", !atributos.observadores)
    $("#editCardObservadoresBuscarBtn").prop("disabled", !atributos.observadores)
    $("#editCardArquivado").prop("disabled", !atributos.arquivado)
    $("#editCardTipoChamado").prop("disabled", !atributos.tipo_chamado)
    alternarClassificacaoChamadoModal()
    alternarSecaoChecklistModal(podeVerChecklist, podeChecklist)

    if (modoEdicao) {
        alternarSecaoArquivadoModal(true)
        alternarSecaoAtualizacoesModal(true)
        alternarSecaoComentariosModal(true)
        alternarAtualizacoesModal(atributos.criar_atualizacoes_por_texto)
        alternarComentariosModal(atributos.criar_comentarios)
        return
    }

    alternarSecaoArquivadoModal(false)
    alternarSecaoAtualizacoesModal(false)
    alternarSecaoComentariosModal(false)
    alternarAtualizacoesModal(false)
    alternarComentariosModal(false)
}

function alternarArquivosModal(habilitado) {
    $("#modalAttachmentInput").prop("disabled", !habilitado)
    $("#modalAttachmentBrowseBtn").prop("disabled", !habilitado)
    $("#modalAttachmentDropzone")
        .toggleClass("is-disabled", !habilitado)
        .attr("aria-disabled", habilitado ? "false" : "true")
}

function obterTextoPesquisaCard(card) {
    if (!card) {
        return ""
    }

    const atualizacoesTexto = Array.isArray(card.atualizacoes)
        ? card.atualizacoes.map(function (item) {
            const descricao = item && item.descricao ? item.descricao : ""
            const apresentacao = item && item.apresentacao ? item.apresentacao : ""
            return `${descricao} ${apresentacao}`
        }).join(" ")
        : ""
    const comentariosTexto = Array.isArray(card.comentarios)
        ? card.comentarios.map(function (item) {
            const comentario = item && item.comentario ? item.comentario : ""
            const apresentacao = item && item.apresentacao ? item.apresentacao : ""
            return `${comentario} ${apresentacao}`
        }).join(" ")
        : ""
    const arquivosTexto = Array.isArray(card.arquivos)
        ? card.arquivos.map(function (item) {
            return item && item.nome ? item.nome : ""
        }).join(" ")
        : ""
    const observadoresTexto = Array.isArray(card.observadores)
        ? card.observadores.map(function (item) {
            return item && item.apresentacao ? item.apresentacao : ""
        }).join(" ")
        : ""

    const textoComposto = [
        card.id,
        card.titulo,
        removerTagsHtml(card.descricao),
        card.responsavel,
        observadoresTexto,
        card.criado_por,
        formatarDataHoraBr(card.criado_em),
        card.proximo_retorno,
        atualizacoesTexto,
        comentariosTexto,
        arquivosTexto
    ].join(" ")

    return normalizarTextoPesquisa(textoComposto)
}

function obterFiltroResponsavelKanban() {
    const $select = $("#kanbanResponsavelFiltro")
    if (!$select.length) {
        return {
            id: "",
            nome: "",
            semResponsavel: false
        }
    }

    const $opcao = $select.find("option:selected").first()
    const id = String($select.val() || "").trim()
    return {
        id,
        nome: String($opcao.data("nome") || $opcao.attr("data-nome") || "").trim(),
        semResponsavel: id === "__sem_responsavel__"
    }
}

function obterIdResponsavelCard(card) {
    const idResponsavel = String(card && card.responsavel_id ? card.responsavel_id : "").trim()
    return idResponsavel === "0" ? "" : idResponsavel
}

function normalizarDataParaFiltro(valor) {
    const texto = String(valor || "").trim()
    if (!texto) {
        return ""
    }

    const matchIso = texto.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (matchIso) {
        return `${matchIso[1]}-${matchIso[2]}-${matchIso[3]}`
    }

    const matchBr = texto.match(/^(\d{2})\/(\d{2})\/(\d{4})/)
    if (matchBr) {
        return `${matchBr[3]}-${matchBr[2]}-${matchBr[1]}`
    }

    const matchCompact = texto.match(/^(\d{4})(\d{2})(\d{2})/)
    if (matchCompact) {
        return `${matchCompact[1]}-${matchCompact[2]}-${matchCompact[3]}`
    }

    return ""
}

function obterFiltroPeriodoKanban() {
    return {
        inicio: normalizarDataParaFiltro($("#kanbanDataInicioFiltro").val()),
        fim: normalizarDataParaFiltro($("#kanbanDataFimFiltro").val())
    }
}

function obterDataLocalParaFiltro(dataValor) {
    const texto = String(dataValor || "").trim()
    if (!texto) {
        return ""
    }

    const dataNormalizada = normalizarDataParaFiltro(texto)
    if (dataNormalizada) {
        return dataNormalizada
    }

    const data = new Date(texto)
    if (!Number.isNaN(data.getTime())) {
        const doisDigitos = function (valor) {
            return String(valor).padStart(2, "0")
        }

        return `${data.getFullYear()}-${doisDigitos(data.getMonth() + 1)}-${doisDigitos(data.getDate())}`
    }

    return ""
}

function cardCorrespondeFiltroResponsavel(card, filtroResponsavel = null) {
    const filtro = filtroResponsavel || obterFiltroResponsavelKanban()
    const idFiltro = String(filtro && filtro.id ? filtro.id : "").trim()

    if (!idFiltro) {
        return true
    }

    const idCard = obterIdResponsavelCard(card)

    if (filtro.semResponsavel || idFiltro === "__sem_responsavel__") {
        return !idCard
    }

    if (idCard && idCard === idFiltro) {
        return true
    }

    const nomeFiltro = normalizarTextoPesquisa(filtro && filtro.nome ? filtro.nome : "")
    const nomeCard = normalizarTextoPesquisa(card && card.responsavel ? card.responsavel : "")
    return Boolean(nomeFiltro) && Boolean(nomeCard) && nomeCard === nomeFiltro
}

function cardCorrespondeFiltroPeriodo(card, filtroPeriodo = null) {
    const filtro = filtroPeriodo || obterFiltroPeriodoKanban()
    const inicio = String(filtro && filtro.inicio ? filtro.inicio : "").trim()
    const fim = String(filtro && filtro.fim ? filtro.fim : "").trim()

    if (!inicio && !fim) {
        return true
    }

    const dataCard = obterDataLocalParaFiltro(card && card.criado_em ? card.criado_em : "")
    if (!dataCard) {
        return false
    }

    if (inicio && dataCard < inicio) {
        return false
    }

    if (fim && dataCard > fim) {
        return false
    }

    return true
}

function obterUltimaAtualizacao(card) {
    const atualizacoes = card && Array.isArray(card.atualizacoes) ? card.atualizacoes : []
    return atualizacoes.length ? atualizacoes[0] : null
}

function obterResumoUltimaAtualizacao(card) {
    const ultimaAtualizacao = obterUltimaAtualizacao(card)
    if (!ultimaAtualizacao || !ultimaAtualizacao.descricao) {
        return "Sem atualizações"
    }

    const criado_em = formatarDataHoraBr(ultimaAtualizacao.criado_em)
    return `${ultimaAtualizacao.descricao} (${criado_em})`
}

function obterResumoChecklistCard(card) {
    const checklist = card && Array.isArray(card.checklist) ? normalizarItensChecklist(card.checklist) : []
    const total = checklist.length

    if (!total) {
        return null
    }

    const concluidos = checklist.filter(function (item) {
        return Boolean(item && item.concluido)
    }).length

    return {
        total: total,
        concluidos: concluidos,
        completo: concluidos === total
    }
}

function renderizarBadgeChecklistCard(card) {
    const resumo = obterResumoChecklistCard(card)
    if (!resumo) {
        return ""
    }

    const classeBadge = resumo.completo ? "badge-success" : "badge-info"

    return `
        <div class="kanban-card-checklist-summary mt-2 d-flex justify-content-end">
            <span class="badge ${classeBadge} border ml-2"><i class="fas fa-tasks mr-1"></i>${resumo.concluidos}/${resumo.total}</span>
        </div>
    `
}

function criarCardHtml(card) {
    let { modulo: sistema_modulo } = card

    let sistema = ''
    let modulo_desenv = ''
    let modulo_suporte = ''

    if (card.tipo_chamado == "desenvolvimento") {
        if (sistema_modulo) {
            [sistema] = sistema_modulo.split("/")
            modulo_desenv = sistema_modulo.replaceAll(`${sistema}/`, "")
        }
    } else {
        modulo_suporte = moduloLabel[card.modulo]
    }

    const resumoUltimaAtualizacao = obterResumoUltimaAtualizacao(card)

    const badge_class = card.ultima_coluna == "concluidas" ? "badge-success" : "badge-danger"
    const badge_text = card.ultima_coluna == "concluidas" ? "Concluído" : "Cancelado"
    const badge_display = card.coluna == "arquivados" ? "initial" : "d-none"
    const responsavelEhUsuarioAtual = USUARIO_ATUAL_ID.length > 0
        && String(card.responsavel_id || "").trim() === USUARIO_ATUAL_ID

    return `
        <div class="card kanban-card shadow-sm" data-id="${escaparHtml(card.id)}">
            <div class="card-body p-2">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 font-weight-bold kanban-card-title">${escaparHtml(card.titulo)}</h6>
                    <span class="badge badge-light border ml-2">T-${escaparHtml(card.id)}</span>
                </div>
                <span class="mb-2 kanban-card-arquivado-badge badge ${badge_class} ${badge_display}">${badge_text}</span>
                <div class="small text-muted mb-2">
                    <i class="far fa-calendar-alt mr-1"></i><span class="kanban-card-proximo-retorno-text">Próximo retorno: ${card.proximo_retorno ? escaparHtml(formatarDataBr(card.proximo_retorno)) : '-'}</span>
                </div>
                <div class="small mb-2">
                    <div class="text-muted ${!sistema ? 'd-none mb-2' : 'mb-2'}">
                        <i class="fa fa-globe mr-1"></i><strong>Sistema:</strong> <span class="kanban-card-sistema">${escaparHtml(sistema)}</span>
                    </div>
                    <div class="text-muted ${!modulo_desenv ? 'd-none' : ''}">
                        <i class="fa fa-puzzle-piece mr-1"></i><strong>Modulo:</strong> <span class="kanban-card-modulo_desenv">${escaparHtml(modulo_desenv)}</span>
                    </div>
                    <div class="text-muted ${!modulo_suporte ? 'd-none' : ''}">
                        <i class="fa fa-wrench mr-1"></i><strong>Modulo:</strong> <span class="kanban-card-modulo_suporte">${escaparHtml(modulo_suporte)}</span>
                    </div>
                </div>
                <div class="kanban-card-responsavel-line small mb-2 ${responsavelEhUsuarioAtual ? "text-dark font-weight-bold" : "text-muted"}">
                    <i class="far fa-user mr-1"></i><strong>Responsável:</strong> <span class="kanban-card-responsavel ${responsavelEhUsuarioAtual ? "font-weight-bold" : ""}">${escaparHtml(card.responsavel)}</span>
                </div>
                <div class="text-muted small mb-2">
                    <i class="far fa-eye mr-1"></i><strong>Observadores:</strong> <span class="kanban-card-observadores">${renderizarBadgesObservadores(card)}</span>
                </div>
                <div class="kanban-card-ultima-atualizacao mb-2">
                    <i class="far fa-comment-dots mr-1"></i><strong>Última atualização:</strong>
                    <span class="kanban-card-ultima-atualizacao-text">${escaparHtml(resumoUltimaAtualizacao)}</span>
                </div>
                <div class="kanban-card-meta">
                    <i class="far fa-id-badge mr-1"></i><strong>Criado por:</strong> <span class="kanban-card-criado-por">${escaparHtml(card.criado_por || "Sistema")}</span>
                    <span class="ml-2"><strong>Em:</strong> <span class="kanban-card-criado-em">${escaparHtml(formatarDataHoraBr(card.criado_em))}</span></span>
                </div>
                ${renderizarBadgeChecklistCard(card)}
            </div>
        </div>
    `
}

function obterCardPorId(id) {
    return indiceCards[String(id)] || null
}

function cardEstaArquivado(card) {
    return Boolean(card && (card.arquivado === true || card.arquivado === "on" || card.coluna === COLUNA_ARQUIVADOS))
}

function obterElementoCardPorId(id) {
    const cardId = String(id)
    return $(".kanban-card").filter(function () {
        return String($(this).data("id")) === cardId
    }).first()
}

function atualizarVisualCard($card) {
    const coluna = $card.closest(".kanban-column").data("column")

    $card.removeClass(Object.values(classesCardPorColuna).join(" "))
    $card.addClass(classesCardPorColuna[coluna] || "")

    const $titulo = $card.find(".kanban-card-title")
    $titulo.removeClass(Object.values(classesTituloPorColuna).join(" "))
    $titulo.addClass(classesTituloPorColuna[coluna] || "")
}

function atualizarContadores() {
    colunasKanban.forEach(function (coluna) {
        const total = $(`#col-${coluna} .kanban-card`).length
        $(`[data-count="${coluna}"]`).text(total)
    })
}

function criarEstadoVazioColunaHtml() {
    return '<div class="kanban-empty-state kanban-column-empty-state">Nenhum card nesta coluna</div>'
}

function atualizarEstadosVaziosColunas(termoBusca = termoPesquisaAtual) {
    const exibirEstadoVazio = !Boolean(termoBusca)

    colunasKanban.forEach(function (coluna) {
        const $lista = $(`#col-${coluna}`)
        const totalCards = $lista.children(".kanban-card").length
        const $estadoVazio = $lista.children(".kanban-column-empty-state")

        if (exibirEstadoVazio && totalCards === 0) {
            if (!$estadoVazio.length) {
                $lista.append(criarEstadoVazioColunaHtml())
            }
            return
        }

        $estadoVazio.remove()
    })
}

function ocultarEstadoVazioColunaDuranteArraste($lista) {
    if (!$lista || !$lista.length) {
        return
    }

    $lista.children(".kanban-column-empty-state").addClass("d-none")
}

function restaurarEstadoVazioColunaDuranteArraste($lista) {
    if (!$lista || !$lista.length) {
        return
    }

    if (termoPesquisaAtual || $lista.children(".kanban-card").length > 0) {
        $lista.children(".kanban-column-empty-state").remove()
        return
    }

    const $estadoVazio = $lista.children(".kanban-column-empty-state")
    if ($estadoVazio.length) {
        $estadoVazio.removeClass("d-none")
        return
    }

    $lista.append(criarEstadoVazioColunaHtml())
}

function aplicarFiltroPesquisa() {
    const termoBusca = normalizarTextoPesquisa($("#kanbanSearchInput").val())
    const filtroResponsavel = obterFiltroResponsavelKanban()
    const filtroPeriodo = obterFiltroPeriodoKanban()
    const responsavelFiltroAtivo = Boolean(String(filtroResponsavel.id || "").trim())
    const periodoFiltroAtivo = Boolean(filtroPeriodo.inicio || filtroPeriodo.fim)
    const temFiltrosAtivos = Boolean(termoBusca || responsavelFiltroAtivo || periodoFiltroAtivo)
    termoPesquisaAtual = termoBusca

    let totalVisivel = 0

    colunasKanban.forEach(function (coluna) {
        const $coluna = $(`.kanban-column[data-column="${coluna}"]`)
        const $cards = $(`#col-${coluna} .kanban-card`)
        const totalColuna = $cards.length
        let visiveisColuna = 0

        $cards.each(function () {
            const card = obterCardPorId($(this).data("id"))
            const textoCard = obterTextoPesquisaCard(card)
            const combinaTexto = !termoBusca || textoCard.indexOf(termoBusca) !== -1
            const combinaResponsavel = cardCorrespondeFiltroResponsavel(card, filtroResponsavel)
            const combinaPeriodo = cardCorrespondeFiltroPeriodo(card, filtroPeriodo)
            const deveExibir = combinaTexto && combinaResponsavel && combinaPeriodo

            $(this).toggle(deveExibir)

            if (deveExibir) {
                visiveisColuna += 1
                totalVisivel += 1
            }
        })

        $coluna.show()
        $(`[data-count="${coluna}"]`).text(temFiltrosAtivos ? visiveisColuna : totalColuna)
    })

    const semResultados = temFiltrosAtivos && totalVisivel === 0
    $("#kanbanNoSearchResults").toggleClass("d-none", !semResultados)
    $("#kanbanClearSearch").prop("disabled", !temFiltrosAtivos)
    atualizarEstadosVaziosColunas(termoBusca)
}

function sincronizarEstadoComDOM() {
    colunasKanban.forEach(function (coluna) {
        const novaLista = []

        $(`#col-${coluna} .kanban-card`).each(function () {
            const cardId = String($(this).data("id"))
            const card = obterCardPorId(cardId)

            if (!card) {
                return
            }

            card.coluna = coluna
            if (coluna !== COLUNA_ARQUIVADOS) {
                card.ultima_coluna = coluna
            }

            novaLista.push(card)
        })

        dadosKanban[coluna] = novaLista
    })
}

function moverCardEntreColunas(card, colunaDestino) {
    if (!card || !colunasKanban.includes(colunaDestino)) {
        return
    }

    if (dadosKanban[card.coluna]) {
        dadosKanban[card.coluna] = dadosKanban[card.coluna].filter(function (item) {
            return item.id !== card.id
        })
    }

    card.coluna = colunaDestino
    if (colunaDestino !== COLUNA_ARQUIVADOS) {
        card.ultima_coluna = colunaDestino
    }

    if (!Array.isArray(dadosKanban[colunaDestino])) {
        dadosKanban[colunaDestino] = []
    }

    dadosKanban[colunaDestino].unshift(card)
}

function registrarAtualizacao(card, descricao, apresentacao = USUARIO_ATUAL) {
    if (!card) {
        return
    }

    const atualizacao = criarAtualizacao(descricao, apresentacao)
    if (!atualizacao.descricao) {
        return
    }

    if (!Array.isArray(card.atualizacoes)) {
        card.atualizacoes = []
    }

    card.atualizacoes.unshift(atualizacao)
}

function registrarMovimentacao(card, colunaDestino) {
    const nomeDestino = nomesColuna[colunaDestino] || colunaDestino
    console.log("🚀 ~ registrarMovimentacao ~ colunaDestino:", colunaDestino)
    console.log("🚀 ~ registrarMovimentacao ~ nomeDestino:", nomeDestino)
    registrarAtualizacao(card, `${USUARIO_ATUAL} moveu este cartão para ${nomeDestino}`, USUARIO_ATUAL)
}

function atualizarCardNoDOM(card) {
    const cardBase = card && card.id
        ? Object.assign({}, obterCardPorId(card.id) || {}, card)
        : (card || {})
    card = cardBase

    let { modulo: sistema_modulo } = cardBase

    let sistema = ''
    let modulo_desenv = ''
    let modulo_suporte = ''

    if (cardBase.tipo_chamado == "desenvolvimento") {
        if (sistema_modulo) {
            [sistema] = sistema_modulo.split("/")
            modulo_desenv = sistema_modulo.replaceAll(`${sistema}/`, "")
        }
    } else {
        modulo_suporte = moduloLabel[cardBase.modulo]
    }

    const $card = obterElementoCardPorId(cardBase.id)
    let descricao = cardBase.descricao || ''

    let descricao_texto = descricao.replace(/<[^>]+>/g, '')
    let descricao_curta = descricao_texto.length > 150
        ? descricao_texto.substring(0, 150) + '...'
        : descricao_texto

    if (!$card.length) {
        return
    }

    let badge_class = "kanban-card-arquivado-badge badge "
    badge_class += card.ultima_coluna == "concluidas" ? " badge-success" : " badge-danger"
    badge_class += card.coluna == "arquivados" ? " initial" : " d-none"
    const badge_text = card.ultima_coluna == "concluidas" ? "Concluído" : "Cancelado"

    $card.find(".kanban-card-title").text(card.titulo || "")
    $card.find(".kanban-card-arquivado-badge").attr("class", badge_class)
    $card.find(".kanban-card-arquivado-badge").text(badge_text)
    $card.find(".kanban-card-proximo-retorno-text").text(`Próximo retorno: ${card.proximo_retorno ? formatarDataBr(card.proximo_retorno) : '-'}`)
    $card.find(".kanban-card-description").html(sanitizarRichText(descricao_curta || ""))
    const responsavelEhUsuarioAtual = USUARIO_ATUAL_ID.length > 0 && String(card.responsavel_id || "").trim() === USUARIO_ATUAL_ID
    $card.find(".kanban-card-responsavel")
        .text(card.responsavel || "")
        .toggleClass("font-weight-bold", responsavelEhUsuarioAtual)
    $card.find(".kanban-card-responsavel-line")
        .toggleClass("text-muted", !responsavelEhUsuarioAtual)
        .toggleClass("text-dark", responsavelEhUsuarioAtual)
        .toggleClass("font-weight-bold", responsavelEhUsuarioAtual)
    $card.find(".kanban-card-sistema").text(sistema || "")
    $card.find(".kanban-card-modulo_desenv").text(modulo_desenv || "")
    $card.find(".kanban-card-modulo_suporte").text(modulo_suporte || "")
    $card.find(".kanban-card-observadores").html(renderizarBadgesObservadores(card))
    $card.find(".kanban-card-ultima-atualizacao-text").text(obterResumoUltimaAtualizacao(card))
    $card.find(".kanban-card-criado-por").text(card.criado_por || "Sistema")
    $card.find(".kanban-card-criado-em").text(formatarDataHoraBr(card.criado_em))

    const badgeChecklistHtml = renderizarBadgeChecklistCard(card)
    const $badgeChecklist = $card.find(".kanban-card-checklist-summary")

    if (!badgeChecklistHtml) {
        $badgeChecklist.remove()
    } else if ($badgeChecklist.length) {
        $badgeChecklist.replaceWith(badgeChecklistHtml.trim())
    } else {
        $card.find(".card-body").append(badgeChecklistHtml.trim())
    }
}

function renderizarKanban(aoConcluir) {
    const requisicao = requestAjax(
        {
            objeto: "Chamados",
            metodo: "getChamados"
        },
        function (result) {
            preencherDadosKanban(result)

            colunasKanban.forEach(function (coluna) {
                const $lista = $(`#col-${coluna}`)
                $lista.empty()

                const cards = Array.isArray(dadosKanban[coluna]) ? dadosKanban[coluna] : []
                cards.forEach(function (card) {
                    $lista.append(criarCardHtml(card))
                })
            })

            $(".kanban-card").each(function () {
                atualizarVisualCard($(this))
            })

            iniciarSortableKanban()
            atualizarContadores()
            aplicarFiltroPesquisa()
            abrirChamadoDaUrlSeNecessario()
        },
        false
    )

    if (typeof aoConcluir === "function") {
        requisicao.always(function () {
            window.setTimeout(function () {
                aoConcluir()
            }, 0)
        })
    }

    return requisicao
}


function iniciarSortableKanban() {
    if ($(".kanban-cards").data("ui-sortable")) {
        $(".kanban-cards").sortable("destroy")
    }

    if ($(".kanban-board").hasClass("no-grabbing")) { return }

    $(".kanban-cards").sortable({
        items: ".kanban-card",
        connectWith: ".kanban-cards",
        placeholder: "kanban-placeholder",
        forcePlaceholderSize: true,
        tolerance: "pointer",
        scroll: false,
        cancel: ".kanban-search-bar, #kanbanSearchInput, #kanbanClearSearch",
        sort: function (event, ui) {
            gerenciarColunasDuranteArrasteCard(event)
            aplicarAutoScrollDuranteArraste(event, ui)
        },
        start: function (event, ui) {
            cardSendoArrastado = true
            colunasColapsadasNoInicioArrasteCard = new Set(obterColunasKanbanColapsadasAtuais())
            colunasAutoAbertasDuranteArrasteCard = new Set()

            ui.placeholder.height(ui.item.outerHeight())

            ui.item.data("coluna-origem", ui.item.closest(".kanban-column").data("column"))

            const posicaoOrigem = ui.item.parent().children("[data-id]").index(ui.item)
            ui.item.data("posicao-origem", posicaoOrigem)
        },
        over: function (event, ui) {
            ocultarEstadoVazioColunaDuranteArraste($(this))
        },
        out: function (event, ui) {
            restaurarEstadoVazioColunaDuranteArraste($(this))
        },
        stop: function (event, ui) {
            const cardId = ui.item.data("id")
            const card = obterCardPorId(cardId)
            const colunaOrigem = ui.item.data("coluna-origem")
            const colunaDestino = ui.item.closest(".kanban-column").data("column")
            const posicaoOrigem = ui.item.data("posicao-origem")
            const posicaoDestino = ui.item.parent().children("[data-id]").index(ui.item)
            if (card && colunaOrigem !== colunaDestino) {
                if (colunaDestino === COLUNA_ARQUIVADOS && colunaOrigem !== COLUNA_ARQUIVADOS) {
                    card.ultima_coluna = colunaOrigem || card.ultima_coluna || COLUNA_PADRAO
                } else if (colunaOrigem === COLUNA_ARQUIVADOS && colunaDestino !== COLUNA_ARQUIVADOS) {
                    card.ultima_coluna = colunaDestino
                } else if (colunaDestino !== COLUNA_ARQUIVADOS) {
                    card.ultima_coluna = colunaDestino
                }

                registrarMovimentacao(card, colunaDestino)
            }

            if (posicaoOrigem != posicaoDestino || colunaOrigem != colunaDestino) {
                requestAjax(
                    {
                        'objeto': "Chamados",
                        'metodo': "moverChamado",
                        'id': card.id,
                        'ultima_coluna': card && card.ultima_coluna ? card.ultima_coluna : colunaOrigem,
                        'coluna': colunaDestino,
                        'posicao': posicaoDestino
                    }, function (result) {
                    },
                    false
                )

            }
            sincronizarEstadoComDOM()
            atualizarVisualCard(ui.item)
            atualizarCardNoDOM(card || {})
            atualizarContadores()
            aplicarFiltroPesquisa()
            atualizarSortableCardsPosicoes()

            const colunaFinal = ui.item.closest(".kanban-column").data("column")
            colunasAutoAbertasDuranteArrasteCard.forEach(function (coluna) {
                if (coluna === colunaFinal) {
                    return
                }

                const $coluna = $(`.kanban-column[data-column="${coluna}"]`).first()
                if (!$coluna.length || $coluna.hasClass("collapsed-card")) {
                    return
                }

                if (colunasColapsadasNoInicioArrasteCard.has(coluna)) {
                    alternarColunaKanban(coluna, false)
                }
            })

            colunasColapsadasNoInicioArrasteCard = new Set()
            colunasAutoAbertasDuranteArrasteCard = new Set()

            window.setTimeout(function () {
                cardSendoArrastado = false
            }, 0)



        }
    }).disableSelection()
}

function iniciarSortableColunas() {
    const $board = $(".kanban-board").first()

    if (!$board.length) {
        return
    }

    if ($board.data("ui-sortable")) {
        $board.sortable("destroy")
    }

    $board.sortable({
        items: "> .kanban-column",
        handle: ".card-header",
        cancel: ".kanban-column-toggle, .kanban-column-toggle *",
        helper: "clone",
        placeholder: "kanban-column-placeholder",
        forcePlaceholderSize: true,
        forceHelperSize: true,
        tolerance: "pointer",
        axis: "x",
        scroll: false,
        sort: function (event) {
            aplicarAutoScrollHorizontal(Number(event.pageX))
        },
        start: function (event, ui) {
            ui.placeholder.height(ui.item.outerHeight())
            ui.placeholder.width(ui.item.outerWidth())
            ui.helper.css({
                width: ui.item.outerWidth(),
                maxWidth: ui.item.outerWidth()
            })
        },
        update: function () {
            salvarOrdemColunasKanban()
        }
    }).disableSelection()
}

// O CardWidget dispara o evento antes de a animação terminar; persistimos o
// estado pelo evento, não pela classe do DOM naquele instante.
$(document).on("collapsed.lte.cardwidget", ".kanban-column-toggle", function () {
    atualizarEstadoColunaColapsadaKanban($(this).closest(".kanban-column").data("column"), true)
})

$(document).on("expanded.lte.cardwidget", ".kanban-column-toggle", function () {
    atualizarEstadoColunaColapsadaKanban($(this).closest(".kanban-column").data("column"), false)
})

function inicializarModalEdicao() {
    $("#editCardDescricao").summernote({
        height: 180,
        dialogsInBody: true,
        toolbar: [
            ["style", ["bold", "italic", "underline", "clear"]],
            ["para", ["ul", "ol", "paragraph"]],
            ["insert", ["link"]],
            ["view", ["codeview", "fullscreen"]]
        ]
    })

    $("#editCardTitulo").attr("maxlength", LIMITE_TITULO_CARD)
    $("#modalUpdateInput").attr("maxlength", LIMITE_TEXTO_ATUALIZACAO)

    definirAtributosEditaveisModal(ATRIBUTOS_EDITAVEIS)
    aplicarPermissoesModal()
    sincronizarBotaoEnviarComentarioModal()
}

function abrirModalCriacao(colunaAlvo) {
    modoFormularioCard = "criar"
    definirAtributosEditaveisModal(obterAtributosCriacaoCard())
    definirTempIdArquivoModal()
    colunaCriacaoAtual = colunasKanban.includes(colunaAlvo) ? colunaAlvo : COLUNA_PADRAO

    if (!ATRIBUTOS_EDITAVEIS.criar_em_qualquer_coluna && colunaCriacaoAtual !== COLUNA_PADRAO) {
        colunaCriacaoAtual = COLUNA_PADRAO
    }

    atualizarCorCabecalhoModal(colunaCriacaoAtual)

    $("#modalEditarCardLabel").text("Novo card")
    $("#editCardId").val("")
    $("#editCardTitulo").val("")
    $("#editCardproximo_retorno").val("")
    $("#editCardTipoChamado").val($("#editCardTipoChamado option").first().val() || "desenvolvimento")
    $("#editCardDesenvolvimentoClassificacao").val("")
    $("#editCardSuporteClassificacao").val("")
    preencherClassificacaoChamadoModal("")
    sincronizarSelect2CampoModal($("#editCardDesenvolvimentoClassificacao"))
    $("#editCardObservadoresBusca").val("")
    popularSelectObservadores([])
    limparBuscaObservadores()
    $("#editCardDescricao").summernote("code", "")
    $("#editCardArquivado").prop("checked", colunaCriacaoAtual === COLUNA_ARQUIVADOS)
    atualizarMetaCriacaoModal(null)
    renderizarAtualizacoesModal(null)
    renderizarComentariosModal(null)
    renderizarArquivosModal(null)
    renderizarChecklistModal(null)
    definirMencionaveisComentario([])
    alternarArquivosModal(false)
    aplicarPermissoesModal()
    $("#modalUpdateInput").val("")
    $("#modalChecklistInput").val("")
    limparEditorComentarioModal()
    $("#modalAttachmentInput").val("")
    limparMarcacoesComentario()
    limparFeedbackArrasteModal()

    $("#modalEditarCard").modal("show")
}

function abrirModalEdicaoCard(cardId) {
    limparTempIdArquivoModal()

    return requestAjax(
        {
            'objeto': "Chamados",
            'metodo': "getChamado",
            'id': cardId
        }, function (card) {
            if (!card) {
                return
            }

            definirAtributosEditaveisModal(card.atributos_editaveis || ATRIBUTOS_EDITAVEIS)

            const cardExistente = obterCardPorId(card.id || cardId) || {}
            const colunaAtual = colunasKanban.includes(card.coluna) ? card.coluna : (colunasKanban.includes(cardExistente.coluna) ? cardExistente.coluna : COLUNA_PADRAO)
            const ultimaColunaNormalizada = colunaAtual === COLUNA_ARQUIVADOS
                ? (card.ultima_coluna || cardExistente.ultima_coluna || COLUNA_PADRAO)
                : colunaAtual
            const cardNormalizado = {
                id: String(card.id || cardExistente.id || ""),
                titulo: card.titulo || cardExistente.titulo || "",
                descricao: card.descricao || cardExistente.descricao || "",
                tipo_chamado: card.tipo_chamado || cardExistente.tipo_chamado || "",
                modulo: card.modulo || cardExistente.modulo || "",
                classificacao: card.classificacao || cardExistente.classificacao || "",
                proximo_retorno: card.proximo_retorno || cardExistente.proximo_retorno || "",
                responsavel_id: card.responsavel_id || card.id_responsavel || cardExistente.responsavel_id || "",
                responsavel: obterValorTextoRequest(card, ["responsavel", "responsavel_nome", "responsavel_id"], cardExistente.responsavel || ""),
                observadores: normalizarListaUsuarios(Array.isArray(card.observadores) ? card.observadores : cardExistente.observadores),
                coluna: colunaAtual,
                posicao: Number(card.posicao || cardExistente.posicao) || 0,
                ultima_coluna: ultimaColunaNormalizada,
                atualizacoes: normalizarAtualizacoes(Array.isArray(card.atualizacoes) ? card.atualizacoes : cardExistente.atualizacoes),
                comentarios: normalizarComentarios(Array.isArray(card.comentarios) ? card.comentarios : cardExistente.comentarios),
                arquivos: normalizarArquivos(Array.isArray(card.arquivos) ? card.arquivos : cardExistente.arquivos),
                checklist: normalizarItensChecklist(Array.isArray(card.checklist) ? card.checklist : cardExistente.checklist),
                criado_por_id: card.criado_por_id || cardExistente.criado_por_id || "",
                atributos_editaveis: Object.assign({}, obterAtributosEditaveisModal()),
                criado_por: obterValorTextoRequest(card, ["criado_por", "criado_por_nome", "criado_por_id"], cardExistente.criado_por || "Sistema"),
                criado_em: card.criado_em || cardExistente.criado_em || obterDataHoraAtualIso(),
                arquivado: colunaAtual === COLUNA_ARQUIVADOS
            }

            const cardEmUso = cardExistente && cardExistente.id
                ? Object.assign(cardExistente, cardNormalizado)
                : cardNormalizado

            indiceCards[cardEmUso.id] = cardEmUso
            modoFormularioCard = "editar"
            colunaCriacaoAtual = cardEmUso.coluna === COLUNA_ARQUIVADOS
                ? (cardEmUso.ultima_coluna || COLUNA_PADRAO)
                : cardEmUso.coluna
            atualizarCorCabecalhoModal(cardEmUso.coluna)

            $("#editCardResponsavel option").each(function () {
                let $item = $(this)
                let id = $item.val()

                if (id == cardEmUso.responsavel_id) {
                    $item.attr("selected", "selected")
                } else {
                    $item.removeAttr("selected")
                }
            })
            $("#editCardResponsavel").val(cardEmUso.responsavel_id || "")

            $("#modalEditarCardLabel").text(`Editar tarefa ${cardEmUso.id}`)
            $("#editCardId").val(cardEmUso.id)
            limparTempIdArquivoModal()
            $("#editCardTitulo").val(cardEmUso.titulo)
            $("#editCardproximo_retorno").val(cardEmUso.proximo_retorno)
            $("#editCardTipoChamado").val(cardEmUso.tipo_chamado || $("#editCardTipoChamado option").first().val() || "desenvolvimento")
            $("#editCardDesenvolvimentoClassificacao").val("")
            $("#editCardSuporteClassificacao").val("")
            preencherClassificacaoChamadoModal(cardEmUso.classificacao || "")
            if ((cardEmUso.tipo_chamado || "").toLowerCase() === "suporte") {
                $("#editCardSuporteClassificacao").val(cardEmUso.modulo || "")
            } else {
                $("#editCardDesenvolvimentoClassificacao").val(cardEmUso.modulo || "")
            }
            sincronizarSelect2CampoModal($("#editCardDesenvolvimentoClassificacao"))
            $("#editCardObservadoresBusca").val("")
            popularSelectObservadores(cardEmUso.observadores)
            limparBuscaObservadores()
            $("#editCardDescricao").summernote("code", cardEmUso.descricao || "")
            $("#editCardArquivado").prop("checked", cardEmUso.coluna === COLUNA_ARQUIVADOS)
            atualizarMetaCriacaoModal(cardEmUso)
            renderizarAtualizacoesModal(cardEmUso)
            renderizarComentariosModal(cardEmUso)
            renderizarArquivosModal(cardEmUso)
            renderizarChecklistModal(cardEmUso)
            alternarArquivosModal(true)
            aplicarPermissoesModal()
            sincronizarBotaoEnviarComentarioModal()
            $("#modalUpdateInput").val("")
            $("#modalChecklistInput").val("")
            limparEditorComentarioModal()
            $("#modalAttachmentInput").val("")
            limparFeedbackArrasteModal()
            atualizarMencionaveisComentarioDoModal()

            $("#modalEditarCard").modal("show")
            window.setTimeout(function () {
                definirAtributosEditaveisModal(cardEmUso.atributos_editaveis || ATRIBUTOS_EDITAVEIS)
                aplicarPermissoesModal()
                sincronizarBotaoEnviarComentarioModal()
            }, 0)
        },
        false
    )
}

function salvarNovoCard(onSuccess = null) {
    const chaveRascunhoCriacao = obterChaveChecklistModal()
    const titulo = $.trim(limitarTexto($("#editCardTitulo").val(), LIMITE_TITULO_CARD) || "")
    const proximo_retorno = $("#editCardproximo_retorno").val() || ""
    const tipoChamado = obterValorTipoChamadoModal() || ($("#editCardTipoChamado").val() || "")
    const modulo = obterValorModuloChamadoModal()
    const classificacao = obterValorClassificacaoChamadoModal()
    const responsavel = $("#editCardResponsavel").val() || ""
    const observadores = obterObservadoresSelecionadosModal()
    const observadoresIds = obterIdsObservadoresSelecionadosModal()
    const descricao = sanitizarRichText($("#editCardDescricao").summernote("code"))
    const deveArquivar = $("#editCardArquivado").is(":checked")
    const colunaBase = colunaCriacaoAtual === COLUNA_ARQUIVADOS ? COLUNA_PADRAO : colunaCriacaoAtual
    const colunaDestino = deveArquivar ? COLUNA_ARQUIVADOS : colunaBase
    const checklistRascunho = normalizarItensChecklist(obterChecklistEstadoCard(chaveRascunhoCriacao) || [])

    const dadosFormulario = {};
    $("#formEditarCard").serializeArray().forEach(item => {
        if (item.name !== "id" && item.name !== "observadores[]") {
            dadosFormulario[item.name] = item.value;
        }
    });

    dadosFormulario['descricao'] = descricao
    dadosFormulario['coluna'] = colunaDestino
    dadosFormulario['observadores'] = observadoresIds
    if (checklistRascunho.length) {
        dadosFormulario['checklist'] = checklistRascunho.map(function (item, indice) {
            return {
                texto: String(item && item.texto ? item.texto : "").trim(),
                concluido: item && item.concluido ? 1 : 0,
                ordem: Number(item && item.ordem ? item.ordem : 0) || (indice + 1)
            }
        })
    }

    requestAjax(
        {
            'objeto': "Chamados",
            'metodo': "criaChamado",
            ...dadosFormulario
        }, function (result) {
            const novoCardId = result && result.id ? result.id : gerarNovoIdCard()
            const checklistSalva = normalizarItensChecklist(Array.isArray(result && result.checklist) ? result.checklist : checklistRascunho)
            const novoCard = {
                id: novoCardId,
                titulo: titulo,
                tipo_chamado: tipoChamado,
                modulo: modulo,
                classificacao: classificacao,
                proximo_retorno: proximo_retorno,
                responsavel_id: responsavel,
                responsavel: responsavel,
                observadores: observadores,
                descricao: descricao,
                coluna: colunaDestino,
                ultima_coluna: colunaBase,
                atualizacoes: [],
                comentarios: [],
                arquivos: normalizarArquivos(Array.isArray(result && result.arquivos) ? result.arquivos : []),
                checklist: checklistSalva,
                criado_por: USUARIO_ATUAL,
                criado_em: obterDataHoraAtualIso()
            }

            registrarAtualizacao(novoCard, "Card criado.", USUARIO_ATUAL)

            indiceCards[novoCard.id] = novoCard
            sincronizarEstadoComplementarCard(novoCard)
            limparEstadoComplementarCard(chaveRascunhoCriacao)
            dadosKanban[colunaDestino].unshift(novoCard)
            aplicarFiltroPesquisa()

            if (typeof onSuccess === "function") {
                onSuccess(novoCard, result)
            }
        }
    )

}

function salvarEdicaoCard(onSuccess = null) {
    const cardId = $("#editCardId").val()
    const card = obterCardPorId(cardId)

    if (!card) {
        return
    }

    card.titulo = $.trim(limitarTexto($("#editCardTitulo").val(), LIMITE_TITULO_CARD) || "")
    card.proximo_retorno = $("#editCardproximo_retorno").val() || ""
    card.tipo_chamado = obterValorTipoChamadoModal() || ($("#editCardTipoChamado").val() || "")
    card.modulo = obterValorModuloChamadoModal()
    card.classificacao = obterValorClassificacaoChamadoModal()
    card.responsavel_id = $("#editCardResponsavel").val() || ""
    card.observadores = obterObservadoresSelecionadosModal()
    card.arquivado = $("#editCardArquivado").is(":checked") ? 'on' : ''
    card.descricao = sanitizarRichText($("#editCardDescricao").summernote("code"))
    sincronizarEstadoComplementarCard(card)

    const deveArquivar = $("#editCardArquivado").is(":checked")
    const estaArquivado = card.coluna === COLUNA_ARQUIVADOS
    const ultimaColunaValida = card.ultima_coluna && card.ultima_coluna !== COLUNA_ARQUIVADOS
        ? card.ultima_coluna
        : COLUNA_PADRAO
    let destino = estaArquivado
        ? COLUNA_ARQUIVADOS
        : (card.coluna || COLUNA_PADRAO)

    if (deveArquivar) {
        if (!estaArquivado) {
            card.ultima_coluna = card.coluna || ultimaColunaValida || COLUNA_PADRAO
        }
        destino = COLUNA_ARQUIVADOS
    } else {
        if (estaArquivado) {
            destino = ultimaColunaValida
        }

        if (destino !== COLUNA_ARQUIVADOS) {
            card.ultima_coluna = destino
        }
    }

    card.coluna = destino
    const dadosRequisicao = Object.assign({}, card)
    delete dadosRequisicao.checklist

    requestAjax(
        {
            'objeto': "Chamados",
            'metodo': "editaChamado",
            ...dadosRequisicao
        }, function (result) {
            moverCardEntreColunas(result, destino)
            registrarMovimentacao(result, destino)
            atualizarCardNoDOM(result)
            aplicarFiltroPesquisa()

            if (typeof onSuccess === "function") {
                onSuccess(result)
            }
        }, false
    )
}

function adicionarAtualizacaoPorCardId(cardId, texto) {
    const card = obterCardPorId(cardId)
    if (!card) {
        return
    }

    const mensagem = String(texto || "").trim()
    if (!mensagem) {
        return
    }

    registrarAtualizacao(card, mensagem, USUARIO_ATUAL)
    atualizarCardNoDOM(card)
    if (String($("#editCardId").val() || "") === String(card.id)) {
        renderizarAtualizacoesModal(card)
    }
    aplicarFiltroPesquisa()
}

function adicionarComentarioPorCardId(cardId, texto, mencoes = []) {
    requestAjax(
        {
            'objeto': "ChamadosComentarios",
            'metodo': "criaComentario",
            'id_chamado': cardId,
            'comentario': texto,
            'mencoes': mencoes
        }, function (result) {
            if (!result.id) return
            const card = obterCardPorId(cardId)
            if (!card) {
                return
            }

            const mensagem = String(texto || "").trim()
            if (!mensagem) {
                return
            }

            if (!Array.isArray(card.comentarios)) {
                card.comentarios = []
            }

            card.comentarios.unshift(criarComentario(mensagem, USUARIO_ATUAL, obterDataHoraAtualIso(), result.id, USUARIO_ATUAL_ID))
            card.comentarios = normalizarComentarios(card.comentarios)
            sincronizarEstadoComplementarCard(card)

            if (String($("#editCardId").val() || "") === String(card.id)) {
                renderizarComentariosModal(card)
            }

            aplicarFiltroPesquisa()
        },
        false
    )

}

function enviarComentarioDoModal() {
    const cardId = $("#editCardId").val()
    sincronizarMarcacoesComentarioComTexto()
    const textoComentario = obterTextoComentarioModal()
    const marcacoes = obterMarcacoesComentarioAtivas()
    adicionarComentarioPorCardId(cardId, textoComentario, marcacoes)
    limparEditorComentarioModal()
    limparMarcacoesComentario()
}

function excluirComentarioPorCardId(cardId, comentarioId) {
    const card = obterCardPorId(cardId)
    const comentarioIdNormalizado = String(comentarioId || "").trim()
    if (!card || !comentarioIdNormalizado) {
        return
    }

    requestAjax(
        {
            'objeto': "ChamadosComentarios",
            'metodo': "excluirComentario",
            'id': comentarioIdNormalizado
        }, function (result) {
            if (result && result.status && String(result.status) !== "success") {
                return
            }

            card.comentarios = normalizarComentarios((Array.isArray(card.comentarios) ? card.comentarios : []).filter(function (item) {
                return String(item && item.id ? item.id : "") !== comentarioIdNormalizado
            }))
            sincronizarEstadoComplementarCard(card)

            if (String($("#editCardId").val() || "") === String(card.id)) {
                renderizarComentariosModal(card)
            }

            aplicarFiltroPesquisa()
        },
        false
    )
}

function adicionarAnexoPorCardId(cardId, arquivo) {
    const chaveCard = String(cardId || "").trim()
    if (!chaveCard || !arquivo) {
        return false
    }

    const formData = new FormData()
    formData.append('file', arquivo, arquivo.name)
    formData.append('objeto', 'ChamadosArquivos')
    formData.append('metodo', 'criaArquivo')
    if (obterCardPorId(chaveCard)) {
        formData.append('id_chamado', chaveCard)
    } else {
        const tempIdChamado = obterTempIdArquivoModal()
        if (tempIdChamado) {
            formData.append('temp_id_chamado', tempIdChamado)
        }
    }

    requestAjax(
        formData,
        function (result) {
            if (!result || !result.id) {
                return
            }

            const card = obterCardOuRascunhoPorId(chaveCard)
            if (!card) {
                return
            }

            if (!Array.isArray(card.arquivos)) {
                card.arquivos = []
            }

            card.arquivos.unshift(criarAnexo(
                result && result.nome ? result.nome : arquivo.name,
                result && result.tipo ? result.tipo : (arquivo.type || ""),
                arquivo.size || 0,
                result && result.criado_em ? result.criado_em : obterDataHoraAtualIso(),
                result && result.id ? result.id : null,
                USUARIO_ATUAL_ID
            ))

            card.arquivos = normalizarArquivos(card.arquivos)
            sincronizarEstadoComplementarCard(card)

            if (String($("#editCardId").val() || "").trim() === String(card.id)
                || (modoFormularioCard === "criar" && chaveCard === obterChaveRascunhoCriacaoCard())) {
                renderizarArquivosModal(card)
            }

            aplicarFiltroPesquisa()
            exibirAvisoKanban("success", `Arquivo ${arquivo.name} anexado com sucesso.`)
        },
        false
    )

    return true
}

function alternarFeedbackArrasteModal(ativo) {
    $("#modalEditarCard").toggleClass("is-dragover", ativo)
    $("#modalCardDropOverlay").toggleClass("d-none", !ativo)
    $("#modalAttachmentDropzone").toggleClass("is-active", ativo)
}

function limparFeedbackArrasteModal() {
    window.clearTimeout(timeoutFeedbackArrasteModal)
    timeoutFeedbackArrasteModal = null
    alternarFeedbackArrasteModal(false)
}

function manterFeedbackArrasteModal() {
    window.clearTimeout(timeoutFeedbackArrasteModal)
    alternarFeedbackArrasteModal(true)
}

function agendarOcultacaoFeedbackArrasteModal() {
    window.clearTimeout(timeoutFeedbackArrasteModal)
    timeoutFeedbackArrasteModal = window.setTimeout(function () {
        alternarFeedbackArrasteModal(false)
    }, 80)
}

function eventoContemArquivos(event) {
    const original = event && event.originalEvent ? event.originalEvent : event
    const tipos = original && original.dataTransfer ? original.dataTransfer.types : original && original.clipboardData ? original.clipboardData.types : []

    if (!tipos || !tipos.length) {
        return false
    }

    return Array.from(tipos).indexOf("Files") !== -1
}

function extrairArquivosTransferidos(listaArquivos, listaItens = []) {
    const arquivosDiretos = Array.from(listaArquivos || []).filter(function (item) {
        return item && typeof item.name === "string"
    })

    if (arquivosDiretos.length) {
        return arquivosDiretos
    }

    return Array.from(listaItens || []).map(function (item) {
        if (!item || item.kind !== "file" || typeof item.getAsFile !== "function") {
            return null
        }

        return item.getAsFile()
    }).filter(function (item) {
        return item && typeof item.name === "string"
    })
}

function processarListaArquivosModal(listaArquivos, listaItens = []) {
    const cardId = String($("#editCardId").val() || "").trim() || obterChaveRascunhoCriacaoCard()
    if (!cardId) {
        return
    }

    const arquivos = extrairArquivosTransferidos(listaArquivos, listaItens)

    if (!arquivos.length) {
        return
    }

    if (arquivos.length > 1) {
        exibirAvisoKanban("info", "Apenas o primeiro arquivo foi adicionado ao card.")
    }

    const arquivo = arquivos[0]
    if (!arquivoEhPermitidoParaAnexo(arquivo)) {
        exibirAvisoKanban(
            "warning",
            "Formato de arquivo não permitido. Use imagens, PDF, DOC, DOCX, XLS, XLSX, CSV, ODS, TXT, ZIP, RAR ou 7Z."
        )
        return
    }

    adicionarAnexoPorCardId(cardId, arquivo)
}

function excluirAnexoPorCardId(cardId, anexoId) {
    const card = obterCardOuRascunhoPorId(cardId)
    const anexoIdNormalizado = String(anexoId || "").trim()
    if (!card || !anexoIdNormalizado) {
        return
    }

    requestAjax(
        {
            'objeto': "ChamadosArquivos",
            'metodo': "excluirArquivo",
            'id': anexoIdNormalizado
        }, function (result) {
            if (result && result.status && String(result.status) !== "success") {
                return
            }

            card.arquivos = normalizarArquivos((Array.isArray(card.arquivos) ? card.arquivos : []).filter(function (item) {
                return String(item && item.id ? item.id : "") !== anexoIdNormalizado
            }))
            sincronizarEstadoComplementarCard(card)

            if (String($("#editCardId").val() || "").trim() === String(card.id)
                || (modoFormularioCard === "criar" && String(card.id) === obterChaveRascunhoCriacaoCard())) {
                renderizarArquivosModal(card)
            }

            aplicarFiltroPesquisa()
            exibirAvisoKanban("success", "Arquivo excluído com sucesso.")
        },
        false
    )
}

function vincularEventos() {
    $(document).on("click", ".kanban-card", function (event) {
        if (cardSendoArrastado) {
            return
        }

        const cardId = $(this).data("id")
        abrirModalEdicaoCard(cardId)
    })

    $(document).on("click", ".kanban-create-card", function () {
        const coluna = $(this).data("create-column")

        if ($(this).prop("disabled")) {
            return
        }

        if (!ATRIBUTOS_EDITAVEIS.criar_em_qualquer_coluna && coluna !== COLUNA_PADRAO) {
            return
        }

        abrirModalCriacao(coluna)
    })

    $(document).on("input", "#kanbanSearchInput", function () {
        aplicarFiltroPesquisa()
    })

    $(document).on("change", "#kanbanResponsavelFiltro", function () {
        aplicarFiltroPesquisa()
    })

    $(document).on("input change", "#kanbanDataInicioFiltro, #kanbanDataFimFiltro", function () {
        aplicarFiltroPesquisa()
    })

    $(document).on("click", "#kanbanClearSearch", function () {
        $("#kanbanSearchInput").val("")
        $("#kanbanDataInicioFiltro").val("")
        $("#kanbanDataFimFiltro").val("")

        const $filtroResponsavel = $("#kanbanResponsavelFiltro")
        if ($filtroResponsavel.length) {
            $filtroResponsavel.val("")
        }

        aplicarFiltroPesquisa()
        $("#kanbanSearchInput").trigger("focus")
    })

    $(document).on("input", "#editCardObservadoresBusca", function () {
        filtrarOpcoesObservadores($(this).val())
    })

    $(document).on("focus", "#editCardObservadoresBusca", function () {
        const termo = $(this).val()
        if ($.trim(termo)) {
            filtrarOpcoesObservadores(termo)
        }
    })

    $(document).on("keydown", "#editCardObservadoresBusca", function (event) {
        if (event.key !== "Enter") {
            return
        }

        event.preventDefault()
        adicionarObservadorModal($(this).val())
    })

    $(document).on("click", "#editCardObservadoresBuscarBtn", function () {
        $("#editCardObservadoresBusca").trigger("focus")
    })

    $(document).on("change", "#editCardResponsavel", function () {
        atualizarMencionaveisComentarioDoModal()
        atualizarPainelMarcacaoComentario()
    })

    $(document).on("change", '#editCardTipoChamado, select[name="tipo_chamado"]', function () {
        alternarClassificacaoChamadoModal()
    })

    $(document).on("click", "#editCardObservadoresPesquisa li[user-id]", function () {
        if (!obterAtributosEditaveisModal().observadores) {
            return
        }

        alternarObservadorPorItemPesquisa($(this))
    })

    $(document).on("click", ".kanban-observador-chip-remove", function () {
        if (obterAtributosEditaveisModal().observadores) {
            removerObservadorModal($(this).data("observador"))
        }
    })

    $(document).on("click", function (event) {
        if (!$(event.target).closest("#editCardObservadoresBusca, #editCardObservadoresPesquisa, #editCardObservadoresBuscarBtn").length) {
            ocultarBuscaObservadores()
        }
    })

    $(document).on("click", "#modalAddUpdateBtn", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const cardId = $("#editCardId").val()
        const descricao = $.trim(limitarTexto($("#modalUpdateInput").val(), LIMITE_TEXTO_ATUALIZACAO) || "")
        requestAjax(
            {
                'objeto': "ChamadosAtualizacoes",
                'metodo': "criaAtualizacao",
                'descricao': descricao,
                'id_chamado': cardId,
            }, function (result) {
                if (result.id) {
                    adicionarAtualizacaoPorCardId(cardId, descricao)
                    $("#modalUpdateInput").val("")
                }
            },
            false
        )
    })

    $(document).on("keydown", "#modalUpdateInput", function (event) {
        if (event.key !== "Enter") {
            return
        }

        event.preventDefault()
        event.stopPropagation()

        const cardId = $("#editCardId").val()
        adicionarAtualizacaoPorCardId(cardId, limitarTexto($(this).val(), LIMITE_TEXTO_ATUALIZACAO))
        $(this).val("")
    })

    $(document).on("click", "#modalChecklistAddBtn", function (event) {
        event.preventDefault()
        event.stopPropagation()

        adicionarItemChecklistModal()
    })

    $(document).on("keydown", "#modalChecklistInput", function (event) {
        if (event.key !== "Enter") {
            return
        }

        event.preventDefault()
        event.stopPropagation()
        adicionarItemChecklistModal()
    })

    $(document).on("input", ".kanban-checklist-item-input", function () {
        const cardId = obterChaveChecklistModal()
        const itemId = String($(this).closest(".kanban-checklist-item").data("checklist-item-id") || "").trim()
        atualizarTextoChecklistItemModal(cardId, itemId, $(this).val())
    })

    $(document).on("blur", ".kanban-checklist-item-input", function () {
        const cardId = obterChaveChecklistModal()
        const itemId = String($(this).closest(".kanban-checklist-item").data("checklist-item-id") || "").trim()
        const texto = $.trim($(this).val() || "")

        if (!texto) {
            renderizarChecklistModal(obterCardOuRascunhoPorId(cardId) || { id: cardId })
            return
        }

        salvarChecklistItemModal(cardId, itemId)
    })

    $(document).on("keydown", ".kanban-checklist-item-input", function (event) {
        if (event.key !== "Enter") {
            return
        }

        event.preventDefault()
        event.stopPropagation()
        $(this).trigger("blur")
    })

    $(document).on("change", ".kanban-checklist-item-toggle", function () {
        const cardId = obterChaveChecklistModal()
        const itemId = String($(this).closest(".kanban-checklist-item").data("checklist-item-id") || "").trim()
        alternarConcluidoChecklistModal(cardId, itemId, $(this).is(":checked"))
    })

    $(document).on("click", ".kanban-checklist-item-remove", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const cardId = obterChaveChecklistModal()
        const itemId = String($(this).data("checklist-item-remove") || $(this).closest(".kanban-checklist-item").data("checklist-item-id") || "").trim()
        removerItemChecklistModal(cardId, itemId)
    })

    $(document).on("click", "#modalAddCommentBtn", function (event) {
        event.preventDefault()
        event.stopPropagation()

        enviarComentarioDoModal()
    })

    $(document).on("keydown", "#modalCommentInput", function (event) {
        if (event.key === "Escape") {
            ocultarPainelMarcacaoComentario()
            return
        }

        if (!(event.ctrlKey || event.metaKey) || event.key !== "Enter") {
            window.requestAnimationFrame(function () {
                normalizarEditorComentarioModalVazio()
                atualizarPainelMarcacaoComentario()
                sincronizarMarcacoesComentarioComTexto()
            })
            return
        }

        event.preventDefault()
        event.stopPropagation()

        enviarComentarioDoModal()
    })

    $(document).on("click", ".kanban-comment-delete", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const cardId = $("#editCardId").val()
        const card = obterCardOuRascunhoPorId(cardId)
        if (cardEstaArquivado(card)) {
            exibirAvisoKanban("warning", "Não é possível excluir comentários de cards arquivados.")
            return
        }

        const comentarioId = $(this).data("id")
        excluirComentarioPorCardId(cardId, comentarioId)
    })

    $(document).on("click", ".kanban-expand-toggle", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const $botao = $(this)
        const $container = $botao.closest(".kanban-expandable-content")
        const expandido = $botao.attr("aria-expanded") === "true"
        const vaiExpandir = !expandido
        const $conteudo = $container.find(".kanban-expandable-body").first()

        if (!$conteudo.length) {
            return
        }

        const textoCompleto = decodificarTextoExpandivel($conteudo.attr("data-expand-full"))
        const textoResumido = decodificarTextoExpandivel($conteudo.attr("data-expand-preview"))
        const temIndicadorExpandir = String($conteudo.attr("data-expand-mention-indicator") || "") === "1"
        const nomesMarcacoes = obterNomesMarcacoesComentarioDoElemento($conteudo)

        if (textoCompleto || textoResumido) {
            const textoRenderizado = $conteudo.hasClass("kanban-comment-body")
                ? renderizarTextoComentarioComMarcacoes(vaiExpandir ? textoCompleto : textoResumido, nomesMarcacoes)
                : escaparHtml(vaiExpandir ? textoCompleto : textoResumido)

            $conteudo.html(textoRenderizado)
        }

        $conteudo.toggleClass("is-collapsed", !vaiExpandir)
        $botao.attr("aria-expanded", vaiExpandir ? "true" : "false")
        $botao.replaceWith(renderizarBotaoExpandivelComentario(vaiExpandir, temIndicadorExpandir))

        const $updatesWrap = $container.closest(".kanban-updates-table-wrap")
        if ($updatesWrap.length) {
            const possuiAtualizacaoExpandida = $updatesWrap.find(".kanban-update-description.kanban-expandable-body").filter(function () {
                return $(this).hasClass("is-collapsed") === false
            }).length > 0

            $updatesWrap.toggleClass("has-expanded-content", possuiAtualizacaoExpandida)

            if (vaiExpandir && $conteudo.get(0) && typeof $conteudo.get(0).scrollIntoView === "function") {
                window.requestAnimationFrame(function () {
                    $conteudo.get(0).scrollIntoView({ block: "nearest" })
                })
            }
        }
    })

    $(document).on("click", "#modalAttachmentBrowseBtn", function (event) {
        event.preventDefault()
        $("#modalAttachmentInput").trigger("click")
    })

    $(document).on("input keyup mouseup focus", "#modalCommentInput", function () {
        normalizarEditorComentarioModalVazio()
        sincronizarBotaoEnviarComentarioModal()
        atualizarPainelMarcacaoComentario()
        sincronizarMarcacoesComentarioComTexto()
    })

    $(document).on("mousedown", ".kanban-mention-item", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const pessoa = {
            id: String($(this).data("id") || "").trim(),
            nome: String($(this).data("nome") || "").trim(),
            nome_cargo: String($(this).data("cargo") || "").trim(),
            nome_setor: String($(this).data("setor") || "").trim()
        }

        if (!pessoa.id || !pessoa.nome) {
            return
        }

        inserirMarcacaoComentario(pessoa)
    })

    $(document).on("paste", "#modalCommentInput", function (event) {
        if (!editorComentarioEstaHabilitado()) {
            return
        }

        const clipboardData = event.originalEvent ? event.originalEvent.clipboardData : null
        const textoPlano = clipboardData ? String(clipboardData.getData("text/plain") || "") : ""

        if (!textoPlano) {
            return
        }

        event.preventDefault()
        inserirTextoNoEditorComentario(textoPlano)
        atualizarPainelMarcacaoComentario()
        sincronizarMarcacoesComentarioComTexto()
    })

    $(document).on("click", ".kanban-attachment-action-preview", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const $acao = $(this)
        const url = String($acao.attr("href") || "").trim()
        const tipo = String($acao.data("preview-type") || "").trim()
        const nome = String($acao.data("preview-name") || $acao.attr("title") || "").trim()

        if (!url) {
            return
        }

        abrirVisualizacaoAnexo(url, tipo, nome)
    })

    $(document).on("click", function (event) {
        if (!$(event.target).closest("#modalCommentInput, #modalCommentMentionsPanel").length) {
            ocultarPainelMarcacaoComentario()
        }
    })

    $(document).on("change", "#modalAttachmentInput", function () {
        processarListaArquivosModal(this.files, "upload")
        $(this).val("")
    })

    $(document).on("click", ".kanban-attachment-action-delete", function (event) {
        event.preventDefault()
        event.stopPropagation()

        const cardId = String($("#editCardId").val() || "").trim() || obterChaveRascunhoCriacaoCard()
        const card = obterCardOuRascunhoPorId(cardId)
        if (cardEstaArquivado(card)) {
            exibirAvisoKanban("warning", "Não é possível excluir arquivos de cards arquivados.")
            return
        }

        const anexoId = $(this).data("id")
        excluirAnexoPorCardId(cardId, anexoId)
    })

    $(document).on("dragenter dragover", "#modalEditarCard .modal-content", function (event) {
        if (!eventoContemArquivos(event) || $("#modalEditarCard").hasClass("show") === false) {
            return
        }

        event.preventDefault()
        event.stopPropagation()

        if ($("#modalAttachmentBrowseBtn").prop("disabled")) {
            return
        }

        manterFeedbackArrasteModal()

        if (event.originalEvent && event.originalEvent.dataTransfer) {
            event.originalEvent.dataTransfer.dropEffect = "copy"
        }
    })

    $(document).on("dragleave", "#modalEditarCard .modal-content", function (event) {
        if (!eventoContemArquivos(event)) {
            return
        }

        event.preventDefault()
        event.stopPropagation()
        agendarOcultacaoFeedbackArrasteModal()
    })

    $(document).on("drop", "#modalEditarCard .modal-content", function (event) {
        if (!eventoContemArquivos(event)) {
            return
        }

        event.preventDefault()
        event.stopPropagation()
        limparFeedbackArrasteModal()

        if ($("#modalAttachmentBrowseBtn").prop("disabled")) {
            return
        }

        const dataTransfer = event.originalEvent ? event.originalEvent.dataTransfer : null
        processarListaArquivosModal(
            dataTransfer && dataTransfer.files ? dataTransfer.files : [],
            "drop",
            dataTransfer && dataTransfer.items ? dataTransfer.items : []
        )
    })

    $(document).on("paste", "#modalEditarCard", function (event) {
        if (!eventoContemArquivos(event) || $("#modalAttachmentBrowseBtn").prop("disabled")) {
            return
        }

        const clipboardData = event.originalEvent ? event.originalEvent.clipboardData : null
        processarListaArquivosModal(
            clipboardData && clipboardData.files ? clipboardData.files : [],
            "clipboard",
            clipboardData && clipboardData.items ? clipboardData.items : []
        )
        event.preventDefault()
    })

    $(document).on("submit", "#formEditarCard", function (event) {
        event.preventDefault()

        const form = this
        if (!form.checkValidity()) {
            form.reportValidity()
            return
        }

        const finalizarSubmitCard = function () {
            renderizarKanban()
            $("#modalEditarCard").modal("hide")
        }

        if (modoFormularioCard === "criar") {
            salvarNovoCard(finalizarSubmitCard)
            return
        }

        salvarEdicaoCard(finalizarSubmitCard)
    })

    $("#modalEditarCard").on("hidden.bs.modal", function () {
        const form = $("#formEditarCard").get(0)
        if (form) {
            form.reset()
        }

        limparTempIdArquivoModal()
        definirAtributosEditaveisModal(ATRIBUTOS_EDITAVEIS)
        $("#editCardDescricao").summernote("code", "")
        preencherClassificacaoChamadoModal("")
        $("#editCardObservadoresBusca").val("")
        popularSelectObservadores([])
        limparBuscaObservadores()
        $("#editCardArquivado").prop("checked", false)
        $("#modalUpdateInput").val("")
        limparEditorComentarioModal()
        $("#modalAttachmentInput").val("")
        limparMarcacoesComentario()
        renderizarAtualizacoesModal(null)
        renderizarComentariosModal(null)
        renderizarArquivosModal(null)
        renderizarChecklistModal(null)
        atualizarMetaCriacaoModal(null)
        definirMencionaveisComentario([])
        alternarArquivosModal(false)
        sincronizarSelect2CampoModal($("#editCardDesenvolvimentoClassificacao"))
        aplicarPermissoesModal()
        limparFeedbackArrasteModal()
        $("#modalEditarCardLabel").text("Editar tarefa")
        atualizarCorCabecalhoModal("")
        modoFormularioCard = "editar"
        colunaCriacaoAtual = COLUNA_PADRAO
        $("#modalChecklistInput").val("")
        sincronizarBotaoEnviarComentarioModal()
    })

    $("#modalEditarCard").on("shown.bs.modal", function () {
        window.requestAnimationFrame(function () {
            sincronizarBotaoEnviarComentarioModal()
        })
    })
}

$(document).ready(function () {
    inicializarIndiceCards()
    inicializarModalEdicao()
    inicializarSelectObservadores()
    popularSelectObservadores([])
    aplicarOrdemColunasKanban()
    iniciarSortableColunas()
    renderizarKanban(function () {
        aplicarColunasColapsadasKanban()
        finalizarInicializacaoKanban()
    })
    vincularEventos()

    $('#editCardDesenvolvimentoClassificacao').select2({
        theme: 'bootstrap-5',
        placeholder: 'Selecione',
        dropdownParent: $('#modalEditarCard'),
        width: '100%'
    })

    $('#editCardDesenvolvimentoClassificacao').on('select2:open', function () {
        window.setTimeout(function () {
            const campoPesquisa = document.querySelector('.select2-container--open .select2-search__field')
            if (campoPesquisa) {
                campoPesquisa.focus()
            }
        }, 0)
    })
})
