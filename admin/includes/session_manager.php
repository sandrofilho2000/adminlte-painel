<?php

require_once dirname(__DIR__, 2) . '/includes/session.php';

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

if (!$isLoggedIn) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/admin/';
    $loginUrl = '/?redirect=' . rawurlencode($requestUri);
    header('Location: ' . $loginUrl);
    exit;
}

if (!defined('ID_USER')) {
    define('ID_USER', (int) ($_SESSION['id'] ?? $_SESSION['user_id'] ?? 0));
}

$_SESSION['estado_conselho'] = $_SESSION['estado_conselho'] ?? 'BR';

if (!defined('ESTADO_CONSELHO')) {
    define('ESTADO_CONSELHO', $_SESSION['estado_conselho']);
}

$_SESSION['nome'] = $_SESSION['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuário');
$_SESSION['usuario_nome'] = $_SESSION['usuario_nome'] ?? $_SESSION['nome'];
/* $_SESSION['is_admin'] = $_SESSION['is_admin'] ?? true; */
