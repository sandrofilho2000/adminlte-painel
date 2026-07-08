
var tabelaPortaisCrefs;
var arquivoLogoPortal = null;

function atualizarPreviewLogoPortal(arquivo) {
    if (!arquivo || !arquivo.type || !arquivo.type.startsWith("image/")) {
        exibirMensagemBootstrap("Selecione uma imagem valida.", "danger")
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

function renderizarTabelaPortaisCrefs(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaPortaisCrefs")) {
        tabelaPortaisCrefs = $("#tabelaPortaisCrefs").DataTable({
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
                    dados.objeto = "PortaisCrefs"
                    dados.metodo = "getPortaisCrefs"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "id", data: "id", visible: false },
                { name: "estado_conselho", data: "estado_conselho" },
                { name: "ativo", data: "ativo" },
                { name: "dt_inclusao", data: "dt_inclusao" },
                { data: null, render: function(data, type, row){
                    return `
                        <div class="teste"></div>
                    `
                } },
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 3 },
                { responsivePriority: 2, targets: 1 },
            ],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaPortaisCrefs.ajax.reload(null, false)
}


$(function () {
    renderizarTabelaPortaisCrefs()

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

        requestAjax(formData, function (resultado) {
            if (resultado === true || resultado?.tipo === "success" || resultado?.success) {
                form.reset()
                limparPreviewLogoPortal()
                tabelaPortaisCrefs.ajax.reload(null, false)
            }
        })
    })

    $("#formPortais").on("reset", function () {
        window.setTimeout(limparPreviewLogoPortal, 0)
    })
})
