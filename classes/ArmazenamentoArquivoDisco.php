<?php

namespace Classes;

use RuntimeException;
use finfo;
use ZipArchive;

class ArmazenamentoArquivoDisco
{
    private const EXTENSOES_BLOQUEADAS = [
        'php', 'phtml', 'pht', 'phar', 'cgi', 'pl', 'py', 'rb', 'sh', 'bat',
        'cmd', 'exe', 'dll', 'com', 'msi', 'js', 'mjs', 'html', 'htm', 'xhtml',
        'svg', 'xml'
    ];

    private const MIMES_PERMITIDOS_PADRAO = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip-compressed'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/x-zip-compressed'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip', 'application/x-zip-compressed'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip', 'application/x-zip-compressed'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    private string $raiz;
    private array $extensoesPermitidas;
    private array $mimesPermitidos;
    private int $tamanhoMaximoBytes;
    private ?string $rotinaPermissao;
    private bool $exigirUsuarioLogado;
    private static bool $ignorarPermissao = false;
    private static bool $ignorarUsuarioLogado = false;

    public function __construct(
        ?string $raiz = null,
        array $extensoesPermitidas = [],
        int $tamanhoMaximoBytes = 15728640,
        ?string $rotinaPermissao = null,
        array $mimesPermitidos = [],
        bool $exigirUsuarioLogado = true
    ) {
        $this->raiz = $this->prepararRaiz($raiz);
        $this->extensoesPermitidas = $this->normalizarExtensoes($extensoesPermitidas ?: array_keys(self::MIMES_PERMITIDOS_PADRAO));
        $this->mimesPermitidos = $mimesPermitidos ?: self::MIMES_PERMITIDOS_PADRAO;
        $this->tamanhoMaximoBytes = $tamanhoMaximoBytes > 0 ? $tamanhoMaximoBytes : 15728640;
        $this->rotinaPermissao = $rotinaPermissao;
        $this->exigirUsuarioLogado = $exigirUsuarioLogado;
    }

    public static function permissaoSeraIgnorada(): bool
    {
        return self::$ignorarPermissao;
    }

    public static function usuarioLogadoSeraIgnorado(): bool
    {
        return self::$ignorarUsuarioLogado;
    }

    public static function executarIgnorandoPermissao(callable $callback)
    {
        $estadoAnterior = self::$ignorarPermissao;
        self::$ignorarPermissao = true;

        try {
            return $callback();
        } finally {
            self::$ignorarPermissao = $estadoAnterior;
        }
    }

    public static function executarIgnorandoUsuarioLogado(callable $callback)
    {
        $estadoAnterior = self::$ignorarUsuarioLogado;
        self::$ignorarUsuarioLogado = true;

        try {
            return $callback();
        } finally {
            self::$ignorarUsuarioLogado = $estadoAnterior;
        }
    }

    public static function executarIgnorandoSeguranca(callable $callback)
    {
        $estadoPermissao = self::$ignorarPermissao;
        $estadoUsuario = self::$ignorarUsuarioLogado;
        self::$ignorarPermissao = true;
        self::$ignorarUsuarioLogado = true;

        try {
            return $callback();
        } finally {
            self::$ignorarPermissao = $estadoPermissao;
            self::$ignorarUsuarioLogado = $estadoUsuario;
        }
    }

    public function salvarUpload(array $arquivo, string $diretorioRelativo = '', array $opcoes = []): array
    {
        $this->validarUsuarioLogado();
        $this->validarPermissao((string)($opcoes['operacao'] ?? 'Incluir'));
        $this->validarErroUpload((int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE));

        $nomeOriginal = trim((string)($arquivo['name'] ?? ''));
        $tmpName = (string)($arquivo['tmp_name'] ?? '');
        $tamanhoBytes = (int)($arquivo['size'] ?? 0);
        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Arquivo temporário inválido para upload.');
        }

        $this->validarExtensao($extensao);
        $mimeType = $this->validarArquivoFisico($tmpName, $extensao, $tamanhoBytes);

        $diretorioDestino = $this->criarDiretorioSeguro($diretorioRelativo);
        $nomeArquivo = $this->gerarNomeArquivo($nomeOriginal, $extensao, (string)($opcoes['prefixo'] ?? 'arquivo'));
        $destino = $diretorioDestino . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (!move_uploaded_file($tmpName, $destino)) {
            throw new RuntimeException('Não foi possível gravar o arquivo enviado.');
        }

        @chmod($destino, 0664);

