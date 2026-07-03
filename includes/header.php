<?php
    $usuario_nome = $_SESSION['usuario_nome'] ?? 'Administrador';
    $usuario_cargo = $_SESSION['usuario_cargo'] ?? 'Administrador do sistema';
    $usuario_avatar = 'https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg';
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <a href="/adminlte-painel/admin/" class="brand-link aurora-brand">
        <span class="aurora-brand-icon">
            <img
                src="/adminlte-painel/public/images/logo-mini.png"
                alt="Aurora Tech"
                class="brand-image" />
        </span>
        <span class="brand-text"><strong>aurora</strong> tech</span>
     </a>
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Pesquisar" aria-label="Pesquisar">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-comments"></i>
                <span class="badge badge-danger navbar-badge">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="#" class="dropdown-item">
                    <div class="media">
                        <img src="https://adminlte.io/themes/v3/dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                Brad Diesel
                                <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">Me ligue quando puder...</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> Há 4 horas</p>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <div class="media">
                        <img src="https://adminlte.io/themes/v3/dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                John Pierce
                                <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">Recebi sua mensagem</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> Há 4 horas</p>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <div class="media">
                        <img src="https://adminlte.io/themes/v3/dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                        <div class="media-body">
                            <h3 class="dropdown-item-title">
                                Nora Silvester
                                <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                            </h3>
                            <p class="text-sm">O assunto aparece aqui</p>
                            <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> Há 4 horas</p>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">Ver todas as mensagens</a>
            </div>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">15 notificações</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> 4 novas mensagens
                    <span class="float-right text-muted text-sm">3 min</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-users mr-2"></i> 8 solicitações de amizade
                    <span class="float-right text-muted text-sm">12 horas</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-file mr-2"></i> 3 novos relatórios
                    <span class="float-right text-muted text-sm">2 dias</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">Ver todas as notificações</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link btn btn-link" data-theme-toggle aria-label="Alternar tema escuro">
                <i class="fas fa-moon" data-theme-toggle-icon></i>
            </button>
        </li>

        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <img src="<?= htmlspecialchars($usuario_avatar) ?>" class="user-image img-circle elevation-2" alt="User Image">
                <span class="d-none d-md-inline"><?= htmlspecialchars($usuario_nome) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <li class="user-header bg-primary">
                    <img src="<?= htmlspecialchars($usuario_avatar) ?>" class="img-circle elevation-2" alt="User Image">
                    <p>
                        <?= htmlspecialchars($usuario_nome) ?> - <?= htmlspecialchars($usuario_cargo) ?>
                        <small>Membro desde nov. de 2023</small>
                    </p>
                </li>
                <li class="user-body">
                    <div class="row">
                        <div class="col-4 text-center">
                            <a href="#">Seguidores</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Vendas</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Amigos</a>
                        </div>
                    </div>
                </li>
                <li class="user-footer">
                    <a href="/adminlte-painel/perfil.php" class="btn btn-default btn-flat">Perfil</a>
                    <a href="/adminlte-painel/logout.php" class="btn btn-default btn-flat float-right">Sair</a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
