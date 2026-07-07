let tabelaUsuarios = null
const urlIdioma = "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"

function renderizarTabelaUsuarios(pagina = 1) {
    if (!$.fn.DataTable.isDataTable("#tabelaUsuarios")) {
        tabelaUsuarios = $("#tabelaUsuarios").DataTable({
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
                    dados.objeto = "Usuarios"
                    dados.metodo = "getUsuarios"
                    dados.aplicarPaginacaoNoResultado = 1
                }
            },
            columns: [
                { name: "u.id", data: "id" },
                { name: "u.apresentacao", data: "apresentacao" },
                { name: "u.email", data: "email" },
                { name: "u.criado_em", data: "criado_em" },
                {
                    name: "u.status",
                    data: "status",
                    render: function (valor) {
                        return Number(valor) === 1
                            ? '<span class="badge badge-success">Ativo</span>'
                            : '<span class="badge badge-secondary">Inativo</span>'
                    }
                },
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 1 },
                { responsivePriority: 2, targets: 0 },
                { responsivePriority: 3, targets: 4 }
            ],
            language: { url: urlIdioma }
        })
        return
    }

    tabelaUsuarios.ajax.reload(null, false)
}


$(function () {
    renderizarTabelaUsuarios()
})
