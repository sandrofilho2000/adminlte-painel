<?php
require_once __DIR__ . '/../../includes/config.php';
include_once ADMIN_PATH . '/includes/session_manager.php';
include_once ADMIN_PATH . '/includes/functions.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /webconfef/admin/");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>efControl - Notificações</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="/webconfef/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/webconfef/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/solid.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.9.1/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/webconfef/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/webconfef/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" type="text/css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="/webconfef/templates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css" integrity="sha512-nNlU0WK2QfKsuEmdcTwkeh+lhGs6uyOxuUs+n+0oXSYDok5qy0EI0lt01ZynHq6+p/tbgpZ7P+yUb+r71wqdXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php require ADMIN_PATH . '/includes/navbar.php'; ?>
        <?php require ADMIN_PATH . '/includes/sidebar.php'; ?>

        <div class="content-wrapper p-4">
            <section class="content-header">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1>Notificações</h1>
                </div>
            </section>

            <section class="content mt-3">
                <div class="card card-primary mt-4">
                    <div class="card-body">
                        <div id="notificacoesAcoesWrapper" class="d-flex align-items-center d-none mb-3" style="gap: 4px">
                            <button type="button" class="btn btn-primary btn-sm" id="btnAtualizarNotificacoes" title="Atualizar notificações" aria-label="Atualizar notificações">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button type="button" class="btn btn-success btn-sm d-none" id="btnLerTodasNotificacoes">
                                Ler todas
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm d-none" id="btnVerNotificacoesNaoLidas">
                                Ver notificações não lidas
                            </button>
                        </div>

                        <table id="tblNotificacoes" class="table table-striped table-bordered responsive dataTable no-footer collapsed" style="width:100%; table-layout: fixed;">
                            <colgroup>
                                <col>
                                <col style="width: 180px;">
                                <col style="width: 50px;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Mensagem</th>
                                    <th>Data</th>
                                    <th>Ler</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <?php require ADMIN_PATH . '/includes/footer.php'; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="/webconfef/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
    <script src="/webconfef/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/webconfef/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.js"></script>
    <script src="/webconfef/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
    <script src="https://cdn.rawgit.com/mgalante/jquery.redirect/master/jquery.redirect.js"></script>
    <script src="https://cdn.es.gov.br/scripts/jquery/jquery-maskedinput/1.4.1/jquery.maskedinput-1.4.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="/webconfef/templates/AdminLTE-3.2.0/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.21/sorting/datetime-moment.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="/webconfef/src/functions.js?v=<?= $v ?>"></script>
    <script src="/adminlte-painel/src/theme-toggle.js"></script>
    <script src="/webconfef/admin/content/notificacoes/js/main.js?v=<?= $v ?>"></script>
</body>

</html>
