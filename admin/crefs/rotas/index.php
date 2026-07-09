<?php

use Classes\Rotas;
use Classes\Portais;
use Classes\ConselhosRegionais;

Controller::setPermissao("00008");
Controller::setPageTitle("Rotas do Site");
Controller::setApenasConfef(true);
Controller::setFileJavascript("/admin/crefs/rotas/js/main.js?v=$v");


$rotas = new Rotas() ;
$rotas = $rotas->getRotas();


$portais = new Portais();
$portais = $portais->getPortais();
?>

<style>
    #formRotas .linha-campos-rota {
        align-items: flex-start;
    }

    #formRotas .controle-ativo-rota {
        padding-top: 31px;
    }

    #abasRotasSite .nav-link {
        color: rgba(255, 255, 255, 0.88);
    }

    #abasRotasSite .nav-link.active {
        color: #495057;
    }
</style>

<div class="card card-primary card-outline card-outline-tabs">
    <div class="card-header p-0 border-bottom-0 bg-primary">
        <ul class="nav nav-tabs" id="abasRotasSite" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="abaListarRotasTab" data-toggle="pill" href="#abaListarRotas" role="tab" aria-controls="abaListarRotas" aria-selected="true">Listar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="abaAtribuirRotasTab" data-toggle="pill" href="#abaAtribuirRotas" role="tab" aria-controls="abaAtribuirRotas" aria-selected="false">Atribuir</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="conteudoAbasRotasSite">
            <div class="tab-pane fade show active" id="abaListarRotas" role="tabpanel" aria-labelledby="abaListarRotasTab">
                <div class="card card-primary" id="cartaoFormularioRota">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title" id="tituloFormularioRota">Nova rota</h3>
                        <span class="badge badge-light ml-auto" id="indicadorModoFormularioRota">
                            <i class="fas fa-plus-circle mr-1"></i> Modo de criacao
                        </span>
                    </div>

                    <form id="formRotas" method="post" action="#">
                        <div class="card-body">
                            <input type="hidden" name="objeto" value="Rotas">
                            <input type="hidden" name="metodo" value="criaRota">
                            <input type="hidden" name="id" value="">

                            <div class="row linha-campos-rota">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="rotaNome">Nome</label>
                                        <input type="text" class="form-control" id="rotaNome" name="nome" maxlength="120" placeholder="Ex.: Portal CREF1/RJ">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rotaUrl">URL</label>
                                        <input type="text" class="form-control" id="rotaUrl" name="url" maxlength="255" placeholder="caminho/do/site/">
                                        <small class="form-text text-muted">
                                            Esta rota sera adicionada como complemento de <strong>https://(UF do CREF).confef.org.br/</strong>
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group controle-ativo-rota mb-md-3 w-100">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="rotaAtivo" name="ativo" value="1" checked>
                                            <label class="custom-control-label" for="rotaAtivo">Ativa</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="reset" class="btn btn-secondary mr-2" id="botaoLimparRota">Limpar</button>
                            <button type="submit" class="btn btn-primary" id="botaoSalvarRota">
                                <i class="fas fa-plus mr-1"></i> Criar rota
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Rotas cadastradas</h3>
                    </div>
                    <div class="card-body">
                        <table id="tabelaRotas" class="table table-striped table-bordered responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Url</th>
                                    <th>Ativa</th>
                                    <th>Acoes</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="abaAtribuirRotas" role="tabpanel" aria-labelledby="abaAtribuirRotasTab">
                <form id="formAtribuirRotas" method="post" action="#">
                    <div class="row linha-campos-rota">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="id_portal">Portal</label>
                                <select name="portal" id="id_portal" class="form-control">
                                    <option selected disabled>Selecione...</option>
                                    <?php foreach($portais as $portal): ?>
                                        <option value="<?= $portal->id ?>"><?= ConselhosRegionais::obterLegenda($portal->estado_conselho) ?> </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="rota_atribuicao_todos"
                            name="todas_rotas"
                            value="1">
                        <label class="custom-control-label" for="rota_atribuicao_todos">Todos</label>
                    </div>

                    <div class="row">
                        <?php foreach ($rotas as $rota): ?>
                            <?php
                                $rota = (array) $rota;
                                $idRota = (string) ($rota['id'] ?? '');
                                $nomeRota = (string) ($rota['nome'] ?? '');
                                $urlRota = (string) ($rota['url'] ?? '');
                                $rotuloRota = trim($nomeRota) !== '' ? $nomeRota : $urlRota;

                                if ($idRota === '' || $rotuloRota === '') {
                                    continue;
                                }

                                $idCheckbox = 'rota_atribuicao_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $idRota);
                            ?>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input checkbox-rota-atribuicao"
                                        id="<?= htmlspecialchars($idCheckbox, ENT_QUOTES, 'UTF-8') ?>"
                                        name="rotas[]"
                                        value="<?= htmlspecialchars($idRota, ENT_QUOTES, 'UTF-8') ?>"
                                        data-id="<?= htmlspecialchars($idRota, ENT_QUOTES, 'UTF-8') ?>">
                                    <label class="custom-control-label" for="<?= htmlspecialchars($idCheckbox, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="d-block"><?= htmlspecialchars($rotuloRota, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (trim($urlRota) !== ''): ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($urlRota, ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3" id="botaoSalvarAtribuicaoRotas">
                        <i class="fas fa-save mr-1"></i> Salvar atribuicao
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
