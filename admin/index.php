<?php
  if (!defined('BASE_PATH')) {
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . "/adminlte-painel");
  }

  require_once BASE_PATH . '/vendor/autoload.php';
  require_once BASE_PATH . '/includes/functions.php';

  $ui_helper = new Classes\UIHelper();
  $mock_numbers = [
    array(
      "title" => 'New Orders',
      "value" => 150,
      "icon" => 'fas fa-shopping-cart',
      "color" => 'primary'
    ),
    array(
      "title" => 'Bounce Rate',
      "value" => "53%",
      "icon" => "fas fa-chart-pie",
      "color" => 'success'
    ),
    array(
      "title" => 'User Registrations',
      "value" => "44",
      "icon" => "fas fa-user",
      "color" => 'warning'
    ),
    array(
      "title" => 'Unique Visitors',
      "value" => "44",
      "icon" => "fas fa-user",
      "color" => 'danger'
    ),

  ]
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <title>Document</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <?php
      include_once '../includes/header.php';
    ?>
    <?php
      include_once '../includes/sidebar.php';
    ?>

    <!-- O content-wrapper contém o conteúdo da página -->
    <div class="content-wrapper">
      <!-- Cabeçalho da página (Opcional) -->
      <div class="content-header">
        <div class="container-fluid">
          <h1 class="m-0">Dashboard</h1>
        </div>
      </div>

      <!-- Conteúdo principal -->
      <section class="content">
        <div class="container-fluid">
          <div class="row">
            <?php foreach($mock_numbers as $mock_number):?>
              <div class="col-lg-3 col-6">
              <?= $ui_helper::smallBox($mock_number['title'], $mock_number['value'], $mock_number['icon'], $mock_number['color']); ?>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </section>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>

</html>