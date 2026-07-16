<?php

require_once __DIR__ . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

$tokenRecebido = (string) ($_POST['csrf_token'] ?? '');
$tokenSessao = (string) ($_SESSION['csrf_token'] ?? '');

if ($tokenSessao === '' || $tokenRecebido === '' || !hash_equals($tokenSessao, $tokenRecebido)) {
    http_response_code(403);
    exit('Token CSRF inválido.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

header('Location: /');
exit;
