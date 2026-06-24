<?php
Controller::setPageTitle("Rotinas");
Controller::setFileJavascript("/admin/rotinas/js/main.js")
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
                  required
                >
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label for="rotinaIcone">Icone</label>
                <input
                  type="text"
                  class="form-control"
                  id="rotinaIcone"
                  name="icone"
                  maxlength="100"
                  placeholder="Ex.: fas fa-tasks"
                >
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
              placeholder="Descreva a finalidade desta rotina"
            ></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="rotinaUrl">URL</label>
                <input
                  type="text"
                  class="form-control"
                  id="rotinaUrl"
                  name="url"
                  maxlength="255"
                  placeholder="/adminlte-painel/admin/rotinas/"
                >
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="rotinaPaiId">Rotina pai</label>
                <input
                  type="number"
                  class="form-control"
                  id="rotinaPaiId"
                  name="rotina_pai_id"
                  min="0"
                  step="1"
                  placeholder="ID da rotina pai"
                >
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="rotinaOrdem">Ordem</label>
                <input
                  type="number"
                  class="form-control"
                  id="rotinaOrdem"
                  name="ordem"
                  min="0"
                  step="1"
                  placeholder="0"
                >
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
  </div>
</section>
