<?php
    $usuario_nome = $_SESSION['usuario_nome'] ?? 'Administrador';
    $usuario_cargo = $_SESSION['usuario_cargo'] ?? 'Administrador do sistema';
    $usuario_avatar = 'https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg';
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item seletor-cor-navbar">
            <button
                type="button"
                class="nav-link btn btn-link"
                id="botaoPaletaCores"
                aria-label="Escolher cor do painel"
                aria-controls="painelPaletaCores"
                aria-expanded="false"
                title="Escolher cor do painel">
                <i class="fas fa-palette"></i>
            </button>
            <div class="painel-paleta-cores d-none" id="painelPaletaCores" role="dialog" aria-label="Cores do painel">
                <div class="painel-paleta-cabecalho">Cor do painel</div>
                <div class="painel-paleta-opcoes">
                    <button type="button" class="opcao-cor-painel" data-cor-painel="oceano" aria-label="Usar cor Oceano">
                        <span class="amostra-cor amostra-cor-oceano"></span><span>Oceano</span>
                    </button>
                    <button type="button" class="opcao-cor-painel" data-cor-painel="petroleo" aria-label="Usar cor Petróleo">
                        <span class="amostra-cor amostra-cor-petroleo"></span><span>Petróleo</span>
                    </button>
                    <button type="button" class="opcao-cor-painel" data-cor-painel="floresta" aria-label="Usar cor Floresta">
                        <span class="amostra-cor amostra-cor-floresta"></span><span>Floresta</span>
                    </button>
                    <button type="button" class="opcao-cor-painel" data-cor-painel="rubi" aria-label="Usar cor Rubi">
                        <span class="amostra-cor amostra-cor-rubi"></span><span>Rubi</span>
                    </button>
                    <button type="button" class="opcao-cor-painel" data-cor-painel="dourado" aria-label="Usar cor Dourado">
                        <span class="amostra-cor amostra-cor-dourado"></span><span>Dourado</span>
                    </button>
                </div>
            </div>
        </li>

        <script>
            (function () {
                const chaveCorPainel = "adminlte-painel-cor"
                const coresPainel = ["oceano", "petroleo", "floresta", "rubi", "dourado"]

                function aplicarCorPainel(cor) {
                    const corValida = coresPainel.includes(cor) ? cor : "floresta"

                    document.body.classList.remove(...coresPainel)
                    document.body.classList.add(corValida)
                    localStorage.setItem(chaveCorPainel, corValida)

                    document.querySelectorAll("[data-cor-painel]").forEach(function (botao) {
                        const selecionado = botao.dataset.corPainel === corValida
                        botao.classList.toggle("active", selecionado)
                        botao.setAttribute("aria-pressed", selecionado ? "true" : "false")
                    })
                }

                function fecharPaletaCores() {
                    const painel = document.getElementById("painelPaletaCores")
                    const botao = document.getElementById("botaoPaletaCores")

                    painel?.classList.add("d-none")
                    botao?.setAttribute("aria-expanded", "false")
                }

                function iniciarPaletaCores() {
                    const painel = document.getElementById("painelPaletaCores")
                    const botao = document.getElementById("botaoPaletaCores")

                    aplicarCorPainel(localStorage.getItem(chaveCorPainel) || "floresta")

                    botao?.addEventListener("click", function (evento) {
                        evento.stopPropagation()
                        const abrir = painel.classList.contains("d-none")

                        painel.classList.toggle("d-none", !abrir)
                        botao.setAttribute("aria-expanded", abrir ? "true" : "false")
                    })

                    painel?.addEventListener("click", function (evento) {
                        evento.stopPropagation()
                        const opcao = evento.target.closest("[data-cor-painel]")

                        if (opcao) {
                            aplicarCorPainel(opcao.dataset.corPainel)
                        }
                    })

                    document.addEventListener("click", fecharPaletaCores)
                    document.addEventListener("keydown", function (evento) {
                        if (evento.key === "Escape") {
                            fecharPaletaCores()
                            botao?.focus()
                        }
                    })
                }

                iniciarPaletaCores()
            })()
        </script>

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
            <a href="#" class="nav-link dropdown-toggle marca-usuario-confef" data-toggle="dropdown" aria-label="Abrir menu do usuário">
                <img src="/adminlte-painel/public/images/logo-icon.png" class="logo-usuario-confef" alt="CONFEF">
                <span class="d-none d-md-inline font-weight-bold">CONFEF</span>
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
                    <form action="/adminlte-painel/logout.php" method="post" class="d-inline float-right">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-default btn-flat logout-btn">Sair</button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
