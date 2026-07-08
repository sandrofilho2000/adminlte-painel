
var tabelaPortaisCrefs;

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
})