<?php

use Classes\Usuarios;
use Classes\Rotinas;

$usuarios = (new Usuarios())->getUsuarios();
$rotinas = (new Rotinas())->getRotinas();

Controller::setPermissao("00013");
Controller::setPageTitle("Atribuir Rotinas");
Controller::setFileJavascript("/admin/permissoes/atribuir/js/main.js?v=$v");

?>

<form id="formUsuarioPermissao" method="post" action="#">
    <div class="card card-primary card-outline">
        <input type="hidden" name="objeto" id="objeto" value="Persistemas">
        <input type="hidden" name="metodo" id="metodo" value="criaPersistemas">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_usuario">Usuário</label>
                        <select class="form-control select2" id="id_usuario" multiple name="Usuario" data-placeholder="Selecione um usuário">
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= (int) $usuario['id'] ?>">
                                    (<?= $usuario['estado_conselho'] ?>) <?= htmlspecialchars((string) $usuario['apresentacao'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_permissao">Permissão</label>
                        <select class="form-control" id="id_permissao" name="Rotina" data-placeholder="Selecione uma permissão">
                            <option value="" selected disabled>Selecione...</option>
                            <?php foreach ($rotinas as $rotina): ?>
                                <option value="<?= $rotina['Rotina'] ?>">
                                    <?= htmlspecialchars((string) $rotina['Descricao'], ENT_QUOTES, 'UTF-8') ?> - <?= $rotina['Rotina'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="custom-control custom-switch mb-3 mb-md-0 mr-3">
                            <input type="checkbox" class="custom-control-input" id="permissaoLer" name="Consulta" value="1">
                            <label class="custom-control-label" for="permissaoLer">Ler</label>
                        </div>
                        <div class="custom-control custom-switch mb-3 mb-md-0 mr-3">
                            <input type="checkbox" class="custom-control-input" id="permissaoIncluir" name="Incluir" value="1">
                            <label class="custom-control-label" for="permissaoIncluir">Incluir</label>
                        </div>
                        <div class="custom-control custom-switch mb-3 mb-md-0 mr-3">
                            <input type="checkbox" class="custom-control-input" id="permissaoDeletar" name="Excluir" value="1">
                            <label class="custom-control-label" for="permissaoDeletar">Deletar</label>
                        </div>
                        <div class="custom-control custom-switch mb-3 mb-md-0">
                            <input type="checkbox" class="custom-control-input" id="permissaoEditar" name="Alterar" value="1">
                            <label class="custom-control-label" for="permissaoEditar">Editar</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Salvar permissão
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Permissões cadastradas</h3>
    </div>
    <div class="card-body">
        <table id="tabelaPermissoes" class="table table-striped table-bordered responsive nowrap" style="width:100%">
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
        <button type="button" class="btn btn-primary" id="salvarAlteracoesPermissoes">
            <i class="fas fa-save mr-1"></i> Salvar alterações
        </button>
    </div>
</div>

<div class="modal fade" id="modalExcluirPermissao" tabindex="-1" role="dialog" aria-labelledby="modalExcluirPermissaoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 id="modalExcluirPermissaoTitulo">Excluir permissão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Deseja realmente excluir esta permissão?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarExcluirPermissao">
                    <i class="fas fa-trash mr-1"></i> Excluir
                </button>
            </div>
        </div>
    </div>
</div>
