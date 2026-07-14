<?php
  use Classes\Persistemas;

  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
  }

  require_once BASE_PATH . '/includes/config.php';
  require_once BASE_PATH . '/includes/functions.php';

  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  function destinoRedirecionamentoLogin(): string
  {
    $redirecionamento = $_POST['redirect'] ?? $_GET['redirect'] ?? '/adminlte-painel/admin/';
    $redirecionamento = is_string($redirecionamento) ? $redirecionamento : '/adminlte-painel/admin/';

    if (
      $redirecionamento === ''
      || !str_starts_with($redirecionamento, '/')
      || str_starts_with($redirecionamento, '//')
      || str_contains($redirecionamento, "\r")
      || str_contains($redirecionamento, "\n")
    ) {
      return '/adminlte-painel/admin/';
    }

    return $redirecionamento;
  }

  function chaveLimiteLogin(string $credencial): string
  {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return 'login:' . hash('sha256', $ip . '|' . strtolower(trim($credencial)));
  }

  function obterTentativasLogin(string $chave): array
  {
    if (function_exists('apcu_fetch')) {
      $encontrado = false;
      $tentativas = apcu_fetch($chave, $encontrado);
      return $encontrado && is_array($tentativas) ? $tentativas : [];
    }

    return is_array($_SESSION['_tentativas_login'][$chave] ?? null)
      ? $_SESSION['_tentativas_login'][$chave]
      : [];
  }

  function salvarTentativasLogin(string $chave, array $tentativas): void
  {
    if (function_exists('apcu_store')) {
      apcu_store($chave, $tentativas, 1800);
      return;
    }

    $_SESSION['_tentativas_login'][$chave] = $tentativas;
  }

  function segundosBloqueioLogin(string $chave): int
  {
    return max(0, (int) (obterTentativasLogin($chave)['bloqueado_ate'] ?? 0) - time());
  }

  function registrarFalhaLogin(string $chave): void
  {
    $agora = time();
    $tentativas = obterTentativasLogin($chave);
    $inicio = (int) ($tentativas['inicio'] ?? 0);
    $falhas = (int) ($tentativas['falhas'] ?? 0);

    if ($inicio === 0 || $agora - $inicio > 900) {
      $inicio = $agora;
      $falhas = 0;
    }

    $falhas++;
    $atraso = $falhas >= 5 ? min(60 * (2 ** ($falhas - 5)), 900) : 0;
    salvarTentativasLogin($chave, [
      'inicio' => $inicio,
      'falhas' => $falhas,
      'bloqueado_ate' => $agora + $atraso,
    ]);
  }

  function limparFalhasLogin(string $chave): void
  {
    if (function_exists('apcu_delete')) {
      apcu_delete($chave);
    }

    unset($_SESSION['_tentativas_login'][$chave]);
  }

  if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ' . destinoRedirecionamentoLogin());
    exit;
  }

  $erro_login = null;
  $credencial = '';
  $redirecionamento = destinoRedirecionamentoLogin();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credencial = trim((string) ($_POST['credencial'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $tokenCsrf = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], $tokenCsrf)) {
      $erro_login = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($credencial === '' || $senha === '') {
      $erro_login = 'Informe seu usuário ou e-mail e sua senha.';
    } else {
      $chaveLimite = chaveLimiteLogin($credencial);
      $segundosBloqueio = segundosBloqueioLogin($chaveLimite);

      if ($segundosBloqueio > 0) {
        $erro_login = "Muitas tentativas. Aguarde {$segundosBloqueio} segundos.";
      } else {
        $resultado = (new \Classes\Usuarios())->autenticar($credencial, $senha);

        if (!$resultado['sucesso']) {
          registrarFalhaLogin($chaveLimite);
          $erro_login = $resultado['erro'];
        } elseif ($resultado['trocar_senha']) {
          limparFalhasLogin($chaveLimite);
          $erro_login = 'Primeiro acesso ou senha expirada. Redefina sua senha antes de continuar.';
        } else {
          limparFalhasLogin($chaveLimite);
          session_regenerate_id(true);
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

          $usuario = $resultado['usuario'];
          $nomeUsuario = trim((string) ($usuario['apresentacao'] ?? ''));
          $nomeUsuario = $nomeUsuario !== '' ? $nomeUsuario : (string) $usuario['login'];

          $_SESSION['loggedin'] = true;
          /* $_SESSION['is_admin'] = false; */
          $_SESSION['id'] = (int) $usuario['id'];
          $_SESSION['user_id'] = (int) $usuario['id'];
          $_SESSION['email'] = $usuario['email'];
          $_SESSION['login'] = $usuario['login'];
          $_SESSION['usuario_nome'] = $nomeUsuario;
          $_SESSION['nome'] = $nomeUsuario;
          $_SESSION['instituicao'] = $usuario['instituicao'];
          $_SESSION['estado_conselho'] = $usuario['estado_conselho'] ?: 'BR';
          if (!defined('ESTADO_CONSELHO')) {
            define('ESTADO_CONSELHO', $_SESSION['estado_conselho']);
          }
          unset($_SESSION['Permissoes']);
          $_SESSION['Permissoes'] = Persistemas::carregarPermissoes(true) ?: [];

          header('Location: ' . $redirecionamento);
          exit;
        }
      }
    }
  }
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <title>Entrar</title>
  <style>
    .login-theme-toggle {
      position: fixed;
      right: 1rem;
      top: 1rem;
      z-index: 1030;
    }
  </style>
</head>

<body class="hold-transition login-page">
  <button type="button" class="btn btn-default login-theme-toggle" data-theme-toggle aria-label="Alternar tema escuro">
    <i class="fas fa-moon" data-theme-toggle-icon></i>
  </button>

  <div class="login-box">
    <div class="card card-outline card-primary">
      <div class="card-header text-center">
        <a href="/adminlte-painel/login.php" class="h1"><b>Admin</b>LTE</a>
      </div>

      <div class="card-body login-card-body">
        <p class="login-box-msg">Entre para iniciar sua sessão</p>

        <?php if ($erro_login): ?>
          <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($erro_login) ?>
          </div>
        <?php endif; ?>

        <form action="/adminlte-painel/login.php" method="post" autocomplete="on">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirecionamento, ENT_QUOTES, 'UTF-8') ?>">

          <div class="input-group mb-3">
            <input id="loginCredencial" name="credencial" type="text" class="form-control" placeholder="Usuário ou e-mail" autocomplete="username" value="<?= htmlspecialchars($credencial, ENT_QUOTES, 'UTF-8') ?>" required>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input id="loginSenha" name="senha" type="password" class="form-control" placeholder="Senha" autocomplete="current-password" required>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-8">
              <div class="icheck-primary">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Lembrar de mim</label>
              </div>
            </div>

            <div class="col-4">
              <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </div>
          </div>
        </form>

        <p class="mb-1 mt-3">
          <a href="#">Esqueci minha senha</a>
        </p>
        <p class="mb-0">
          <a href="#" class="text-center">Cadastrar uma nova conta</a>
        </p>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
  <script src="src/theme-toggle.js"></script>
</body>

</html>
