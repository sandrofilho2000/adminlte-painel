const $formRotinas = $("#formRotinas")
const language_url = "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
let tabelaRotinas = null

function renderTabelaRotinas(page = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaRotinas")) {
        tabelaRotinas = $("#tabelaRotinas").DataTable({
            serverSide: true,
            pageLength: 20,
            lengthChange: false,
            order: [[0, "desc"]],
            displayStart: (page - 1) * 20,
            ajax: {
                url: "/adminlte-painel/controle/controle_default.php",
                type: "POST",
                data: function (d) {
                    d.objeto = "Rotinas"
                    d.metodo = "getRotinas"
                    d.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                {
                    name: "r.id",
                    data: "id"
                },
                {
                    name: "r.nome",
                    data: "nome"
                },
                {
                    name: "r.descricao",
                    data: "descricao"
                },
                {
                    name: "r.url",
                    data: "url"
                },
                {
                    name: "r.icone",
                    data: "icone",
                    render: function (data) {
                        if (!data) {
                            return ""
                        }

                        return `<i class="${data} mr-1"></i> ${data}`
                    }
                },
                {
                    name: "r.rotina_pai_id",
                    data: "rotina_pai_id"
                },
                {
                    name: "r.ordem",
                    data: "ordem"
                },
                {
                    name: "r.ativo",
                    data: "ativo",
                    render: function (data) {
                        return Number(data) === 1
                            ? '<span class="badge badge-success">Sim</span>'
                            : '<span class="badge badge-secondary">Nao</span>'
                    }
                }
            ],
            language: { url: language_url }
        })

        return
    }

    tabelaRotinas
        .ajax.reload()
        .columns.adjust()

    if (tabelaRotinas.responsive) {
        tabelaRotinas.responsive.recalc()
    }
}

$("#formRotinas").on("submit", (e) => {
    e.preventDefault()
    gravarFrm("formRotinas")
})

$(function () {
    renderTabelaRotinas()
})
