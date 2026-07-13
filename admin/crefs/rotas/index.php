<?php

use Classes\Rotas;
use Classes\Portais;
use Classes\ConselhosRegionais;
use Classes\RotinasConfig;

Controller::setPermissao("00008");
Controller::setPageTitle("Rotas do Site");
Controller::setApenasConfef(true);
Controller::setFileJavascript("/admin/crefs/rotas/js/main.js?v=$v");
Controller::setFileStyle("/admin/crefs/rotas/css/styles.css?v=$v");
$rotas = new Rotas();
$rotas = $rotas->getRotas(true);

$portais = new Portais();
$portais = $portais->getPortais();

$ultimoCodigo = str_pad(RotinasConfig::obterUltimoCodigo(), 5, '0', STR_PAD_LEFT); ?>
?>


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
                            <i class="fas fa-plus-circle mr-1"></i> Modo de criação
                        </span>
                    </div>

                    <form id="formRotas" method="post" action="#">
                        <div class="card-body">
                            <input type="hidden" name="objeto" value="Rotas">
                            <input type="hidden" name="metodo" value="criaRota">
                            <input type="hidden" name="id" value="">

                            <div class="row linha-campos-rota">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="rotaNome">Nome</label>
                                        <input type="text" class="form-control" id="rotaNome" name="nome" maxlength="120" placeholder="Ex.: Portal CREF1/RJ">
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="rotaUrl">URL</label>
                                        <input type="text" class="form-control" id="rotaUrl" name="url" maxlength="255" placeholder="caminho/do/site/">
                                        <small class="form-text text-muted">
                                            Rota final
                                            <strong>https://(UF do CREF).confef.org.br/</strong><span id="previewRotaPai" class="text-info font-italic"></span><span id="previewRotaDigitada" class="text-primary font-weight-bold"></span>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="rotinaCodigo">Código</label>
                                        <input type="text" class="form-control" id="rotinaCodigo" name="codigo" maxlength="45" placeholder="<?php echo $ultimoCodigo; ?>" placeholder_original="<?php echo $ultimoCodigo; ?>" readonly disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-1">
                                    <div class="form-group controle-ativo-rota mb-md-3 w-100">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="rotaAtivo" name="ativo" value="1" checked>
                                            <label class="custom-control-label" for="rotaAtivo">Ativa</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-11">
                                    <div class="form-group">
                                        <label for="rotaPai">Rota pai</label>
                                        <select class="form-control" id="rotaPai" name="id_pai">
                                            <option value="">Nenhuma</option>
                                            <?php foreach ($rotas as $rotaPai): ?>
                                                <?php
                                                $rotaPai = (array) $rotaPai;
                                                $idRotaPai = (string) ($rotaPai['id'] ?? '');
                                                $nomeRotaPai = (string) ($rotaPai['nome'] ?? '');
                                                $urlRotaPai = (string) ($rotaPai['url'] ?? '');
                                                $nivelRotaPai = (int) ($rotaPai['nivel'] ?? 0);
                                                $rotaFinalPai = (string) ($rotaPai['rota_ascendentes'] ?? '') . $urlRotaPai;
                                                $rotuloRotaPai = trim($nomeRotaPai) !== '' ? $nomeRotaPai : $urlRotaPai;

                                                if ($idRotaPai === '' || $rotuloRotaPai === '') {
                                                    continue;
                                                }
                                                ?>
                                                <option
                                                    value="<?= htmlspecialchars($idRotaPai, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nivel="<?= htmlspecialchars((string) $nivelRotaPai, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-rota-final="<?= htmlspecialchars($rotaFinalPai, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($rotuloRotaPai, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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
                                    <th>Id</th>
                                    <th>Nome</th>
                                    <th>Url</th>
                                    <th>Código</th>
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
                                    <?php foreach ($portais as $portal): ?>
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

                    <div class="lista-atribuicao-rotas">
                        <?php foreach ($rotas as $rota): ?>
                            <?php
                            $rota = (array) $rota;
                            $idRota = (string) ($rota['id'] ?? '');
                            $nomeRota = (string) ($rota['nome'] ?? '');
                            $urlRota = (string) ($rota['url'] ?? '');
                            $nivelRota = (int) ($rota['nivel'] ?? 0);
                            $rotaFinal = (string) ($rota['rota_ascendentes'] ?? '') . $urlRota;
                            $rotuloRota = trim($nomeRota) !== '' ? $nomeRota : $urlRota;

                            if ($idRota === '' || $rotuloRota === '') {
                                continue;
                            }

                            $idCheckbox = 'rota_atribuicao_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $idRota);
                            ?>
                            <div class="item-atribuicao-rota">
                                <div class="custom-control custom-checkbox mb-2" style="padding-left: <?= 1.5 + ($nivelRota * 1.25) ?>rem;">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input checkbox-rota-atribuicao"
                                        id="<?= htmlspecialchars($idCheckbox, ENT_QUOTES, 'UTF-8') ?>"
                                        name="rotas[]"
                                        value="<?= htmlspecialchars($idRota, ENT_QUOTES, 'UTF-8') ?>"
                                        data-id="<?= htmlspecialchars($idRota, ENT_QUOTES, 'UTF-8') ?>">
                                    <label class="custom-control-label" for="<?= htmlspecialchars($idCheckbox, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="d-block"><?= htmlspecialchars($rotuloRota, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (trim($rotaFinal) !== ''): ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($rotaFinal, ENT_QUOTES, 'UTF-8') ?></small>
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