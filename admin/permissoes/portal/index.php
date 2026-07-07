<?php

use Classes\Usuarios;
use Classes\Rotinas;

$usuarios = (new Usuarios())->getUsuarios();
$rotinas = (new Rotinas())->getRotinas();

Controller::setPageTitle("Permissões do Portal");
Controller::setFileJavascript("/admin/permissoes/portal/js/main.js?v=$v");

?>

<div class="card card-primary card-outline">
    <form id="formUsuarioPermissao" method="post" action="#">
        <input type="hidden" name="objeto" id="objeto" value="Persistemas">
        <input type="hidden" name="metodo" id="metodo" value="criaPersistemas">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_usuario">Usuário</label>
                        <select class="form-control select2" id="id_usuario" multiple name="Usuario" data-placeholder="Selecione um usuario">
                            <option value="" selected disabled>Selecione...</option>
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
                                    (<?= $rotina['tipo_sistema'] ?>) <?= htmlspecialchars((string) $rotina['Descricao'], ENT_QUOTES, 'UTF-8') ?> - <?= $rotina['Rotina'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-6 col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="permissaoLer" name="Consulta" value="1">
                        <label class="custom-control-label" for="permissaoLer">Ler</label>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="permissaoIncluir" name="Incluir" value="1">
                        <label class="custom-control-label" for="permissaoIncluir">Incluir</label>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="permissaoDeletar" name="Excluir" value="1">
                        <label class="custom-control-label" for="permissaoDeletar">Deletar</label>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="permissaoEditar" name="Alterar" value="1">
                        <label class="custom-control-label" for="permissaoEditar">Editar</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Salvar permissão
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</form>
</div>