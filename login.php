<?php
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
  }

  require_once BASE_PATH . '/includes/config.php';
  require_once BASE_PATH . '/includes/functions.php';
  require_once BASE_PATH . '/classes/Users/Users.php';

  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  function loginRedirectTarget(): string
  {
    $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '/adminlte-painel/admin/';
    $redirect = is_string($redirect) ? $redirect : '/adminlte-painel/admin/';

    if ($redirect === '' || str_starts_with($redirect, 'http://') || str_starts_with($redirect, 'https://') || !str_starts_with($redirect, '/')) {
      return '/adminlte-painel/admin/';
    }

    return $redirect;
  }

  if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ' . loginRedirectTarget());
    exit;
  }

  $erro_login = null;
  $email = '';
  $redirect = loginRedirectTarget();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
      $erro_login = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
      $erro_login = 'Informe um e-mail e uma senha válidos.';
    } else {
      $usuario = Users::autenticarPorEmail($email, $password);

      if ($usuario === null) {
        $erro_login = 'E-mail ou senha inválidos.';
      } else {
        session_regenerate_id(true);

        $nomeUsuario = trim((string) (($usuario['first_name'] ?? '') . ' ' . ($usuario['last_name'] ?? '')));
        $nomeUsuario = $nomeUsuario !== '' ? $nomeUsuario : $usuario['email'];

        $_SESSION['loggedin'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['id'] = (int) $usuario['id'];
        $_SESSION['user_id'] = (int) $usuario['id'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['usuario_nome'] = $nomeUsuario;
        $_SESSION['nome'] = $nomeUsuario;
        $_SESSION['first_name'] = $usuario['first_name'];
        $_SESSION['last_name'] = $usuario['last_name'];
        $_SESSION['status'] = $usuario['status'];
        $_SESSION['estado_conselho'] = 'BR';
        $_SESSION['Permissoes'] = [
          ['Rotina' => '00072', 'Consulta' => '1', 'Incluir' => '1', 'Excluir' => '1', 'Alterar' => '1'],
          ['Rotina' => '00108', 'Consulta' => '1', 'Incluir' => '1', 'Excluir' => '1', 'Alterar' => '1'],
        ];

        header('Location: ' . $redirect);
        exit;
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
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

          <div class="input-group mb-3">
            <input id="loginEmail" name="email" type="email" class="form-control" placeholder="E-mail" autocomplete="email" value="<?= htmlspecialchars($email) ?>" required>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input id="loginPassword" name="password" type="password" class="form-control" placeholder="Senha" autocomplete="current-password" required>
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
