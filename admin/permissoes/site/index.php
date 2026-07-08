<?php
    Controller::setPermissao("00008");
    Controller::setPageTitle("Rotas do Site");
    Controller::setApenasConfef(true)

?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Rotas cadastradas</h3>
    </div>
    <div class="card-body">
        <table id="tabelaRotasSite" class="table table-striped table-bordered responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>CREF</th>
                    <th>Rotina</th>
                    <th>Ler</th>
                    <th>Incluir</th>
                    <th>Deletar</th>
                    <th>Editar</th>
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