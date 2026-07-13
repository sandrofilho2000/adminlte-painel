<?php
require_once __DIR__ . '/../../includes/config.php';
require_once BASE_PATH . '/vendor/autoload.php';
include_once ADMIN_PATH . '/includes/session_manager.php';
require_once ADMIN_PATH . '/includes/functions.php';

$tem_permissao = verificaPermissao('00108');
if (!$tem_permissao || $_SESSION['estado_conselho'] != 'BR') {
  header("location: /adminlte-painel/admin/");
  exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel - Logs</title>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>


  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css"> -->

  <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">


  <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">

  <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.css">

  <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/datatables_news/datatables.min.css">

  <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/jquery-confirm/jquery-confirm.min.css">
  <link rel="stylesheet" href="/adminlte-painel/admin/content/logs/css/styles.css">
  <link rel="stylesheet" href="/adminlte-painel/src/css/style.css">

</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">

    <!-- Navbar -->
    <?php require($_SERVER['DOCUMENT_ROOT'] . "/adminlte-painel/admin/includes/navbar.php"); ?>

    <!-- Sidebar -->
    <?php require($_SERVER['DOCUMENT_ROOT'] . "/adminlte-painel/admin/includes/sidebar.php"); ?>

    <!-- Content Wrapper -->

    <div class="content-wrapper p-4">
      <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
          <h1>Logs</h1>
        </div>
      </section>
      <section class="content mt-3">
        <div class="card card-primary mt-4">
          <div class="card-header p-0">
            <ul class="nav nav-tabs" id="logsTab" role="tablist" style="border-bottom: unset;">
              <li class="nav-item">
                <a class="nav-link active" id="tab-erros-link" data-toggle="tab" href="#tab-erros" role="tab" aria-controls="tab-erros" aria-selected="true">
                  Erros
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="tab-regras-link" data-toggle="tab" href="#tab-regras" role="tab" aria-controls="tab-regras" aria-selected="false">
                  Regras de Exclusao
                </a>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="logsTabContent">
              <div class="tab-pane fade show active" id="tab-erros" role="tabpanel" aria-labelledby="tab-erros-link">
                <div id="logsAcoesWrapper" class="d-flex align-items-center d-none" style="gap: 4px">
                  <button type="button" class="btn btn-primary btn-sm" id="btnAtualizarLogs" title="Atualizar logs" aria-label="Atualizar logs">
                    <i class="fas fa-sync-alt"></i>
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm d-none" id="btnVerLogsIgnorados">
                    Ver logs ignorados
                  </button>
                </div>
                <table id="tabelaLogs" class="table table-striped table-bordered responsive dataTable no-footer collapsed" style="width:100%">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Data</th>
                      <th>Mensagem</th>
                      <th>Usuário</th>
                      <th>Estado conselho</th>
                      <th>Arquivo</th>
                      <th>Linha</th>
                      <th>Payload</th>
                      <th>Objeto e metodo</th>
                      <th>Erro</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>

              <div class="tab-pane fade" id="tab-regras" role="tabpanel" aria-labelledby="tab-regras-link">
                <form id="formRegrasExclusaoLogs" class="pt-2">
                  <input type="hidden" name="id" id="regraId" value="" />
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="regraCampo">Campo alvo</label>
                        <select class="form-control" id="regraCampo" name="campo" required>
                          <option value="">Selecione</option>
                          <option value="mensagem">Mensagem</option>
                          <option value="objeto_metodo">Objeto e metodo</option>
                          <option value="payload">Payload</option>
                          <option value="trace">Trace</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="regraOperador">Operador</label>
                        <select class="form-control" id="regraOperador" name="operador" required>
                          <option value="">Selecione</option>
                          <option value="LIKE">Contem</option>
                          <option value="IGUAL">Igual a</option>
                          <option value="LIKE_START">Inicia com</option>
                          <option value="LIKE_END">Termina com</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="regraValor">Valor da regra</label>
                    <textarea class="form-control" id="regraValor" name="valor" rows="3" maxlength="500" placeholder="Valor usado para filtrar o log" required></textarea>
                  </div>

                  <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="regraAtivo" name="ativo" value="1" checked>
                    <label class="form-check-label" for="regraAtivo">Regra ativa</label>
                  </div>

                  <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary mr-2" id="btnLimparRegraExclusao">Limpar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                  </div>

                  <div id="regrasExclusaoFeedback" class="mt-3 d-none"></div>
                </form>
                <table id="tabelaLogsIgnorar" class="table table-striped table-bordered responsive dataTable no-footer collapsed" style="width:100%">
                  <thead>
                    <tr>
                      <th>Campo</th>
                      <th>Operador</th>
                      <th>Valor</th>
                      <th>Ativo</th>
                      <th>Criado em</th>
                      <th>Criado por</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <?php require($_SERVER['DOCUMENT_ROOT'] . "/adminlte-painel/admin/includes/footer.php"); ?>
  <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>
  <input type="hidden" id="logged_id_user" name="logged_id_user" value="<?= $_SESSION['id'] ?>" />
  <input type="hidden" id="logged_id_apresentacao" name="logged_id_apresentacao" value="<?= $_SESSION['nome'] ?>" />
  <!-- Modal -->


  <!-- Scripts -->
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/datatables_news/datatables.min.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/jquery-validation/jquery.validate.min.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/jquery-validation/additional-methods.min.js"></script>
  <script src="https://cdn.rawgit.com/mgalante/jquery.redirect/master/jquery.redirect.js"></script>
  <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/jquery-confirm/jquery-confirm.min.js"></script>
  <script src="https://adminlte.io/themes/v3/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://cdn.datatables.net/plug-ins/1.10.21/sorting/datetime-moment.js"></script>

  <script src="/adminlte-painel/src/functions.js?v=<?= $v ?>"></script>
  <script src="/adminlte-painel/src/theme-toggle.js"></script>
  <script src="/adminlte-painel/admin/content/logs/js/main.js"></script>
</body>

</html>
