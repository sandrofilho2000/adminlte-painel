<?php

use Classes\Rotinas;
use Classes\RotinasConfig;

Controller::setPageTitle("Rotinas");
Controller::setFileStyle("https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css");
Controller::setFileStyle("https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css");
Controller::setFileJavascript("https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js");
Controller::setFileJavascript("https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js");
Controller::setFileJavascript("https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js");
Controller::setFileJavascript("https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js");
Controller::setFileJavascript("/admin/rotinas/js/main.js");

$last_code = RotinasConfig::getLastCode();
$last_code = sprintf('%05d', $last_code);

$Rotinas = new Rotinas();
$rotinas = $Rotinas->getRotinas();

?>

<section class="content">
  <div class="container-fluid">
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Cadastro de rotina</h3>
      </div>

      <form id="formRotinas" method="post" action="#">
        <div class="card-body">
          <input type="hidden" id="rotinaId" name="id" value="">
          <input type="hidden" id="objeto" name="objeto" value="Rotinas">
          <input type="hidden" id="metodo" name="metodo" value="criaRotina">

          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label for="rotinaNome">Nome</label>
                <input
                  type="text"
                  class="form-control"
                  id="rotinaNome"
                  name="nome"
                  maxlength="150"
                  placeholder="Ex.: Chamados"
                  required>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label for="rotinaIcone">Icone</label>
                <input
                  type="text"
                  class="form-control"
                  id="rotinaIcone"
                  name="icone"
                  maxlength="100"
                  placeholder="Ex.: fas fa-tasks">
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label for="rotinaIcone">Código</label>
                <input
                  type="text"
                  class="form-control"
                  id="lastIcone"
                  name="code"
                  placeholder="<?= $last_code ?>"
                  readonly
                  disabled>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="rotinaDescricao">Descricao</label>
            <textarea
              class="form-control"
              id="rotinaDescricao"
              name="descricao"
              rows="4"
              placeholder="Descreva a finalidade desta rotina"></textarea>
          </div>

          <div class="row">
            <div class="col-md-9">
              <div class="form-group">
                <label for="rotinaUrl">URL</label>
                <input
                  type="text"
                  class="form-control"
                  id="rotinaUrl"
                  name="url"
                  maxlength="255"
                  placeholder="/adminlte-painel/admin/rotinas/">
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="rotinaOrdem">Tipo</label>
                <select class="form-control" name="tipo">
                  <option disabled>Selecione...</option>
                  <option value="pagina">Página</option>
                  <option value="permissao">Permissão</option>
                  <option value="grupo">Grupo</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label for="rotinaPaiId">Rotina pai</label>
                <select class="form-control" name="rotina_pai_id">
                  <option>Selecione... (Nenhum)</option>
                  <?php foreach ($rotinas as $rotina): ?>
                    <option value="<?= $rotina["id"] ?>">(<?= $rotina["code"] ?>) <?= $rotina["nome"] ?></option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="rotinaAtivo" name="ativo" value="1" checked>
              <label class="custom-control-label" for="rotinaAtivo">Rotina ativa</label>
            </div>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
          <button type="reset" class="btn btn-secondary mr-2">Limpar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Rotinas cadastradas</h3>
      </div>

      <div class="card-body">
        <table id="tabelaRotinas" class="table table-striped table-bordered responsive dataTable no-footer collapsed" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Código</th>
              <th>Nome</th>
              <th>Descrição</th>
              <th>URL</th>
              <th>Icone</th>
              <th>Rotina pai</th>
              <th>Tipo</th>
              <th>Ativo</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>