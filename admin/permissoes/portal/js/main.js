let tabelaPermissoes = null
let idPermissaoExclusao = null
const permissoesAlteradas = new Map()

function atualizarBotaoSalvarAlteracoes() {
    $("#salvarAlteracoesPermissoes").prop("disabled", permissoesAlteradas.size === 0)
}

function registrarAlteracaoPermissao(idPermissao, campo, valor, valorOriginal) {
    const permissao = permissoesAlteradas.get(idPermissao) || { id: idPermissao }

    if (valor === valorOriginal) {
        delete permissao[campo]
    } else {
        permissao[campo] = valor
    }

    if (Object.keys(permissao).length === 1) {
        permissoesAlteradas.delete(idPermissao)
        return
    }

    permissoesAlteradas.set(idPermissao, permissao)
}

function coletarPermissoesVisiveis() {
    const permissoes = new Map()

    tabelaPermissoes.rows({ page: "current" }).every(function () {
        const permissao = this.data()

        permissoes.set(Number(permissao.id), {
            id: Number(permissao.id),
            Consulta: Number(permissao.Consulta) === 1 ? 1 : 0,
            Incluir: Number(permissao.Incluir) === 1 ? 1 : 0,
            Excluir: Number(permissao.Excluir) === 1 ? 1 : 0,
            Alterar: Number(permissao.Alterar) === 1 ? 1 : 0
        })
    })

    $("#tabelaPermissoes .alternador-permissao").each(function () {
        const alternador = $(this)
        const idPermissao = Number(alternador.data("id-permissao"))
        const campo = alternador.data("campo")
        const permissao = permissoes.get(idPermissao)

        if (!permissao) return

        permissao[campo] = alternador.is(":checked") ? 1 : 0
    })

    return Array.from(permissoes.values())
}

function renderizarAlternadorPermissao(valor, nome, campo, idPermissao) {
    const permissaoAlterada = permissoesAlteradas.get(idPermissao)
    const valorAtual = permissaoAlterada?.[campo] ?? Number(valor)
    const estaAtivo = Number(valorAtual) === 1 ? "checked" : ""

    return `
        <div class="custom-control custom-switch d-flex justify-content-center">
            <input
                type="checkbox"
                class="custom-control-input alternador-permissao"
                id="${nome}"
                data-id-permissao="${idPermissao}"
                data-campo="${campo}"
                data-valor-original="${Number(valor)}"
                ${estaAtivo}
            >
            <label class="custom-control-label" for="${nome}"></label>
        </div>
    `
}

function renderizarTabelaPermissoes(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaPermissoes")) {
        tabelaPermissoes = $("#tabelaPermissoes").DataTable({
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
                    dados.objeto = "Persistemas"
                    dados.metodo = "getPermissoes"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "u.apresentacao", data: "apresentacao" },
                { name: "u.estado_conselho", data: "estado_conselho" },
                {
                    name: "r.Rotina",
                    data: "Rotina",
                    render: function (dados, tipo, permissao) {
                        return `(${permissao.Rotina || ""}) ${permissao.Descricao || ""}`
                    }
                },
                {
                    name: "p.Consulta",
                    data: "Consulta",
                    className: "dt-center",
                    render: (dados, tipo, permissao) => {
                        if (tipo !== "display") return dados
                        return renderizarAlternadorPermissao(dados, `consulta_${permissao.id}`, "Consulta", permissao.id)
                    }
                },
                {
                    name: "p.Incluir",
                    data: "Incluir",
                    className: "dt-center",
                    render: (dados, tipo, permissao) => {
                        if (tipo !== "display") return dados
                        return renderizarAlternadorPermissao(dados, `incluir_${permissao.id}`, "Incluir", permissao.id)
                    }
                },
                {
                    name: "p.Excluir",
                    data: "Excluir",
                    className: "dt-center",
                    render: (dados, tipo, permissao) => {
                        if (tipo !== "display") return dados
                        return renderizarAlternadorPermissao(dados, `excluir_${permissao.id}`, "Excluir", permissao.id)
                    }
                },
                {
                    name: "p.Alterar",
                    data: "Alterar",
                    className: "dt-center",
                    render: (dados, tipo, permissao) => {
                        if (tipo !== "display") return dados
                        return renderizarAlternadorPermissao(dados, `alterar_${permissao.id}`, "Alterar", permissao.id)
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: "dt-center no-search all always-visible td-edicoes",
                    render: (dados, tipo, permissao) => `
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-danger btn-delete-permissao" data-id="${permissao.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `
                }
            ],
            columnDefs: [],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaPermissoes.ajax.reload(null, false)
}

$(function () {
    const campoUsuario = $("#id_usuario")
    const botaoSalvarAlteracoes = $("#salvarAlteracoesPermissoes")

    renderizarTabelaPermissoes()

    iniciarSelect2(campoUsuario, "Selecione um usuario")
    campoUsuario.val((campoUsuario.val() || []).filter(Boolean)).trigger("change")

    $("#formUsuarioPermissao").on("submit", function (e) {
        e.preventDefault()

        const formData = new FormData(this)
        const usuarios = ($("#id_usuario").val() || []).filter(Boolean)
        formData.append("Usuarios", usuarios)

        requestAjax(formData, function (resultado) {
            tabelaPermissoes.ajax.reload(null, false)
        })
    })

    $("#tabelaPermissoes").on("change", ".alternador-permissao", function () {
        const alternador = $(this)
        const idPermissao = alternador.data("id-permissao")
        const campo = alternador.data("campo")
        const valor = alternador.is(":checked") ? 1 : 0
        const valorOriginal = Number(alternador.data("valor-original"))

        registrarAlteracaoPermissao(idPermissao, campo, valor, valorOriginal)
        atualizarBotaoSalvarAlteracoes()
    })

    $("#tabelaPermissoes").on("click", ".btn-delete-permissao", function () {
        idPermissaoExclusao = $(this).data("id")
        $("#modalExcluirPermissao").modal("show")
    })

    $("#confirmarExcluirPermissao").on("click", function () {
        requestAjax(
            {
                'objeto': "Persistemas",
                'metodo': "deletePermissao",
                'id': idPermissaoExclusao
            }, function(result){
                console.log("🚀 ~ result:", result)
            }
        )
        $("#modalExcluirPermissao").modal("hide")
    })

    $("#modalExcluirPermissao").on("hidden.bs.modal", function () {
        idPermissaoExclusao = null
    })

    botaoSalvarAlteracoes.on("click", function () {
        const alteracoes = Array.from(permissoesAlteradas.values())
        const permissoes = coletarPermissoesVisiveis()

        requestAjax(
            {
                'objeto': "Persistemas",
                'metodo': "editPersistemaseMassa",
                'permissoes': permissoes
            }, function(result){
                console.log("🚀 ~ result:", result)
            }
        )

        permissoesAlteradas.clear()
        atualizarBotaoSalvarAlteracoes()
    })
})
