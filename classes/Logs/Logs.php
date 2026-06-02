<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class Logs extends ClasseBase
{
    public $id;
    public $timestamp;
    public $id_usuario;
    public $tipo;
    public $mensagem;
    public $codigo;
    public $objeto_metodo;
    public $payload;
    public $trace;
    public $linha;
    public $erro;
    public $criado_em;

    public $ver_logs_ignorados = false;

    private static $gravando = false;
    private const MENSAGEM_ERRO_REDIGIR = 'Unable to bind to server: Invalid credentials';
    private const MENSAGEM_AUTENTICACAO_GENERICA = 'Falha ao autenticar. Verifique usuário/e-mail e senha.';
    private const CAMPOS_REGRAS_PERMITIDOS = [
        'mensagem' => 'l.mensagem',
        'objeto_metodo' => 'l.objeto_metodo',
        'payload' => 'l.payload',
        'trace' => 'l.trace',
    ];

    private const OPERADORES_REGRAS = [
        'MAIOR',
        'MENOR',
        'MAIOR_IGUAL',
        'MENOR_IGUAL',
        'IGUAL',
        'DIFERENTE',
        'IN',
        'NOT_IN',
        'LIKE',
        'LIKE_START',
        'LIKE_END',
        'NOT LIKE',
        'NOT_LIKE_START',
        'NOT_LIKE_END',
    ];

    protected $_tabela = array(
        'nome' => 'logs_erros',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "timestamp",
            "id_usuario",
            "tipo",
            "mensagem",
            "codigo",
            "objeto_metodo",
            "payload",
            "trace",
            "linha",
            "erro",
            "criado_em",
        ),
        'permissao' => '00108'
    );

    public function __construct() {}

    public static function gravarErro($erro, $objeto_metodo = '', $payload = '')
    {
        if (self::$gravando) {
            return null;
        }

        self::$gravando = true;

        try {
            $object = self::normalizarErro($erro, $objeto_metodo, $payload);

            if (self::deveRedigirMensagemErro($object['mensagem'] ?? null)) {
                $object = self::redigirMensagemAutenticacao($object);
            }

            global $pdo;
            if (!$pdo instanceof \PDO) {
                $object['logger_status'] = 'pdo_indisponivel';
                error_log('[Logs] PDO indisponivel para gravar logs_erros.');
                return null;
            }

            $sql = "INSERT INTO confef1.logs_erros
                    (`timestamp`, `id_usuario`, `tipo`, `mensagem`, `codigo`, `objeto_metodo`,`payload`, `linha`, `trace`, `erro`)
                    VALUES
                    (:timestamp, :id_usuario, :tipo, :mensagem, :codigo, :objeto_metodo, :payload, :linha, :trace, :erro)";

            $stmt = $pdo->prepare($sql);

            $erroJson = json_encode(
                $object['erro'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
            );
            if ($erroJson === false) {
                $erroJson = json_encode(
                    ['json_encode_error' => json_last_error_msg()],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            $stmt->bindValue(':timestamp', self::truncar((string)$object['timestamp'], 35), \PDO::PARAM_STR);
            if ($object['id_usuario'] === null) {
                $stmt->bindValue(':id_usuario', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':id_usuario', (int)$object['id_usuario'], \PDO::PARAM_INT);
            }
            $stmt->bindValue(':tipo', self::truncar((string)($object['tipo'] ?? ''), 255), \PDO::PARAM_STR);
            $stmt->bindValue(':mensagem', self::truncar((string)($object['mensagem'] ?? ''), 64000), \PDO::PARAM_STR);
            $stmt->bindValue(':codigo', self::truncar((string)($object['codigo'] ?? ''), 100), \PDO::PARAM_STR);
            $stmt->bindValue(':objeto_metodo', self::truncar((string)($object['objeto_metodo'] ?? ''), 1024), \PDO::PARAM_STR);
            $stmt->bindValue(':payload', self::truncar((string)($object['payload'] ?? ''), 64000), \PDO::PARAM_STR);
            if ($object['linha'] === null) {
                $stmt->bindValue(':linha', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':linha', (int)$object['linha'], \PDO::PARAM_INT);
            }
            $stmt->bindValue(':trace', (string)($object['trace'] ?? ''), \PDO::PARAM_STR);
            $stmt->bindValue(':erro', $erroJson, \PDO::PARAM_STR);
            $stmt->execute();

            $object['logger_status'] = 'ok';
            $object['log_id'] = (int)$pdo->lastInsertId();

            return $object['log_id'];
        } catch (\Throwable $e) {
            $falha = [
                'timestamp' => date('c'),
                'logger_status' => 'falha',
                'logger_exception' => $e->getMessage(),
                'logger_file' => $e->getFile(),
                'logger_line' => $e->getLine(),
            ];

            error_log('[Logs] Falha ao inserir logs_erros: ' . $e->getMessage());

            return null;
        } finally {
            self::$gravando = false;
        }
    }

    private static function deveRedigirMensagemErro($mensagem): bool
    {
        $mensagem = trim((string)$mensagem);
        if ($mensagem === '') {
            return false;
        }

        return stripos($mensagem, self::MENSAGEM_ERRO_REDIGIR) !== false;
    }

    private static function redigirMensagemAutenticacao(array $object): array
    {
        $login = self::extrairLoginAutenticacao($object);
        $mensagem = self::MENSAGEM_AUTENTICACAO_GENERICA;
        if ($login !== '') {
            $mensagem .= ' Login: ' . $login . '.';
        }

        $object['mensagem'] = $mensagem;
        $object['payload'] = $mensagem;
        $object['erro'] = $mensagem;
        $object['trace'] = null;
        $object['linha'] = null;

        return $object;
    }

    private static function extrairLoginAutenticacao(array $object): string
    {
        $login = '';

        if (isset($_POST['login']) && is_scalar($_POST['login'])) {
            $login = (string)$_POST['login'];
        } else {
            $payload = $object['payload'] ?? '';

            if (is_string($payload) && trim($payload) !== '') {
                $decodedPayload = json_decode($payload, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPayload) && isset($decodedPayload['login']) && is_scalar($decodedPayload['login'])) {
                    $login = (string)$decodedPayload['login'];
                }
            } elseif (is_array($payload) && isset($payload['login']) && is_scalar($payload['login'])) {
                $login = (string)$payload['login'];
            }
        }

        $login = str_replace(["\r", "\n"], ' ', trim($login));

        return self::truncar($login, 120);
    }

    private static function normalizarErro($erro, $objeto_metodo, $payload): array
    {
        $objetoMetodoNormalizado = trim((string)$objeto_metodo);
        $payloadNormalizado = self::normalizarPayload($payload);

        if ($payloadNormalizado === '' && !empty($_POST)) {
            $payloadNormalizado = self::normalizarPayload($_POST);
        }

        $object = [
            'timestamp' => date('c'),
            'id_usuario' => self::resolverIdUsuario(),
            'tipo' => null,
            'mensagem' => null,
            'codigo' => null,
            'objeto_metodo' => $objetoMetodoNormalizado,
            'payload' => $payloadNormalizado,
            'linha' => null,
            'trace' => null,
            'erro' => [
                'objeto_metodo' => $objetoMetodoNormalizado,
                'payload' => $payloadNormalizado,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
                'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'sapi' => PHP_SAPI,
                'memory_usage' => memory_get_usage(true),
                'memory_peak_usage' => memory_get_peak_usage(true),
                'session_id' => session_id(),
                'session_user' => $_SESSION['id'] ?? null,
            ],
        ];

        if ($erro instanceof \Throwable) {
            $object['tipo'] = get_class($erro);
            $object['mensagem'] = $erro->getMessage();
            $object['codigo'] = (string)$erro->getCode();
            $object['arquivo'] = $erro->getFile();
            $object['linha'] = $erro->getLine();
            $object['trace'] = $erro->getTraceAsString();
            $object['erro']['raw'] = [
                'class' => get_class($erro),
                'message' => $erro->getMessage(),
                'code' => $erro->getCode(),
                'file' => $erro->getFile(),
                'line' => $erro->getLine(),
                'trace' => $erro->getTraceAsString(),
            ];
            return $object;
        }

        if (is_object($erro)) {
            $erro = get_object_vars($erro);
        }

        if (is_array($erro)) {
            if (!empty($erro['objeto_metodo'])) {
                $object['objeto_metodo'] = trim((string)$erro['objeto_metodo']);
                $object['erro']['objeto_metodo'] = $object['objeto_metodo'];
            }
            if (!empty($erro['payload'])) {
                $object['payload'] = self::normalizarPayload($erro['payload']);
                $object['erro']['payload'] = $object['payload'];
            }

            $object['tipo'] = self::primeiroNaoVazio([
                $erro['tipo'] ?? null,
                $erro['type'] ?? null,
                $erro['kind'] ?? null,
                'array',
            ]);
            $object['mensagem'] = self::primeiroNaoVazio([
                $erro['mensagem'] ?? null,
                $erro['message'] ?? null,
                $erro['erro'] ?? null,
            ]);
            $object['codigo'] = self::primeiroNaoVazio([
                isset($erro['codigo']) ? (string)$erro['codigo'] : null,
                isset($erro['code']) ? (string)$erro['code'] : null,
                isset($erro['severity']) ? (string)$erro['severity'] : null,
            ]);
            $object['arquivo'] = self::primeiroNaoVazio([
                $erro['arquivo'] ?? null,
                $erro['file'] ?? null,
            ]);
            $object['linha'] = isset($erro['linha'])
                ? (int)$erro['linha']
                : (isset($erro['line']) ? (int)$erro['line'] : null);
            $object['trace'] = self::primeiroNaoVazio([
                isset($erro['trace']) ? (string)$erro['trace'] : null,
            ]);
            $object['erro']['raw'] = $erro;

            return $object;
        }

        $object['tipo'] = 'scalar';
        $object['mensagem'] = (string)$erro;
        $object['erro']['raw'] = ['valor' => (string)$erro];

        return $object;
    }

    private static function normalizarPayload($payload): string
    {
        if ($payload === null) {
            return '';
        }

        if (is_string($payload)) {
            return $payload;
        }

        if (is_scalar($payload)) {
            return (string)$payload;
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $json === false ? '' : $json;
    }

    private static function resolverIdUsuario(): ?int
    {
        if (defined('ID_USER') && is_numeric(ID_USER)) {
            return (int)ID_USER;
        }

        if (defined('ID_USUARIO') && is_numeric(ID_USUARIO)) {
            return (int)ID_USUARIO;
        }

        if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
            return (int)$_SESSION['id'];
        }

        return null;
    }

    private static function truncar(string $valor, int $max): string
    {
        return strlen($valor) > $max ? substr($valor, 0, $max) : $valor;
    }

    private static function primeiroNaoVazio(array $valores): ?string
    {
        foreach ($valores as $valor) {
            if ($valor === null) {
                continue;
            }
            $texto = trim((string)$valor);
            if ($texto !== '') {
                return $texto;
            }
        }

        return null;
    }

    private function normalizarBooleano($valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_numeric($valor)) {
            return ((int)$valor) === 1;
        }

        if ($valor === null) {
            return false;
        }

        $normalizado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalizado ?? false;
    }

    private function deveExibirLogsIgnorados(): bool
    {
        return $this->normalizarBooleano($this->ver_logs_ignorados);
    }

    private function normalizarListaParaIn($valor): array
    {
        if (is_array($valor)) {
            return array_values(array_filter($valor, function ($item) {
                return $item !== null && $item !== '';
            }));
        }

        $texto = trim((string)$valor);
        if ($texto === '') {
            return [];
        }

        if (strpos($texto, '[') === 0) {
            $json = json_decode($texto, true);
            if (is_array($json)) {
                return array_values(array_filter($json, function ($item) {
                    return $item !== null && $item !== '';
                }));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $texto)), function ($item) {
            return $item !== '';
        }));
    }

    private function escaparValorLike(string $valor): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $valor
        );
    }

    private function montarCondicaoRegra($regra, int $indice): ?string
    {
        $campoEntrada = trim((string)($regra->campo ?? ''));
        $operador = strtoupper(trim((string)($regra->operador ?? '')));
        $valor = $regra->valor ?? '';

        if (!isset(self::CAMPOS_REGRAS_PERMITIDOS[$campoEntrada])) {
            return null;
        }

        if (!in_array($operador, self::OPERADORES_REGRAS, true)) {
            return null;
        }

        $campoSql = self::CAMPOS_REGRAS_PERMITIDOS[$campoEntrada];
        $placeholderBase = "regra_ignorar_{$indice}";

        switch ($operador) {
            case 'MAIOR':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql > :$placeholderBase";
            case 'MENOR':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql < :$placeholderBase";
            case 'MAIOR_IGUAL':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql >= :$placeholderBase";
            case 'MENOR_IGUAL':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql <= :$placeholderBase";
            case 'DIFERENTE':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql <> :$placeholderBase";
            case 'IGUAL':
                $this->substituir($placeholderBase, $valor);
                return "$campoSql = :$placeholderBase";
            case 'LIKE':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, '%' . mb_strtoupper($valorLike) . '%');
                return "UPPER($campoSql) LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'LIKE_START':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, mb_strtoupper($valorLike) . '%');
                return "UPPER($campoSql) LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'LIKE_END':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, '%' . mb_strtoupper($valorLike));
                return "UPPER($campoSql) LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'NOT LIKE':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, '%' . mb_strtoupper($valorLike) . '%');
                return "UPPER($campoSql) NOT LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'NOT_LIKE_START':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, mb_strtoupper($valorLike) . '%');
                return "UPPER($campoSql) NOT LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'NOT_LIKE_END':
                $valorLike = $this->escaparValorLike((string)$valor);
                $this->substituir($placeholderBase, '%' . mb_strtoupper($valorLike));
                return "UPPER($campoSql) NOT LIKE :$placeholderBase ESCAPE '\\\\'";
            case 'IN':
            case 'NOT_IN':
                $valores = $this->normalizarListaParaIn($valor);
                if (empty($valores)) {
                    return null;
                }

                $placeholders = [];
                foreach ($valores as $idx => $item) {
                    $chave = "{$placeholderBase}_{$idx}";
                    $placeholders[] = ":$chave";
                    $this->substituir($chave, $item);
                }

                $inNotIn = $operador === 'NOT_IN' ? 'NOT IN' : 'IN';
                return "$campoSql $inNotIn (" . implode(', ', $placeholders) . ")";
        }

        return null;
    }

    private function aplicarGrupoRegrasExclusao($regras, bool $negarGrupo = false): void
    {
        if (empty($regras) || !is_iterable($regras)) {
            return;
        }

        $condicoes = [];
        foreach ($regras as $indice => $regra) {
            $condicao = $this->montarCondicaoRegra($regra, (int)$indice);
            if ($condicao !== null) {
                $condicoes[] = $condicao;
            }
        }

        if (empty($condicoes)) {
            return;
        }

        $grupo = '(' . implode(' OR ', $condicoes) . ')';
        if ($negarGrupo) {
            $grupo = "NOT $grupo";
        }

        $this->filtrar((string)microtime(true), $grupo, 'EXPLICITO');
    }

    public function getLogs()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }

        // Evita "vazamento" de ordenacao/paginacao do DataTable atual para consulta interna.
        $regras_de_exclusao = Dao::executarComEstadoIsolado(function () {
            return (new LogsIgnorar())->getRegrasAtivas();
        });

        $this->queryCorrente = "SELECT 
                u.apresentacao as nome_usuario,
                u.estado_conselho as estado_conselho,
                l.id,
                l.timestamp,
                l.id_usuario,
                l.tipo,
                l.mensagem,
                l.codigo,
                l.objeto_metodo,
                l.payload,
                l.trace,
                l.linha,
                l.erro,
                l.criado_em
            FROM confef1.logs_erros l 
            LEFT JOIN confef1.TBLUsuarios u ON l.id_usuario = u.id
            WHERE 1=1 
        ";

        if (!$this->deveExibirLogsIgnorados()) {
            // Oculta os ignorados: AND NOT (regra1 OR regra2 OR ...)
            $this->aplicarGrupoRegrasExclusao($regras_de_exclusao, true);
        }

        $this->retornarComoArray = true;
        $result = $this->buscar();
        return $result;
    }

    public function contarLogsIgnorados()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }

        // Evita "vazamento" de ordenacao/paginacao do DataTable atual para consulta interna.
        $regras_de_exclusao = Dao::executarComEstadoIsolado(function () {
            return (new LogsIgnorar())->getRegrasAtivas();
        });

        $this->queryCorrente = "SELECT count(*) as total
            FROM confef1.logs_erros l 
            LEFT JOIN confef1.TBLUsuarios u ON l.id_usuario = u.id 
            WHERE 1=1
        ";

        if(empty($regras_de_exclusao)) {
            return 0;
        }

        // Soma regras ignoradas: AND (regra1 OR regra2 OR ...)
        $this->aplicarGrupoRegrasExclusao($regras_de_exclusao, false);

        $this->retornarComoArray = true;
        $result = $this->buscar()[0]['total'] ?? 0;
        return $result;
    }

    public function excluirLog($id)
    {
        $id = $this->id ?? $id;
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }
        $log = $this->instanciarPorId($id);
        !empty($log) && $log->excluir();
        return true;
    }
}
