<?php
  use Classes\Persistemas;

  require_once __DIR__ . '/includes/session.php';

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
    $redirecionamento = $_POST['redirect'] ?? $_GET['redirect'] ?? '/admin/';
    $redirecionamento = is_string($redirecionamento) ? $redirecionamento : '/admin/';

    if (
      $redirecionamento === ''
      || str_contains($redirecionamento, "\\")
      || str_contains($redirecionamento, "\r")
      || str_contains($redirecionamento, "\n")
      || (
        $redirecionamento !== '/admin/'
        && !str_starts_with($redirecionamento, '/admin/')
      )
    ) {
      return '/admin/';
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
    global $pdo;

    $consulta = $pdo->prepare("
      SELECT data_json, expires_at
      FROM confef1.app_rate_limits
      WHERE rate_key = :chave
      LIMIT 1
    ");
    $consulta->execute(['chave' => $chave]);
    $registro = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
      return [];
    }

    if (strtotime((string) ($registro['expires_at'] ?? '')) <= time()) {
      limparFalhasLogin($chave);
      return [];
    }

    $tentativas = json_decode((string) ($registro['data_json'] ?? ''), true);
    return is_array($tentativas) ? $tentativas : [];
  }

  function registrarFalhaLogin(string $chave): void
  {
    global $pdo;

    $iniciouTransacao = !$pdo->inTransaction();

    try {
      if ($iniciouTransacao) {
        $pdo->beginTransaction();
      }

      $agora = time();
      $expiraEm = date('Y-m-d H:i:s', $agora + 1800);
      $dadosIniciais = json_encode([
        'inicio' => $agora,
        'falhas' => 0,
        'bloqueado_ate' => 0,
      ], JSON_UNESCAPED_UNICODE);

      $inserir = $pdo->prepare("
        INSERT INTO confef1.app_rate_limits (rate_key, data_json, expires_at)
        VALUES (:chave, :dados, :expira_em)
        ON DUPLICATE KEY UPDATE rate_key = VALUES(rate_key)
      ");
      $inserir->execute([
        'chave' => $chave,
        'dados' => $dadosIniciais,
        'expira_em' => $expiraEm,
      ]);

      $consultar = $pdo->prepare("
        SELECT data_json, expires_at
        FROM confef1.app_rate_limits
        WHERE rate_key = :chave
        FOR UPDATE
      ");
      $consultar->execute(['chave' => $chave]);
      $registro = $consultar->fetch(PDO::FETCH_ASSOC) ?: [];
      $tentativas = json_decode((string) ($registro['data_json'] ?? ''), true);
      $tentativas = is_array($tentativas) ? $tentativas : [];

      $inicio = (int) ($tentativas['inicio'] ?? 0);
      $falhas = (int) ($tentativas['falhas'] ?? 0);

      if ($inicio === 0 || $agora - $inicio > 900) {
        $inicio = $agora;
        $falhas = 0;
      }

      $falhas++;
      $atraso = $falhas >= 5 ? min(60 * (2 ** ($falhas - 5)), 900) : 0;
      $dados = json_encode([
        'inicio' => $inicio,
        'falhas' => $falhas,
        'bloqueado_ate' => $agora + $atraso,
      ], JSON_UNESCAPED_UNICODE);

      $atualizar = $pdo->prepare("
        UPDATE confef1.app_rate_limits
        SET data_json = :dados,
            expires_at = :expira_em,
            updated_at = CURRENT_TIMESTAMP
        WHERE rate_key = :chave
      ");
      $atualizar->execute([
        'dados' => $dados,
        'expira_em' => $expiraEm,
        'chave' => $chave,
      ]);

      if ($iniciouTransacao) {
        $pdo->commit();
      }
    } catch (Throwable $erro) {
      if ($iniciouTransacao && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      error_log('[login][rate_limit] ' . $erro->getMessage());
      throw new RuntimeException('Não foi possível validar a segurança do login. Tente novamente.');
    }
  }

  function segundosBloqueioLogin(string $chave): int
  {
    return max(0, (int) (obterTentativasLogin($chave)['bloqueado_ate'] ?? 0) - time());
  }

  function limparFalhasLogin(string $chave): void
  {
    global $pdo;

    $excluir = $pdo->prepare("
      DELETE FROM confef1.app_rate_limits
      WHERE rate_key = :chave
    ");
    $excluir->execute(['chave' => $chave]);
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha384-nRgPTkuX86pH8yjPJUAFuASXQSSl2/bBUiNV47vSYpKFxHJhbcrGnmlYpYJMeD7a" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" integrity="sha384-qrt37eUXKQgF1p6OlpdB29OTyKryxbxdJHkvfVN4suujWnn6PibIvbnygcK4uJfA" crossorigin="anonymous">
  <link rel="stylesheet" href="/adminlte-painel/src/styles.css">
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
        <a href="/" class="h1"><b>Admin</b>LTE</a>
      </div>

      <div class="card-body login-card-body">
        <p class="login-box-msg">Entre para iniciar sua sessão</p>

        <?php if ($erro_login): ?>
          <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($erro_login) ?>
          </div>
        <?php endif; ?>

        <form action="/" method="post" autocomplete="on">
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js" integrity="sha384-GzAyPc+9MeNdsDGfpe/gNkeDXXSbdZdY0yKEFBGFxqmq/97NJ92k5oyF1YPOOhm5" crossorigin="anonymous"></script>
  <script src="/adminlte-painel/src/theme-toggle.js"></script>
</body>

</html>
