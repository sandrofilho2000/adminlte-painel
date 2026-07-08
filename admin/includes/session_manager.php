<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

if (!$isLoggedIn) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/adminlte-painel/admin/';
    $loginUrl = '/adminlte-painel/login.php?redirect=' . rawurlencode($requestUri);
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

$_SESSION['nome'] = $_SESSION['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuario');
$_SESSION['usuario_nome'] = $_SESSION['usuario_nome'] ?? $_SESSION['nome'];
/* $_SESSION['is_admin'] = $_SESSION['is_admin'] ?? true; */

if (!isset($_SESSION['Permissoes']) || !is_array($_SESSION['Permissoes'])) {
    $_SESSION['Permissoes'] = [
        ['Rotina' => '00072', 'Consulta' => '1', 'Incluir' => '1', 'Excluir' => '1', 'Alterar' => '1'],
        ['Rotina' => '00108', 'Consulta' => '1', 'Incluir' => '1', 'Excluir' => '1', 'Alterar' => '1'],
    ];
}
