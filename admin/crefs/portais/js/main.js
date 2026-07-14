
var tabelaPortais;
var arquivoLogoPortal = null;
var conselhosRegionaisPortal = {
    RJ: "CREF1/RJ",
    RS: "CREF2/RS",
    SC: "CREF3/SC",
    SP: "CREF4/SP",
    CE: "CREF5/CE",
    MG: "CREF6/MG",
    DF: "CREF7/DF",
    AM: "CREF8/AM-AC-RO-RR",
    PR: "CREF9/PR",
    PB: "CREF10/PB",
    MS: "CREF11/MS",
    PE: "CREF12/PE",
    BA: "CREF13/BA",
    GO: "CREF14/GO-TO",
    PI: "CREF15/PI",
    RN: "CREF16/RN",
    MT: "CREF17/MT",
    PA: "CREF18/PA-AP",
    AL: "CREF19/AL",
    SE: "CREF20/SE",
    MA: "CREF21/MA",
    ES: "CREF22/ES",
}

function escaparHtmlPortal(valor) {
    return $("<div>").text(valor ?? "").html()
}

function formatarCnpjPortal(valor) {
    return String(valor || "").replace(/\D/g, "").slice(0, 14)
        .replace(/^(\d{2})(\d)/, "$1.$2")
        .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/\.(\d{3})(\d)/, ".$1/$2")
        .replace(/(\d{4})(\d)/, "$1-$2")
}

function formatarCepPortal(valor) {
    return String(valor || "").replace(/\D/g, "").slice(0, 8).replace(/^(\d{5})(\d)/, "$1-$2")
}