        return $this->montarRespostaArquivo($destino, $nomeOriginal, $mimeType);
    }

    public function ler(string $caminhoRelativo): string
    {
        $this->validarUsuarioLogado();
        $this->validarPermissao('Consulta');
        $caminho = $this->resolverCaminhoArquivo($caminhoRelativo);

        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo.');
        }

        return $conteudo;
    }

    public function metadados(string $caminhoRelativo): array
    {
        $this->validarUsuarioLogado();
        $this->validarPermissao('Consulta');
        $caminho = $this->resolverCaminhoArquivo($caminhoRelativo);

        return $this->montarRespostaArquivo($caminho, basename($caminho), $this->detectarMimeType($caminho));
    }

    public function excluir(string $caminhoRelativo): bool
    {
        $this->validarUsuarioLogado();
        $this->validarPermissao('Excluir');
        $caminho = $this->resolverCaminhoArquivo($caminhoRelativo);

        if (!unlink($caminho)) {
            throw new RuntimeException('Não foi possível excluir o arquivo.');
        }

        return true;
    }

    public function caminhoAbsoluto(string $caminhoRelativo): string
    {
        $this->validarUsuarioLogado();
        return $this->resolverCaminhoArquivo($caminhoRelativo);
    }

    private function prepararRaiz(?string $raiz): string
    {
        $base = defined('BASE_PATH') ? (string)BASE_PATH : dirname(__DIR__, 2);
        $raiz = trim((string)($raiz ?: $this->obterVariavelAmbiente('WEBCONFEF_STORAGE_DIR', '')));
        $raiz = $raiz !== '' ? $raiz : dirname($base) . DIRECTORY_SEPARATOR . 'webconfef_storage';

        if (!$this->caminhoEhAbsoluto($raiz)) {
            $raiz = rtrim($base, "/\\") . DIRECTORY_SEPARATOR . ltrim($raiz, "/\\");
        }

        if (!is_dir($raiz) && !mkdir($raiz, 0775, true) && !is_dir($raiz)) {
            throw new RuntimeException('Não foi possível preparar a pasta de armazenamento.');
        }

        $real = realpath($raiz);
        if ($real === false || !is_dir($real)) {
            throw new RuntimeException('Pasta de armazenamento inválida.');
        }

        return rtrim($real, "/\\");
    }

    private function obterVariavelAmbiente(string $chave, ?string $padrao = null): ?string
    {
        if (function_exists('env')) {
            $valor = env($chave, $padrao);
            return $valor !== null ? trim((string)$valor, " \t\n\r\0\x0B\"'") : $padrao;
        }

        if (array_key_exists($chave, $_ENV)) {
            return trim((string)$_ENV[$chave], " \t\n\r\0\x0B\"'");
        }

        $valor = getenv($chave);
        if ($valor !== false) {
            return trim((string)$valor, " \t\n\r\0\x0B\"'");
        }

        if (isset($_SERVER[$chave])) {
            return trim((string)$_SERVER[$chave], " \t\n\r\0\x0B\"'");
        }

        return $padrao;
    }

    private function validarUsuarioLogado(): void
    {
        if (!$this->exigirUsuarioLogado || self::usuarioLogadoSeraIgnorado()) {
            return;
        }

        $idSessao = $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? null;
        $idConstante = defined('ID_USER') ? ID_USER : null;
        $idUsuario = $idSessao ?? $idConstante;

        if (empty($idUsuario)) {
            throw new RuntimeException('Usuário logado obrigatório para executar esta operação.');
        }
    }

    private function validarPermissao(string $operacao): void
    {
        if ($this->rotinaPermissao === null || $this->rotinaPermissao === '') {
            return;
        }

        if (self::permissaoSeraIgnorada()) {
            return;
        }

        if (!function_exists('verificaPermissao') || !verificaPermissao($this->rotinaPermissao, $operacao)) {
            throw new RuntimeException('Você não possui permissão para executar esta operação.');
        }
    }

    private function validarErroUpload(int $erro): void
    {
        if ($erro === UPLOAD_ERR_OK) {
            return;
        }

        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('O arquivo excede o limite permitido.');
        }

        throw new RuntimeException('Falha no envio do arquivo.');
    }

    private function validarExtensao(string $extensao): void
    {
        if ($extensao === '' || in_array($extensao, self::EXTENSOES_BLOQUEADAS, true)) {
            throw new RuntimeException('Extensão de arquivo bloqueada.');
        }

        if (!in_array($extensao, $this->extensoesPermitidas, true)) {
            throw new RuntimeException('Extensão de arquivo não permitida.');
        }
    }

    private function validarArquivoFisico(string $caminho, string $extensao, int $tamanhoBytes): string
    {
        if ($tamanhoBytes <= 0) {
            throw new RuntimeException('O arquivo enviado está vazio.');
        }

        if ($tamanhoBytes > $this->tamanhoMaximoBytes) {
            throw new RuntimeException('O arquivo excede o tamanho máximo permitido.');
        }

        $mimeType = $this->detectarMimeType($caminho);
        $mimesPermitidos = $this->mimesPermitidos[$extensao] ?? [];

        if ($mimeType === '' || !in_array($mimeType, $mimesPermitidos, true)) {
            throw new RuntimeException('O tipo real do arquivo não corresponde ao formato permitido.');
        }

        if (!$this->validarAssinaturaArquivo($caminho, $extensao, $mimeType)) {
            throw new RuntimeException('O conteúdo do arquivo não corresponde ao formato informado.');
        }

        return $mimeType;
    }

    private function criarDiretorioSeguro(string $diretorioRelativo): string
    {
        $diretorioRelativo = $this->normalizarCaminhoRelativo($diretorioRelativo);
        $diretorio = $this->raiz . ($diretorioRelativo !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $diretorioRelativo) : '');

        if (!$this->caminhoEstaNaRaiz($diretorio)) {
            throw new RuntimeException('Diretório de destino inválido.');
        }

        if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível preparar o diretório de destino.');
        }

        return $diretorio;
    }

    private function resolverCaminhoArquivo(string $caminhoRelativo): string
    {
        $relativo = $this->normalizarCaminhoRelativo($caminhoRelativo);
        if ($relativo === '') {
            throw new RuntimeException('Caminho do arquivo não informado.');
        }

        $caminho = $this->raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);
        $real = realpath($caminho);

        if ($real === false || !is_file($real) || !$this->caminhoEstaNaRaiz($real)) {
            throw new RuntimeException('Arquivo não localizado.');
        }

        return $real;
    }

    private function montarRespostaArquivo(string $caminhoAbsoluto, string $nomeOriginal, string $mimeType): array
    {
        return [
            'nome_original' => $nomeOriginal,
            'nome_arquivo' => basename($caminhoAbsoluto),
            'caminho_relativo' => $this->obterCaminhoRelativo($caminhoAbsoluto),
            'caminho_absoluto' => $caminhoAbsoluto,
            'tamanho_bytes' => is_file($caminhoAbsoluto) ? (int)filesize($caminhoAbsoluto) : 0,
            'mime_type' => $mimeType,
        ];
    }

    private function obterCaminhoRelativo(string $caminhoAbsoluto): string
    {
        $normalizado = str_replace('\\', '/', $caminhoAbsoluto);
        $raiz = str_replace('\\', '/', $this->raiz);

        return ltrim(substr($normalizado, strlen($raiz)), '/');
    }

    private function gerarNomeArquivo(string $nomeOriginal, string $extensao, string $prefixo): string
    {
        $base = pathinfo($nomeOriginal, PATHINFO_FILENAME);
        $base = $this->normalizarNomeArquivo($base);
        $base = $base !== '' ? $base : $this->normalizarNomeArquivo($prefixo);
        $base = $base !== '' ? $base : 'arquivo';

        return $base . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
    }

    private function normalizarNomeArquivo(string $nome): string
    {
        $nome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome;
        $nome = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $nome) ?: '';
        $nome = preg_replace('/_+/', '_', $nome) ?: '';

        return trim(substr($nome, 0, 120), '_-');
    }

    private function normalizarExtensoes(array $extensoes): array
    {
        $normalizadas = [];
        foreach ($extensoes as $extensao) {
            $extensao = strtolower(trim((string)$extensao, " .\t\n\r\0\x0B"));
            if ($extensao !== '' && !in_array($extensao, self::EXTENSOES_BLOQUEADAS, true)) {
                $normalizadas[] = $extensao;
            }
        }

        return array_values(array_unique($normalizadas));
    }

    private function normalizarCaminhoRelativo(string $caminho): string
    {
        $partes = preg_split('#[\\\\/]+#', str_replace("\0", '', $caminho)) ?: [];
        $seguras = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if ($parte === '' || $parte === '.' || $parte === '..') {
                continue;
            }
            $seguras[] = $this->normalizarSegmentoCaminho($parte);
        }

        return implode('/', array_filter($seguras, static fn ($parte) => $parte !== ''));
    }

    private function normalizarSegmentoCaminho(string $parte): string
    {
        $parte = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $parte) ?: $parte;
        $parte = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $parte) ?: '';
        $parte = preg_replace('/_+/', '_', $parte) ?: '';
        $parte = trim(substr($parte, 0, 180), '_-.');

        if ($parte === '' || $parte === '.' || $parte === '..') {
            return '';
        }

        return $parte;
    }

    private function caminhoEhAbsoluto(string $caminho): bool
    {
        return (bool)preg_match('#^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)#', $caminho);
    }

    private function caminhoEstaNaRaiz(string $caminho): bool
    {
        $caminho = str_replace('\\', '/', rtrim($caminho, '/\\'));
        $raiz = str_replace('\\', '/', rtrim($this->raiz, '/\\'));

        return $caminho === $raiz || strpos($caminho . '/', $raiz . '/') === 0;
    }

    private function detectarMimeType(string $caminho): string
    {
        $mimeType = '';

        if (class_exists(finfo::class)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = (string)($finfo->file($caminho) ?: '');
        }

        if ($mimeType === '' && function_exists('mime_content_type')) {
            $mimeType = (string)@mime_content_type($caminho);
        }

        return strtolower(trim($mimeType));
    }

    private function validarAssinaturaArquivo(string $caminho, string $extensao, string $mimeType): bool
    {
        $cabecalho = $this->lerCabecalho($caminho);
        if ($cabecalho === '') {
            return false;
        }

        switch ($extensao) {
            case 'pdf':
                return strncmp($cabecalho, '%PDF-', 5) === 0;
            case 'jpg':
            case 'jpeg':
                return strncmp($cabecalho, "\xFF\xD8\xFF", 3) === 0;
            case 'png':
                return strncmp($cabecalho, "\x89PNG\r\n\x1A\n", 8) === 0;
            case 'gif':
                return strncmp($cabecalho, 'GIF87a', 6) === 0 || strncmp($cabecalho, 'GIF89a', 6) === 0;
            case 'webp':
                return strncmp($cabecalho, 'RIFF', 4) === 0 && substr($cabecalho, 8, 4) === 'WEBP';
            case 'doc':
            case 'xls':
            case 'ppt':
                return strncmp($cabecalho, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0;
            case 'docx':
            case 'xlsx':
            case 'pptx':
            case 'odt':
            case 'ods':
                return $this->validarPacoteZip($caminho, $extensao, $cabecalho);
            case 'zip':
                return strncmp($cabecalho, "PK\x03\x04", 4) === 0
                    || strncmp($cabecalho, "PK\x05\x06", 4) === 0
                    || strncmp($cabecalho, "PK\x07\x08", 4) === 0;
            case 'csv':
            case 'txt':
                return strpos($mimeType, 'text/') === 0 || in_array($mimeType, ['application/csv', 'application/vnd.ms-excel'], true);
        }

        return false;
    }

    private function lerCabecalho(string $caminho, int $bytes = 512): string
    {
        $handle = fopen($caminho, 'rb');
        if (!is_resource($handle)) {
            return '';
        }

        $conteudo = (string)fread($handle, $bytes);
        fclose($handle);

        return $conteudo;
    }

    private function validarPacoteZip(string $caminho, string $extensao, string $cabecalho): bool
    {
        if (
            strncmp($cabecalho, "PK\x03\x04", 4) !== 0
            && strncmp($cabecalho, "PK\x05\x06", 4) !== 0
            && strncmp($cabecalho, "PK\x07\x08", 4) !== 0
        ) {
            return false;
        }

        if (!class_exists(ZipArchive::class)) {
            return true;
        }

        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true) {
            return false;
        }

        $valido = match ($extensao) {
            'docx' => $this->zipPossuiEntrada($zip, ['word/document.xml']),
            'xlsx' => $this->zipPossuiEntrada($zip, ['xl/workbook.xml']),
            'pptx' => $this->zipPossuiEntrada($zip, ['ppt/presentation.xml']),
            'odt' => $this->zipPossuiEntrada($zip, ['content.xml']) && trim((string)$zip->getFromName('mimetype')) === 'application/vnd.oasis.opendocument.text',
            'ods' => $this->zipPossuiEntrada($zip, ['content.xml']) && trim((string)$zip->getFromName('mimetype')) === 'application/vnd.oasis.opendocument.spreadsheet',
            default => false,
        };

        $zip->close();
        return $valido;
    }

    private function zipPossuiEntrada(ZipArchive $zip, array $entradas): bool
    {
        foreach ($entradas as $entrada) {
            if ($zip->locateName($entrada, ZipArchive::FL_NOCASE) !== false) {
                return true;
            }

            if (
                $zip->locateName(
                    basename($entrada),
                    ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR
                ) !== false
            ) {
                return true;
            }
        }

        return false;
    }
}
