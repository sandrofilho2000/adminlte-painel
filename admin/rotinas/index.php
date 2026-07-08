<?php

use Classes\Rotinas;
use Classes\RotinasConfig;
use Classes\Icones;

Controller::setPageTitle("Rotinas");
Controller::setFileJavascript("/admin/rotinas/js/main.js?v=$v");
Controller::setFileStyle("/admin/rotinas/css/styles.css?v=$v");

$objetoRotinas = new Rotinas();
$rotinas = $objetoRotinas->getRotinas();
$icones = (new Icones())->getIcones();

$ultimoCodigo = str_pad(RotinasConfig::obterUltimoCodigo(), 5, '0', STR_PAD_LEFT); ?>

<div class="card card-primary" id="cartaoFormularioRotina">
  <div class="card-header d-flex align-items-center">
    <h3 class="card-title" id="tituloFormularioRotina">Nova rotina</h3>
    <span class="badge badge-light ml-auto" id="indicadorModoFormulario">
      <i class="fas fa-plus-circle mr-1"></i> Modo de criação
    </span>
  </div>

  <form id="formRotinas" method="post" action="#">
    <div class="card-body">
      <input type="hidden" name="objeto" value="Rotinas">
      <input type="hidden" name="metodo" value="criaRotina">
      <input type="hidden" name="id" value="">

      <div class="row">
        <div class="col-md-5">
          <div class="form-group">
            <label for="rotinaDescricao">Descrição</label>
            <input type="text" class="form-control" id="rotinaDescricao" name="Descricao" placeholder="Ex.: Gestão de chamados">
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label for="rotinaPai">Rotina pai</label>
            <select class="form-control" id="rotinaPai" name="id_pai">
              <option value="">Nenhuma</option>
              <?php foreach ($rotinas as $rotina): ?>
                <option value="<?= (int) $rotina['id'] ?>">
                  (<?= htmlspecialchars((string) $rotina['Rotina'], ENT_QUOTES, 'UTF-8') ?>)
                  <?= htmlspecialchars((string) ($rotina['Descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
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
        <div class="col-md-8">
          <div class="form-group">
            <label for="rotinaRota">Rota</label>
            <input type="text" class="form-control" id="rotinaRota" name="rota" maxlength="255" placeholder="/admin/rotinas">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="rotinaIcone">Ícone</label>
            <select class="form-control" id="rotinaIcone" name="icon">
              <option value=""></option>
              <?php foreach ($icones as $icone): ?>
                <?php
                $classesIcone = (string) ($icone->classes ?? '');
                $nomeIcone = (string) ($icone->nome ?? '');

                if ($classesIcone === '' || strlen($classesIcone) > 50) {
                  continue;
                }
                ?>
                <option
                  value="<?= htmlspecialchars($classesIcone, ENT_QUOTES, 'UTF-8') ?>"
                  data-classes="<?= htmlspecialchars($classesIcone, ENT_QUOTES, 'UTF-8') ?>"
                  data-nome="<?= htmlspecialchars($nomeIcone, ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($nomeIcone . ' — ' . $classesIcone, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="tipo_sistema">Tipo de sistema</label>
            <select class="form-control" id="tipo_sistema" name="tipo_sistema" required>
              <option value="" selected disabled>Selecione...</option>
              <option value="portal">Portal</option>
              <option value="site">Site</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-4">
          <div class="opcao-rotina mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="rotinaStatus" name="status" value="1" checked>
              <label class="custom-control-label" for="rotinaStatus">Rotina ativa</label>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="opcao-rotina mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="rotinaExibirMenu" name="exibir_menu" value="1" checked>
              <label class="custom-control-label" for="rotinaExibirMenu">Exibir no menu</label>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="opcao-rotina mb-3">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="rotinaManutencao" name="em_manutencao" value="1">
              <label class="custom-control-label" for="rotinaManutencao">Em manutenção</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <button type="reset" class="btn btn-secondary mr-2" id="botaoLimparRotina">Limpar</button>
      <button type="submit" class="btn btn-primary" id="botaoSalvarRotina">
        <i class="fas fa-plus mr-1"></i> Criar rotina
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Rotinas cadastradas</h3>
  </div>
  <div class="card-body">
    <table id="tabelaRotinas" class="table table-striped table-bordered responsive nowrap" style="width:100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Rotina</th>
          <th>Descrição</th>
          <th>Sistema</th>
          <th>Rota</th>
          <th>Ícone</th>
          <th>Pai</th>
          <th>Status</th>
          <th>Manutenção</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>