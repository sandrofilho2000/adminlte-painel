<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', BASE_PATH . '/admin');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once BASE_PATH . '/vendor/autoload.php';

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        if ($key === '') {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

if (class_exists(\Dotenv\Dotenv::class) && file_exists(BASE_PATH . '/.env')) {
    \Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

loadEnvFile(BASE_PATH . '/.env');

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }
}

function parseDatabaseUrl(string $databaseUrl): array
{
    $parts = parse_url($databaseUrl);

    if ($parts === false || empty($parts['scheme'])) {
        throw new InvalidArgumentException('DATABASE_URL inválida.');
    }

    return [
        'driver' => $parts['scheme'],
        'host' => $parts['host'] ?? 'localhost',
        'port' => $parts['port'] ?? null,
        'user' => isset($parts['user']) ? urldecode($parts['user']) : null,
        'pass' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
        'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
    ];
}

$databaseUrl = env('DATABASE_URL', env('MYSQL_URL'));

try {
    if (!empty($databaseUrl)) {
        $database = parseDatabaseUrl((string) $databaseUrl);

        if ($database['driver'] !== 'mysql') {
            throw new RuntimeException('Apenas DATABASE_URL mysql:// é suportada.');
        }

        $dbCharset = env('DB_CHARSET', 'utf8mb4');
        $dbPort = $database['port'] ?? 3306;
        $dsn = "mysql:host={$database['host']};port={$dbPort};dbname={$database['database']};charset={$dbCharset}";
        $pdo = new PDO($dsn, $database['user'], $database['pass']);
    } else {
        $ambiente = strtolower(trim((string) env('ENVIRONMENT', 'production')));
        $usarBancoTeste = in_array($ambiente, ['localhost', 'development'], true);

        if ($usarBancoTeste) {
            $dbHost = env('DB_SERVER_TESTE');
            $dbPort = env('DB_PORT_TESTE', '3306');
            $dbName = env('DB_NAME_TESTE');
            $dbUser = env('DB_USERNAME_TESTE');
            $dbPass = env('DB_PASSWORD_TESTE', '');
        } else {
            $dbHost = env('DB_SERVER', env('DB_HOST', 'localhost'));
            $dbPort = env('DB_PORT', '3306');
            $dbName = env('DB_DATABASE', env('DB_NAME'));
            $dbUser = env('DB_USERNAME', env('DB_USER'));
            $dbPass = env('DB_PASSWORD', env('DB_PASS', ''));
        }

        $dbCharset = env('DB_CHARSET', 'utf8mb4');

        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $sufixo = $usarBancoTeste ? '_TESTE' : '';
            throw new RuntimeException(
                "Configure DB_SERVER{$sufixo}, DB_NAME{$sufixo} e DB_USERNAME{$sufixo} no .env."
            );
        }

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $exception) {
    throw new RuntimeException('Erro ao conectar ao banco de dados: ' . $exception->getMessage(), 0, $exception);
}


if(!defined('ID_USER') && (!empty($_SESSION['id']) || !empty($_SESSION['user_id']))){
    define('ID_USER', (int) ($_SESSION['id'] ?? $_SESSION['user_id']));
}

if(!defined('ENVIRONMENT') && !empty($_ENV['ENVIRONMENT'])){
    define('ENVIRONMENT', $_ENV['ENVIRONMENT']);
}

if(
    !defined('ESTADO_CONSELHO')
    && !empty($_SESSION['estado_conselho'])
    && (!empty($_SESSION['loggedin']) || !empty($_SESSION['id']) || !empty($_SESSION['user_id']))
){
    define('ESTADO_CONSELHO', $_SESSION['estado_conselho']);
}