function formatarTelefonePortal(valor) {
    const digitos = String(valor || "").replace(/\D/g, "").slice(0, 11)

    if (digitos.length <= 10) {
        return digitos.replace(/^(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2")
    }

    return digitos.replace(/^(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2")
}

function normalizarUrlLogoPortal(valor) {
    let urlLogo = String(valor || "").trim().replace(/\\/g, "/")

    if (!urlLogo) {
        return ""
    }

    if (/^https?:\/\//i.test(urlLogo) || urlLogo.startsWith("/")) {
        return urlLogo
    }

    urlLogo = urlLogo.replace(/^file:\/\/\//i, "")

    const marcadorArmazenamento = "webconfef_storage/"
    const indiceArmazenamento = urlLogo.indexOf(marcadorArmazenamento)

    if (indiceArmazenamento >= 0) {
        return "/" + urlLogo.substring(indiceArmazenamento)
    }

    return "/webconfef_storage/" + urlLogo.replace(/^\/+/, "")
}

function renderizarLogoPortalTabela(valor) {
    const urlLogo = normalizarUrlLogoPortal(valor)

    if (!urlLogo) {
        return '<span class="text-muted">-</span>'
    }

    const urlEscapada = escaparHtmlPortal(urlLogo)
    return `<img src="${urlEscapada}" alt="Logo do portal" class="logo-portal-tabela">`
}

function renderizarAtivoPortal(valor) {
    return Number(valor) === 1
        ? '<span class="badge badge-success">Ativo</span>'
        : '<span class="badge badge-danger">Inativo</span>'
}

function renderizarConselhoRegionalPortal(valor) {
    const sigla = String(valor || "").trim().toUpperCase()
    return escaparHtmlPortal(conselhosRegionaisPortal[sigla] || sigla || "-")
}

function definirModoFormularioPortal(idPortal = null) {
    const cartao = $("#cartaoFormularioPortal")
    const titulo = $("#tituloFormularioPortal")
    const indicador = $("#indicadorModoFormularioPortal")
    const botaoSalvar = $("#botaoSalvarPortal")
    const botaoLimpar = $("#botaoLimparPortal")
    const campoEstado = $("#estado_conselho")
    const idAtual = Number(idPortal)
    const editando = Number.isInteger(idAtual) && idAtual > 0

    cartao.toggleClass("card-primary", !editando)
    cartao.toggleClass("card-warning", editando)

    campoEstado.prop("disabled", editando)

    if (editando) {
        titulo.text("Editar portal")
        indicador.html(`<i class="fas fa-pen mr-1"></i> Editando ID #${idAtual}`)
        botaoSalvar
            .removeClass("btn-primary")
            .addClass("btn-warning")
            .html('<i class="fas fa-save mr-1"></i> Salvar alterações')
        botaoLimpar.text("Cancelar edição")
        return
    }

    titulo.text("Cadastrar portal")
    indicador.html('<i class="fas fa-plus-circle mr-1"></i> Modo de criação')
    botaoSalvar
        .removeClass("btn-warning")
        .addClass("btn-primary")
        .html('<i class="fas fa-plus mr-1"></i> Salvar portal')
    botaoLimpar.text("Limpar")
}

function carregarPreviewLogoPortalUrl(urlLogo) {
    const urlNormalizada = normalizarUrlLogoPortal(urlLogo)
    arquivoLogoPortal = null
    $("#logo_portal").val("")

    if (!urlNormalizada) {
        limparPreviewLogoPortal()
        return
    }

    $("#previewLogoPortal").attr("src", urlNormalizada).removeClass("d-none")
    $("#conteudoLogoPortal").addClass("d-none")
    $("#areaLogoPortal").removeClass("border-primary").addClass("border-success")
}

function obterDadosLinhaPortal(botao) {
    const linha = $(botao).closest("tr")
    const linhaPrincipal = linha.hasClass("child") ? linha.prev() : linha
    return tabelaPortais ? tabelaPortais.row(linhaPrincipal).data() : null
}

function carregarPortalNoFormulario(portal) {
    if (!portal) {
        return
    }

    $("#formPortais input[name='id']").val(portal.id || "")
    $("#estado_conselho").val(portal.estado_conselho || "").trigger("change")
    ;[
        "cnpj", "endereco", "numero", "complemento", "bairro", "cidade", "estado",
        "cep", "email", "telefone", "transparencia", "facebook", "instagram", "linkedin", "youtube",
        "spotify", "twitter"
    ].forEach(function (campo) {
        $(`#${campo}`).val(portal[campo] || "")
    })
    $("#ativo").prop("checked", Number(portal.ativo) === 1)
    carregarPreviewLogoPortalUrl(portal.logo)
    definirModoFormularioPortal(portal.id)
    document.getElementById("cartaoFormularioPortal")?.scrollIntoView({ behavior: "smooth", block: "start" })
}

function atualizarPreviewLogoPortal(arquivo) {
    if (!arquivo || !arquivo.type || !arquivo.type.startsWith("image/")) {
        exibirMensagemBootstrap("Selecione uma imagem válida.", "danger")
        return
    }

    arquivoLogoPortal = arquivo

    const leitor = new FileReader()
    leitor.onload = function (evento) {
        $("#previewLogoPortal").attr("src", evento.target.result).removeClass("d-none")
        $("#conteudoLogoPortal").addClass("d-none")
        $("#areaLogoPortal").removeClass("border-primary").addClass("border-success")
    }
    leitor.readAsDataURL(arquivo)
}

function atribuirArquivoLogoPortal(arquivo) {
    const input = document.getElementById("logo_portal")

    if (input && window.DataTransfer) {
        const transferencia = new DataTransfer()
        transferencia.items.add(arquivo)
        input.files = transferencia.files
    }

    atualizarPreviewLogoPortal(arquivo)
}

function converterBase64ParaArquivoLogoPortal(base64, nomeArquivo, tipoArquivo) {
    const binario = atob(base64)
    const tamanho = binario.length
    const bytes = new Uint8Array(tamanho)

    for (let i = 0; i < tamanho; i++) {
        bytes[i] = binario.charCodeAt(i)
    }

    return new File([bytes], nomeArquivo, { type: tipoArquivo })
}

function obterImagemTransferida(transferencia) {
    if (!transferencia) {
        return null
    }

    const arquivos = Array.from(transferencia.files || [])
    const arquivo = arquivos.find((item) => item.type && item.type.startsWith("image/"))

    if (arquivo) {
        return arquivo
    }

    const itens = Array.from(transferencia.items || [])
    const itemImagem = itens.find((item) => item.type && item.type.startsWith("image/"))

    return itemImagem ? itemImagem.getAsFile() : null
}

function limparPreviewLogoPortal() {
    arquivoLogoPortal = null
    $("#logo_portal").val("")
    $("#previewLogoPortal").attr("src", "").addClass("d-none")
    $("#conteudoLogoPortal").removeClass("d-none")
    $("#areaLogoPortal").removeClass("border-primary border-success")
}

function renderizarTabelaPortais(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaPortais")) {
        tabelaPortais = $("#tabelaPortais").DataTable({
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
                    dados.objeto = "Portais"
                    dados.metodo = "getPortais"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "id", data: "id", visible: false },
                {
                    name: "logo",
                    data: "logo",
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return renderizarLogoPortalTabela(data)
                    }
                },
                {
                    name: "estado_conselho",
                    data: "estado_conselho",
                    render: function (data) {
                        return renderizarConselhoRegionalPortal(data)
                    }
                },
                { name: "cnpj", data: "cnpj", defaultContent: "" },
                {
                    name: "cidade",
                    data: null,
                    render: function (data, type, row) {
                        const cidade = escaparHtmlPortal(row?.cidade || "")
                        const estado = escaparHtmlPortal(row?.estado || "")

                        return cidade && estado ? `${cidade}/${estado}` : cidade || estado || "-"
                    }
                },
                { name: "email", data: "email", defaultContent: "" },
                {
                    name: "ativo",
                    data: "ativo",
                    render: function (data) {
                        return renderizarAtivoPortal(data)
                    }
                },
                { name: "dt_inclusao", data: "dt_inclusao" },
                {
                    data: null,
                    className: "dt-center no-search all always-visible td-edicoes",
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const id = escaparHtmlPortal(row && row.id ? row.id : "")

                        return `
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-primary mr-1 btn-editar-portal" data-id="${id}" title="Editar portal">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        `
                    }
                },
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 2 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 3 },
            ],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaPortais.ajax.reload(null, false)
}


$(function () {
    renderizarTabelaPortais()
    let processandoPrimeiroCampoInvalido = false

    document.getElementById("formPortais")?.addEventListener("invalid", function (evento) {
        if (processandoPrimeiroCampoInvalido) {
            return
        }

        const aba = $(evento.target).closest(".tab-pane")

        if (aba.length) {
            processandoPrimeiroCampoInvalido = true

            $("#conteudoAbasFormularioPortal > .tab-pane").removeClass("active show")
            $("#abasFormularioPortal .nav-link").removeClass("active").attr("aria-selected", "false")
            aba.addClass("active show")
            $(`#abasFormularioPortal a[href="#${aba.attr("id")}"]`)
                .addClass("active")
                .attr("aria-selected", "true")

            window.setTimeout(function () {
                processandoPrimeiroCampoInvalido = false
            }, 0)
        }
    }, true)

    $("#cnpj").on("input", function () {
        this.value = formatarCnpjPortal(this.value)
    })

    $("#cep").on("input", function () {
        this.value = formatarCepPortal(this.value)
    })

    $("#telefone").on("input", function () {
        this.value = formatarTelefonePortal(this.value)
    })

    $("#estado").on("input", function () {
        this.value = String(this.value || "").replace(/[^A-Za-z]/g, "").slice(0, 2).toUpperCase()
    })

    $("#areaLogoPortal").on("click keydown", function (e) {
        if (e.type === "click" || e.key === "Enter" || e.key === " ") {
            e.preventDefault()
            $("#logo_portal").trigger("click")
        }
    })

    $("#logo_portal").on("change", function () {
        const arquivo = this.files && this.files[0] ? this.files[0] : null

        if (arquivo) {
            atualizarPreviewLogoPortal(arquivo)
        }
    })

    $("#botaoRemoverFundoLogoPortal").on("click", function () {
        if (!arquivoLogoPortal) {
            exibirMensagemBootstrap("Selecione uma imagem antes de remover o fundo.", "warning")
            return
        }

        const formData = new FormData()
        formData.append("objeto", "Portais")
        formData.append("metodo", "removerFundoLogoPortal")
        formData.append("logo_portal", arquivoLogoPortal, arquivoLogoPortal.name || "logo_portal.png")

        requestAjax(formData, function (resultado) {
            if (!resultado || resultado.tipo !== "success" || !resultado.imagem_base64) {
                return
            }

            const tipoArquivo = resultado.tipo_imagem || "image/png"
            const nomeArquivo = resultado.nome_arquivo || "logo_sem_fundo.png"
            const arquivoSemFundo = converterBase64ParaArquivoLogoPortal(resultado.imagem_base64, nomeArquivo, tipoArquivo)

            atribuirArquivoLogoPortal(arquivoSemFundo)
        })
    })

    $("#areaLogoPortal").on("dragenter dragover", function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).addClass("border-primary")
    })

    $("#areaLogoPortal").on("dragleave dragend drop", function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).removeClass("border-primary")
    })

    $("#areaLogoPortal").on("drop", function (e) {
        const arquivo = obterImagemTransferida(e.originalEvent.dataTransfer)

        if (arquivo) {
            atribuirArquivoLogoPortal(arquivo)
        }
    })

    $(document).on("paste", function (e) {
        const arquivo = obterImagemTransferida(e.originalEvent.clipboardData)

        if (arquivo) {
            atribuirArquivoLogoPortal(arquivo)
        }
    })

    $("#formPortais").on("submit", function (e) {
        e.preventDefault()

        const form = this
        const formData = new FormData(form)

        formData.delete("logo_portal")
        formData.set("estado_conselho", $("#estado_conselho").val() || "")

        if (arquivoLogoPortal) {
            formData.append("logo_portal", arquivoLogoPortal, arquivoLogoPortal.name || "logo_portal.png")
        }

        requestAjax(formData, function (resultado) {
            if (resultado === true || resultado?.tipo === "success" || resultado?.success) {
                form.reset()
                limparPreviewLogoPortal()
                definirModoFormularioPortal()
                tabelaPortais.ajax.reload(null, false)
            }
        })
    })

    $("#formPortais").on("reset", function () {
        definirModoFormularioPortal()
        $("#abaPortalTab").tab("show")
        window.setTimeout(limparPreviewLogoPortal, 0)
    })

    $(document).on("click", ".btn-editar-portal", function () {
        carregarPortalNoFormulario(obterDadosLinhaPortal(this))
    })

    $(document).on("click", ".btn-remover-portal", function () {
        exibirMensagemBootstrap("Remoção ainda não implementada no backend.", "warning")
    })
})
