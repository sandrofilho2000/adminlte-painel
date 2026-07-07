<?php
    Controller::setPageTitle("Usuários");
    Controller::setFileStyle("https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css");
    Controller::setFileStyle("https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css");
    Controller::setFileJavascript("https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js");
    Controller::setFileJavascript("https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js");
    Controller::setFileJavascript("https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js");
    Controller::setFileJavascript("https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js");
    Controller::setFileJavascript("/admin/usuarios/js/main.js");
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Usuários</h3>
    </div>
    <div class="card-body">
        <table id="tabelaUsuarios" class="table table-striped table-bordered responsive" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Criado em</th>
                    <th>Ativo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
