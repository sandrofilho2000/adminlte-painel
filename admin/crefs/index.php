<?php
    Controller::setPermissao("00014");
    Controller::setPageTitle("Portais de CREF's");
    Controller::setApenasConfef(true);
    Controller::setFileJavascript("/admin/crefs/js/main.js?v=$v");
?>  

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Portais cadastrados</h3>
    </div>
    <div class="card-body">
        <table id="tabelaPortaisCrefs" class="table table-striped table-bordered responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CREF</th>
                    <th>Ativo</th>
                    <th>Data de cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" id="salvarAlteracoesRotasSite">
            <i class="fas fa-save mr-1"></i> Salvar alteracoes
        </button>
    </div>
</div>