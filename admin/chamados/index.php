<?php
// Initialize the session

require_once __DIR__ .      '/../../includes/config.php';
include_once ADMIN_PATH . '/includes/session_manager.php';
include_once ADMIN_PATH . '/includes/functions.php';

use Classes\Chamados;
use Classes\Persistemas;

//Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || (search($_SESSION["Permissoes"][search($_SESSION["Permissoes"], '00072', 'Rotina')], '1', 'Consulta') == -1)) {
    header("location: /adminlte-painel/admin/");
    exit;
}

$estado_conselho = $_SESSION['estado_conselho'];
$usuarioAtual = trim((string)($_SESSION['nome'] ?? $_SESSION['apresentacao'] ?? $_SESSION['usuario'] ?? 'Usuário'));

$chamados = new Chamados();
$atributos_editaveis = $chamados->getAtributosEditaveis();

$persistemas = new Persistemas();
$meus_modulos = $persistemas->getMeusModulos();

$Usuarios = new Classes\Usuarios();
$Usuarios->habilitarIgnorarPermissao();
$tecnicos_ti = $Usuarios->getUsuariosPorSetor(2);
$Usuarios->desabilitarIgnorarPermissao();

$sou_de_informatica = trim((string) ($_SESSION['setor'] ?? '')) === 'Coordenadoria de Informática e Tecnologia';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>efControl - Chamados</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/solid.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.9.1/font/bootstrap-icons.min.css" rel="stylesheet" />
    <!-- Theme style -->
    <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">

    <!-- summernote -->
    <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">


    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" type="text/css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css" integrity="sha512-nNlU0WK2QfKsuEmdcTwkeh+lhGs6uyOxuUs+n+0oXSYDok5qy0EI0lt01ZynHq6+p/tbgpZ7P+yUb+r71wqdXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="/adminlte-painel/admin/content/chamados/css/styles.css">
    <link rel="stylesheet" href="/adminlte-painel/src/css/style.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <?php
        require ADMIN_PATH . '/includes/navbar.php';
        ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php
        require ADMIN_PATH . '/includes/sidebar.php';
        ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Sistema de Chamados e Suporte</h1>
                        </div>
                        <div class="col-sm-6">
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row mb-2 align-items-end">
                        <div class="col-12 <?php echo $sou_de_informatica ? 'col-md-4 col-lg-4' : 'col-md-8 col-lg-8'; ?>">
                            <div class="input-group kanban-search-bar">
                                <input type="text" class="form-control" id="kanbanSearchInput" placeholder="Pesquisar cards (título, descrição, responsável, observadores, atualizações)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="kanbanClearSearch" title="Limpar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php if ($sou_de_informatica): ?>
                            <div class="col-12 col-md-4 col-lg-4">
                                <div class="form-group mb-0">
                                    <label for="kanbanResponsavelFiltro" class="sr-only">Filtrar por responsável</label>
                                    <select class="form-control" id="kanbanResponsavelFiltro" title="Filtrar chamados por responsável">
                                        <option value="">Todos os responsáveis</option>
                                        <?php foreach ($tecnicos_ti as $tecnico): ?>
                                            <?php
                                            $idTecnico = trim((string) ($tecnico->id ?? ''));
                                            $nomeTecnico = trim((string) ($tecnico->apresentacao ?? ''));
                                            $cargoTecnico = trim((string) ($tecnico->nome_cargo ?? ''));

                                            if ($idTecnico === '' || $nomeTecnico === '') {
                                                continue;
                                            }

                                            $labelTecnico = $nomeTecnico;
                                            if ($cargoTecnico !== '') {
                                                $labelTecnico .= ' - ' . $cargoTecnico;
                                            }
                                            ?>
                                            <option
                                                value="<?= htmlspecialchars($idTecnico, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                                data-nome="<?= htmlspecialchars($nomeTecnico, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                            >
                                                <?= htmlspecialchars($labelTecnico, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__sem_responsavel__">Sem responsavel</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-6 col-md-2 col-lg-2">
                            <div class="form-group mb-0">
                                <label for="kanbanDataInicioFiltro" class="sr-only">Data inicial de criação</label>
                                <input type="date" class="form-control" id="kanbanDataInicioFiltro" title="Filtrar cards pela data inicial de criação">
                            </div>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <div class="form-group mb-0">
                                <label for="kanbanDataFimFiltro" class="sr-only">Data final de criação</label>
                                <input type="date" class="form-control" id="kanbanDataFimFiltro" title="Filtrar cards pela data final de criação">
                            </div>
                        </div>
                    </div>
                    <div id="kanbanNoSearchResults" class="alert alert-warning d-none py-2 px-3">
                        Nenhum card encontrado para os filtros informados.
                    </div>
                    <div class="kanban-board-wrapper kanban-board-loading">
                        <div class="kanban-board <?php echo $atributos_editaveis['criar_em_qualquer_coluna'] ? '' : 'no-grabbing' ?>">
                            <div class="card kanban-column" data-column="backlog">
                                <div class="card-header bg-info d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-white">Em fila</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="backlog">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-white kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" class="btn btn-outline-info btn-sm btn-block kanban-create-card" data-create-column="backlog">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>
                                    <div class="kanban-cards mt-2" id="col-backlog"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="andamento">
                                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-dark">Em andamento</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="andamento">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-dark kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-warning btn-sm btn-block kanban-create-card" data-create-column="andamento">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>
                                    <div class="kanban-cards mt-2" id="col-andamento"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="pausadas">
                                <div class="card-header bg-gray-dark d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-white">Pausados</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="pausadas">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-white kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-dark btn-sm btn-block kanban-create-card" data-create-column="pausadas">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>
                                    <div class="kanban-cards mt-2" id="col-pausadas"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="em_validacao">
                                <div class="card-header bg-orange d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-dark">Em validação</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="em_validacao">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-dark kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-warning btn-sm btn-block kanban-create-card" data-create-column="em_validacao">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>
                                    <div class="kanban-cards mt-2" id="col-em_validacao"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="concluidas">
                                <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-white">Concluídos</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="concluidas">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-white kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">

                                    <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-success btn-sm btn-block kanban-create-card" data-create-column="concluidas">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>

                                    <div class="kanban-cards mt-2" id="col-concluidas"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="retorno">
                                <div class="card-header bg-danger d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-white">Retorno</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="retorno">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-white kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">

                                    <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-danger btn-sm btn-block kanban-create-card" data-create-column="retorno">
                                        <i class="fas fa-plus mr-1"></i>Novo card
                                    </button>

                                    <div class="kanban-cards mt-2" id="col-retorno"></div>
                                </div>
                            </div>

                            <div class="card kanban-column" data-column="arquivados">
                                <div class="card-header bg-secondary d-flex justify-content-between align-items-center">
                                    <span class="kanban-column-header-title text-white">Arquivados</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light mr-2" data-count="arquivados">0</span>
                                        <div class="card-tools m-0">
                                            <button type="button" class="btn btn-tool text-white kanban-column-toggle" data-card-widget="collapse" aria-label="Minimizar coluna">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <div class="kanban-cards mt-2" id="col-arquivados"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- /.content -->

            <div class="modal fade" id="modalEditarCard" tabindex="-1" role="dialog" aria-labelledby="modalEditarCardLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="kanban-modal-drop-overlay d-none" id="modalCardDropOverlay">
                            <div class="kanban-modal-drop-overlay-inner">
                                <i class="fas fa-file-upload mr-2"></i>Solte o arquivo para anexar ao card
                            </div>
                        </div>
                        <div class="modal-header bg-primary" id="modalEditarCardHeader">
                            <h5 class="modal-title text-white" id="modalEditarCardLabel">Editar tarefa</h5>
                            <button type="button" class="close text-white" id="modalEditarCardClose" data-dismiss="modal" aria-label="Fechar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formEditarCard">
                            <div class="modal-body">
                                <input type="hidden" id="editCardId" name="id">
                                <input type="hidden" id="editCardPosicao" name="posicao">
                                <input type="hidden" id="addArquivoTempId" name="temp_id_chamado">
                                <div class="alert alert-light border py-2 mb-3">
                                    <strong>Criado por:</strong> <span id="editCardcriado_por">-</span>
                                    <span class="ml-3"><strong>Em:</strong> <span id="editCardcriado_em">-</span></span>
                                </div>

                                <div class="form-group">
                                    <label for="editCardTitulo">Título</label>
                                    <input type="text" class="form-control" id="editCardTitulo" name="titulo" required maxlength="200" <?= !$atributos_editaveis['titulo'] ? 'disabled' : '' ?>>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="editCardproximo_retorno">Próximo retorno</label>
                                        <input type="date" class="form-control" id="editCardproximo_retorno" name="proximo_retorno" <?= !$atributos_editaveis['proximo_retorno'] ? 'disabled' : '' ?>>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="editCardResponsavel">Responsável</label>
                                        <select class="form-control" id="editCardResponsavel" name="responsavel_id" <?= !$atributos_editaveis['responsavel_id'] ? 'disabled' : '' ?>>
                                            <option value="">Selecione</option>
                                            <?php foreach ($tecnicos_ti as $tecnico) { ?>
                                                <option
                                                    value="<?= $tecnico->id ?>"
                                                    data-cargo="<?= htmlspecialchars((string)($tecnico->nome_cargo ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-setor="<?= htmlspecialchars((string)($tecnico->nome_setor ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                ><?= $tecnico->apresentacao ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="editCardtipo_chamado">Tipo chamado</label>
                                        <select class="form-control" id="editCardTipoChamado" name="tipo_chamado" <?= !$atributos_editaveis['tipo_chamado'] ? 'disabled' : '' ?> required>
                                            <option value="desenvolvimento">Desenvolvimento</option>
                                            <option value="suporte">Suporte</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="desenvolvimentoClassificacao">
                                        <label for="editCardDesenvolvimentoClassificacao">Sistema/Módulo</label>
                                        <select class="form-control" id="editCardDesenvolvimentoClassificacao" name="modulo" <?= !$atributos_editaveis['modulo'] ? 'disabled' : '' ?> required>
                                            <option value="">Selecione</option>
                                            <?php foreach($meus_modulos as $modulo) {?>
                                                <option value="<?= $modulo['Sistema'] ?>/<?= $modulo['Descricao'] ?>">(<?= $modulo['Sistema'] ?>) <?= $modulo['Descricao'] ?></option>
                                            <?php } ?>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="suporteClassificacao">
                                        <label for="editCardSuporteClassificacao">Tipo</label>
                                        <select class="form-control" id="editCardSuporteClassificacao" name="modulo" <?= !$atributos_editaveis['modulo'] ? 'disabled' : '' ?>>
                                            <option value="">Selecione</option>
                                            <option value="suporte-para-maquinas">Suporte para Máquinas</option>
                                            <option value="suporte-para-programas-e-aplicativos">Suporte para Programas e Aplicativos</option>
                                            <option value="suporte-para-perifericos">Suporte para Periféricos</option>
                                            <option value="suporte-para-duvidas-no-geral">Suporte para duvidas no geral</option>
                                            <option value="liberacao-de-acesso-para-internos">Liberação de acesso para internos</option>
                                            <option value="infraestrutura">Infraestrutura</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-12 text-left mb-0" style="font-weight: 700;">
                                        <p style="margin-bottom: 0px">
                                            Classificação do chamado
                                        </p>
                                    </div>
                                    <div class="form-group col-md-4 text-left">
                                        <label class="d-flex align-items-center gap-1" style="gap: 4px">
                                            <input type="radio" name="classificacao" required value="correcao">
                                            <span style="font-weight: normal">Correção</span>
                                        </label>
                                    </div>

                                    <div class="form-group col-md-4 text-center">
                                        <label class="d-flex justify-content-center align-items-center gap-1" style="gap: 4px">
                                            <input type="radio" name="classificacao" required value="melhoria">
                                            <span style="font-weight: normal">Melhoria</span>
                                        </label>
                                    </div>

                                    <div class="form-group col-md-4 text-right">
                                        <label class="d-flex justify-content-end align-items-center gap-1" style="gap: 4px">
                                            <input type="radio" name="classificacao" required value="novo_modulo">
                                            <span style="font-weight: normal">Novo modulo</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="editCardObservadoresBusca">Observadores</label>
                                    <div class="input-group mb-2 position-relative">
                                        <div class="input-group-prepend">
                                            <button type="button" id="editCardObservadoresBuscarBtn" class="btn btn-outline-secondary btn-sm kanban-observadores-buscar-btn">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                        <input type="search" class="form-control form-control-sm" id="editCardObservadoresBusca" placeholder="Pesquisar observadores" <?= !$atributos_editaveis['observadores'] ? 'disabled' : '' ?>>
                                        <ul id="editCardObservadoresPesquisa" class="list-group d-none"></ul>
                                        <ul id="editCardObservadoresSkeleton" class="list-group d-none">
                                            <li class="list-group-item d-flex align-items-center px-2">
                                                <div class="skeleton-line w-55"></div>
                                            </li>
                                            <li class="list-group-item d-flex align-items-center px-2">
                                                <div class="skeleton-line w-75"></div>
                                            </li>
                                            <li class="list-group-item d-flex align-items-center px-2">
                                                <div class="skeleton-line w-50"></div>
                                            </li>
                                            <li class="list-group-item d-flex align-items-center px-2">
                                                <div class="skeleton-line w-65"></div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="form-control kanban-observadores-input" id="editCardObservadores" aria-live="polite"></div>
                                    <small class="form-text text-muted">Adicione um ou mais usuários observadores.</small>
                                </div>

                                <div class="form-group">
                                    <label for="editCardDescricao">Descrição</label>
                                    <textarea class="form-control" id="editCardDescricao" name="descricao"></textarea>
                                </div>

                                <section class="kanban-modal-panel mb-3 d-none" id="modalChecklistSection">
                                    <div class="kanban-modal-panel-header">
                                        <div>
                                            <h6 class="kanban-modal-panel-title mb-1">
                                                <i class="fas fa-tasks mr-1"></i>Checklist
                                            </h6>
                                            <p class="kanban-modal-panel-subtitle mb-0" id="modalChecklistSubtitle">Gerencie o andamento interno do chamado sem sair do card.</p>
                                        </div>
                                        <span class="badge badge-light border" id="modalChecklistCount">0/0</span>
                                    </div>

                                    <div class="kanban-checklist-progress-wrap">
                                        <div class="d-flex justify-content-between align-items-center small">
                                            <span class="text-muted">Progresso</span>
                                            <span class="font-weight-bold" id="modalChecklistProgressText">0%</span>
                                        </div>
                                        <div class="progress kanban-checklist-progress">
                                            <div
                                                class="progress-bar bg-primary"
                                                id="modalChecklistProgressBar"
                                                role="progressbar"
                                                style="width: 0%"
                                                aria-valuenow="0"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            ></div>
                                        </div>
                                    </div>

                                    <div class="kanban-checklist-list" id="modalChecklistList">
                                        <div class="kanban-empty-state">Nenhum item no checklist</div>
                                    </div>

                                    <div class="input-group input-group-sm kanban-checklist-composer">
                                        <input type="text" class="form-control" id="modalChecklistInput" maxlength="220" placeholder="Adicionar item ao checklist">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" id="modalChecklistAddBtn">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </section>

                                <div class="form-group mb-2" id="modalUpdatesSection">
                                    <label class="mb-1">Atualizações</label>
                                    <div class="kanban-updates-table-wrap border rounded p-1 bg-white">
                                        <table class="table table-sm table-bordered table-striped mb-0 kanban-updates-table">
                                            <thead>
                                                <tr>
                                                    <th>Atualização</th>
                                                    <th>Quem</th>
                                                    <th>Quando</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modalCardUpdatesBody">
                                                <tr>
                                                    <td class="kanban-updates-empty" colspan="3">Sem atualizações</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="input-group input-group-sm mt-2">
                                        <input type="text" class="form-control" id="modalUpdateInput" placeholder="Nova atualização" maxlength="220">
                                        <div class="input-group-append">
                                            <button type="button" <?= !$atributos_editaveis['criar_em_qualquer_coluna'] ? 'disabled="disabled"' : '' ?> class="btn btn-outline-secondary" id="modalAddUpdateBtn">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-7 mb-3 mb-lg-0" id="modalCommentsColumn">
                                        <section class="kanban-modal-panel h-100">
                                            <div class="kanban-modal-panel-header">
                                                <div>
                                                    <h6 class="kanban-modal-panel-title mb-1">
                                                        <i class="far fa-comments mr-1"></i>Comentários
                                                    </h6>
                                                    <p class="kanban-modal-panel-subtitle mb-0">Mais recentes no topo.</p>
                                                </div>
                                                <span class="badge badge-light border" id="modalCommentsCount">0</span>
                                            </div>
                                            <div class="kanban-comments-list" id="modalCardCommentsList">
                                                <div class="kanban-empty-state">Nenhum comentário</div>
                                            </div>
                                            <div class="kanban-comment-composer">
                                                <label id="modalCommentInputLabel" class="small font-weight-bold mb-1">Novo comentário</label>
                                                <div class="position-relative kanban-comment-mention-wrapper">
                                                    <div <?php if (!$atributos_editaveis['criar_comentarios']) {
                                                            echo 'contenteditable="false"';
                                                        } else {
                                                            echo 'contenteditable="true"';
                                                        } ?> class="form-control kanban-comment-editor" id="modalCommentInput" role="textbox" aria-multiline="true" aria-labelledby="modalCommentInputLabel" aria-disabled="<?= !$atributos_editaveis['criar_comentarios'] ? 'true' : 'false' ?>" tabindex="<?= !$atributos_editaveis['criar_comentarios'] ? '-1' : '0' ?>" spellcheck="true" data-placeholder="Escreva um comentário. Use @ para marcar o criador, o responsável ou os observadores deste chamado."></div>
                                                    <div class="kanban-comment-mention-panel d-none" id="modalCommentMentionsPanel"></div>
                                                </div>
                                                <small class="form-text text-muted mt-1">Dica: use @ para marcar o criador, o responsável ou os observadores deste chamado.</small>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button <?php if (!$atributos_editaveis['criar_comentarios']) {
                                                                echo "disabled";
                                                            } ?> type="button" class="btn btn-sm btn-primary" id="modalAddCommentBtn">
                                                        <i class="far fa-paper-plane mr-1"></i>Comentar
                                                    </button>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                    <div class="col-lg-5" id="modalAttachmentsColumn">
                                        <section class="kanban-modal-panel h-100">
                                            <div class="kanban-modal-panel-header">
                                                <div>
                                                    <h6 class="kanban-modal-panel-title mb-1">
                                                        <i class="fas fa-paperclip mr-1"></i>Arquivos anexados
                                                    </h6>
                                                    <p class="kanban-modal-panel-subtitle mb-0">Upload, arraste e solte ou cole com Ctrl+V.</p>
                                                </div>
                                                <span class="badge badge-light border" id="modalAttachmentsCount">0</span>
                                            </div>
                                            <input type="file" class="d-none" id="modalAttachmentInput" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                                            <div class="kanban-attachment-dropzone" id="modalAttachmentDropzone" tabindex="0">
                                                <div class="kanban-attachment-dropzone-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="kanban-attachment-dropzone-title">Solte um arquivo aqui</div>
                                                <p class="kanban-attachment-dropzone-text mb-2">Também é possível colar um arquivo da área de transferência.</p>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="modalAttachmentBrowseBtn" data-requestajax-preserve-state="true">
                                                    <i class="fas fa-folder-open mr-1"></i>Selecionar arquivo
                                                </button>
                                            </div>
                                            <div class="kanban-attachments-list" id="modalCardAttachmentsList">
                                                <div class="kanban-empty-state">Nenhum arquivo anexado</div>
                                            </div>
                                        </section>
                                    </div>
                                </div>

                                <div class="form-group form-check" id="modalArquivadoSection">
                                    <input type="checkbox" class="form-check-input" id="editCardArquivado" name="arquivado" <?= !$atributos_editaveis['arquivado'] ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="editCardArquivado">Card arquivado</label>
                                    <small class="form-text text-muted">Ao desarquivar, o card volta para sua última coluna ativa.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Salvar alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-wrapper -->
        <?php
        require ADMIN_PATH . '/includes/footer.php';
        ?>
    </div>
    <!-- Incluindo jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluindo jQuery Validate -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <!-- AdminLTE App -->
    <script src="/adminlte-painel/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Summernote -->
    <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/summernote/summernote-bs4.min.js"></script>
    <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
    <!-- Redirect JQUery Plugin -->
    <script src="https://cdn.rawgit.com/mgalante/jquery.redirect/master/jquery.redirect.js"></script>
    <script src="https://cdn.es.gov.br/scripts/jquery/jquery-maskedinput/1.4.1/jquery.maskedinput-1.4.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="/adminlte-painel/templates/AdminLTE-3.2.0/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- Incluir estes scripts no seu HTML -->
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
    <script>
        window.CHAMADOS_USUARIO_ID = <?= json_encode(ID_USER) ?>;
        window.CHAMADOS_USUARIO_ATUAL = <?= json_encode($usuarioAtual, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.CHAMADOS_USUARIO_EH_TI = <?= json_encode(trim((string)($_SESSION['setor'] ?? '')) === 'Coordenadoria de Informática e Tecnologia') ?>;
    </script>
    <script>
        const ATRIBUTOS_EDITAVEIS = <?= json_encode($atributos_editaveis) ?>;
        const MEUS_MODULOS = <?= json_encode($atributos_editaveis) ?>;
    </script>
    <script src="/adminlte-painel/src/functions.js?v=<?= $v ?>"></script>
    <script src="/adminlte-painel/src/theme-toggle.js"></script>
    <script src="/adminlte-painel/admin/content/chamados/js/main.js?v=<?= $v ?>"></script>
</body>

</html>
