<?php
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(__DIR__, 2));
}

Controller::setFileStyle("/admin/home/css/home.css");
Controller::setFileJavascript("/admin/home/js/dashboard-demo.js");
Controller::setFileJavascript("/admin/home/js/main.js");

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/admin/includes/session_manager.php';
require_once BASE_PATH . '/includes/functions.php';

$ui_helper = new Classes\UIHelper();
$mock_numbers = [
  array(
    "title" => 'Novos pedidos',
    "value" => 150,
    "icon" => 'fas fa-shopping-cart',
    "color" => 'primary'
  ),
  array(
    "title" => 'Taxa de rejeição',
    "value" => "53%",
    "icon" => "fas fa-chart-pie",
    "color" => 'success'
  ),
  array(
    "title" => 'Cadastros de usuários',
    "value" => "44",
    "icon" => "fas fa-user-plus",
    "color" => 'warning'
  ),
  array(
    "title" => 'Visitantes únicos',
    "value" => "65",
    "icon" => "fas fa-chart-line",
    "color" => 'danger'
  ),
];

$members = [
  ['name' => 'Alexander Pierce', 'date' => 'Hoje', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user1-128x128.jpg'],
  ['name' => 'Norman Stanley', 'date' => 'Ontem', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user8-128x128.jpg'],
  ['name' => 'Jane Doe', 'date' => '12 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user7-128x128.jpg'],
  ['name' => 'John Doe', 'date' => '12 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user6-128x128.jpg'],
  ['name' => 'Robert Doe', 'date' => '13 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg'],
  ['name' => 'Mike Doe', 'date' => '14 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user5-128x128.jpg'],
  ['name' => 'Sarah Bullock', 'date' => '15 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user4-128x128.jpg'],
  ['name' => 'Mina Lee', 'date' => '15 Jan', 'avatar' => 'https://adminlte.io/themes/v3/dist/img/user3-128x128.jpg'],
];

?>



<div class="row">
  <?php foreach ($mock_numbers as $mock_number): ?>
    <div class="col-lg-3 col-6">
      <?= $ui_helper::smallBox($mock_number['title'], $mock_number['value'], $mock_number['icon'], $mock_number['color']); ?>
    </div>
  <?php endforeach ?>
</div>

<div class="row">
  <section class="col-lg-7 connectedSortable">
    <div class="card">
      <div class="card-header border-0">
        <h3 class="card-title">
          <i class="fas fa-chart-line mr-1"></i>
          Vendas
        </h3>
        <div class="card-tools">
          <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn bg-info btn-sm" data-card-widget="remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="chart">
          <canvas id="sales-chart" height="280"></canvas>
        </div>
      </div>
    </div>

    <div class="card card-success">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-map-marker-alt mr-1"></i>
          Mapa de visitantes
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div id="world-map-markers"></div>
      </div>
      <div class="card-footer bg-transparent">
        <div class="row text-center">
          <div class="col-4">
            <div class="text-bold text-lg">54.300</div>
            <span class="text-muted">Visitas</span>
          </div>
          <div class="col-4">
            <div class="text-bold text-lg">30%</div>
            <span class="text-muted">Indicações</span>
          </div>
          <div class="col-4">
            <div class="text-bold text-lg">70%</div>
            <span class="text-muted">Orgânico</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="col-lg-5 connectedSortable">
    <div class="card card-primary card-outline direct-chat direct-chat-primary">
      <div class="card-header">
        <h3 class="card-title">Chat direto</h3>
        <div class="card-tools">
          <span title="3 novas mensagens" class="badge bg-primary">3</span>
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn btn-tool" data-widget="chat-pane-toggle">
            <i class="fas fa-comments"></i>
          </button>
          <button type="button" class="btn btn-tool" data-card-widget="remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="direct-chat-messages">
          <div class="direct-chat-msg">
            <div class="direct-chat-infos clearfix">
              <span class="direct-chat-name float-left">Alexander Pierce</span>
              <span class="direct-chat-timestamp float-right">23 Jan 2:00 pm</span>
            </div>
            <img class="direct-chat-img" src="https://adminlte.io/themes/v3/dist/img/user1-128x128.jpg" alt="Imagem do usuário da mensagem">
            <div class="direct-chat-text">
              Este painel está pronto para o relatório semanal?
            </div>
          </div>

          <div class="direct-chat-msg right">
            <div class="direct-chat-infos clearfix">
              <span class="direct-chat-name float-right">Sarah Bullock</span>
              <span class="direct-chat-timestamp float-left">23 Jan 2:05 pm</span>
            </div>
            <img class="direct-chat-img" src="https://adminlte.io/themes/v3/dist/img/user3-128x128.jpg" alt="Imagem do usuário da mensagem">
            <div class="direct-chat-text">
              Sim. Os gráficos e o mapa de visitantes usam dados fictícios.
            </div>
          </div>

          <div class="direct-chat-msg">
            <div class="direct-chat-infos clearfix">
              <span class="direct-chat-name float-left">Alexander Pierce</span>
              <span class="direct-chat-timestamp float-right">23 Jan 2:08 pm</span>
            </div>
            <img class="direct-chat-img" src="https://adminlte.io/themes/v3/dist/img/user1-128x128.jpg" alt="Imagem do usuário da mensagem">
            <div class="direct-chat-text">
              Ótimo. Adicione também alguns membros e marcadores de tráfego.
            </div>
          </div>
        </div>

        <div class="direct-chat-contacts">
          <ul class="contacts-list">
            <li>
              <a href="#">
                <img class="contacts-list-img" src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" alt="contact user image">
                <div class="contacts-list-info">
                  <span class="contacts-list-name">
                    Count Dracula
                    <small class="contacts-list-date float-right">2/28/2026</small>
                  </span>
                  <span class="contacts-list-msg">Como está o painel?</span>
                </div>
              </a>
            </li>
            <li>
              <a href="#">
                <img class="contacts-list-img" src="https://adminlte.io/themes/v3/dist/img/user7-128x128.jpg" alt="contact user image">
                <div class="contacts-list-info">
                  <span class="contacts-list-name">
                    Jane Doe
                    <small class="contacts-list-date float-right">2/23/2026</small>
                  </span>
                  <span class="contacts-list-msg">Envie o relatório mais recente.</span>
                </div>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div class="card-footer">
        <form action="#" method="post">
          <div class="input-group">
            <input type="text" name="message" placeholder="Digite uma mensagem..." class="form-control">
            <span class="input-group-append">
              <button type="button" class="btn btn-primary">Enviar</button>
            </span>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-users mr-1"></i>
          Membros recentes
        </h3>
        <div class="card-tools">
          <span class="badge badge-danger">8 novos membros</span>
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <ul class="users-list clearfix members-list">
          <?php foreach ($members as $member): ?>
            <li>
              <img src="<?= htmlspecialchars($member['avatar']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
              <a class="users-list-name" href="#"><?= htmlspecialchars($member['name']) ?></a>
              <span class="users-list-date"><?= htmlspecialchars($member['date']) ?></span>
            </li>
          <?php endforeach ?>
        </ul>
      </div>
      <div class="card-footer text-center">
        <a href="#">Ver todos os usuários</a>
      </div>
    </div>

    <div class="card card-danger">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-chart-pie mr-1"></i>
          Uso por navegador
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <canvas id="browser-chart" height="220"></canvas>
      </div>
    </div>
  </section>
</div>