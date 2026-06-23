<?php
if (!defined('BASE_PATH')) {
  define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . "/adminlte-painel");
}

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/classes/Controller.php';
require_once BASE_PATH . '/admin/includes/session_manager.php';
require_once BASE_PATH . '/includes/functions.php';

function getIdFromUrl(): ?string
{
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $partes = explode('/', trim($path, '/'));

  return end($partes) ?: null;
}

$paginaSolicitada = isset($_GET['pagina']) ? $_GET['pagina'] : 'home/index.php';
$paginaSolicitada = trim(str_replace('\\', '/', $paginaSolicitada), '/');

if ($paginaSolicitada === '' || strpos($paginaSolicitada, '..') !== false) {
  http_response_code(404);
  exit('Pagina nao encontrada.');
}

if (!preg_match('/^[a-zA-Z0-9_\/.-]+$/', $paginaSolicitada)) {
  http_response_code(404);
  exit('Pagina nao encontrada.');
}

if (substr($paginaSolicitada, -4) !== '.php') {
  $paginaSolicitada = rtrim($paginaSolicitada, '/') . '/index.php';
}

$paginasRoot = realpath(__DIR__);
$conteudoPaginaPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $paginaSolicitada);
$indexAtualPath = realpath(__FILE__);

if (
  !$conteudoPaginaPath ||
  strpos($conteudoPaginaPath, $paginasRoot . DIRECTORY_SEPARATOR) !== 0 ||
  $conteudoPaginaPath === $indexAtualPath ||
  !is_file($conteudoPaginaPath)
) {
  http_response_code(404);
  exit('Pagina nao encontrada.');
}

ob_start();
require $conteudoPaginaPath;
$pageContent = ob_get_clean();

$renderizarIndex = $renderizarIndex ?? true;

if (!$renderizarIndex) {
  echo $pageContent;
  exit;
}

$pageTitle = Controller::getPageTitle() ?? 'Painel';
$renderizarHeader = $renderizarHeader ?? true;
$renderizarFooter = $renderizarFooter ?? true;
$pageDescription = $pageDescription ?? 'O Sistema oferece cursos e informações para profissionais da Educação Física.';

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- SEO Metatags -->
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

  <meta name="keywords" content="futebol, esportes, cursos de educação fisica, regulamentação, saude esportiva, basquete, cursos de volei, cursos de natação, musculação, cursos de condicionamento fisico, treinamento esportivo, cursos de corrida, atletismo, ginastica, cursos de ciclismo, handebol, cursos de tenis, cursos de artes marciais, judo, karate, jiu-jitsu, cursos de boxe, MMA, levantamento de peso, crossfit, yoga, pilates, cursos de esporte profissional, esporte amador, cursos de esportes olimpicos, esportes coletivos, esportes individuais, esportes aquaticos, esportes de inverno, futebol de salao, futsal, futebol americano, rugby, beisebol, cursos de softball, skate, surfe, cursos de esportes radicais, esportes de aventura, escalada, parkour, fitness, alongamento, flexibilidade, resistencia física, coordenação motora, cursos de esportes recreativos, esportes paraolimpicos, treinamento funcional, preparacao f�sica, fisioterapia esportiva, cursos de reabilitação, cursos de nutrição esportiva, hidratação, suplementação, bem-estar, qualidade de vida, lesoes esportivas, cursos de fisiologia do exercicio, biologia esportiva, biomecanica, psicologia esportiva, motivação esportiva, performance esportiva, competição esportivas, campeonatos de futebol, ligas esportivas, Copa do Mundo, Olimpiadas, formacao de atletas, pratica esportiva, cursos de vida ativa, estilo de vida saudavel">

  <!-- Metatags Open Graph (para compartilhamento em redes sociais) -->
  <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
  <meta property="og:url" content="/">
  <meta property="og:image" content="/adminlte-painel/src/assets/images/cabecalho.jpg">
  <meta property="og:type" content="website">

  <!-- Metatags para Twitter (compartilhamento em redes sociais) -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">

  <link rel="stylesheet" href="/adminlte-painel/src/css/style.css">


  <?php Controller::getFilesStyles(); ?>
  <?php Controller::getFilesJavascriptHeader(); ?>
  <script src="/adminlte-painel/src/js/main.js" defer></script>

</head>

<body>
  <div class="body-container">
    <?php if ($renderizarHeader): ?>
      <header>
        <?php
        require BASE_PATH . "/includes/header.php";
        ?>
      </header>
    <?php endif; ?>

    <main>
      <?php echo $pageContent; ?>
    </main>

    <!--FOOTER-->
    <?php if ($renderizarFooter): ?>
      <footer>
        <?php
        require BASE_PATH . "/includes/footer.php";
        ?>
      </footer>
    <?php endif; ?>
  </div>

  <?php Controller::getFilesJavascript(); ?>
  <script src="/adminlte-painel/src/js/carrousel.js"></script>
</body>

</html>

<?php require BASE_PATH . "/includes/vlibras.php" ?>
