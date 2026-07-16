<?php

if (!function_exists('iniciarSessaoSegura')) {
    function iniciarSessaoSegura(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $protocoloEncaminhado = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $usaHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
            || $protocoloEncaminhado === 'https';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $usaHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

iniciarSessaoSegura();
