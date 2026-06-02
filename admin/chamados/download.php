<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
include_once ADMIN_PATH . '/includes/session_manager.php';
include_once ADMIN_PATH . '/includes/functions.php';

use Classes\Chamados;
use Classes\ChamadosArquivos;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

function chamadosNormalizarMimeArquivo($mime)
{
    $mime = strtolower(trim((string) $mime));
    if ($mime === '') {
        return '';
    }

    $partes = explode(';', $mime);
    return trim($partes[0] ?? '');
}

function chamadosMimeParaExtensaoArquivo($mime)
{
    $mime = chamadosNormalizarMimeArquivo($mime);

    if ($mime !== '' && strpos($mime, '/') === false && preg_match('/^[a-z0-9]{1,15}$/', $mime)) {
        return $mime;
    }

    $mapa = array(
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'application/vnd.rar' => 'rar',
        'application/x-7z-compressed' => '7z',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/svg+xml' => 'svg',
    );

    return $mapa[$mime] ?? '';
}

function chamadosExtensaoParaMimeArquivo($extensao)
{
    $extensao = strtolower(trim((string) $extensao));
    if ($extensao === '') {
        return '';
    }

    $mapa = array(
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        '7z' => 'application/x-7z-compressed',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
    );

    return $mapa[$extensao] ?? '';
}

function chamadosObterExtensaoArquivo($nomeArquivo, $mime = '')
{
    $nomeArquivo = trim((string) $nomeArquivo);
    if ($nomeArquivo !== '') {
        $partes = explode('.', $nomeArquivo);
        if (count($partes) > 1) {
            $extensao = strtolower(trim((string) array_pop($partes)));
            if ($extensao !== '') {
                return $extensao;
            }
        }
    }

    return chamadosMimeParaExtensaoArquivo($mime);
}

function chamadosEscaparHtml($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function chamadosMontarHtmlPreviewBase($titulo, $downloadUrl, $conteudoHtml)
{
    $titulo = chamadosEscaparHtml($titulo ?: 'Arquivo');
    $downloadUrl = trim((string) $downloadUrl);
    $botaoDownload = $downloadUrl !== ''
        ? '<a class="preview-action" href="' . chamadosEscaparHtml($downloadUrl) . '" target="_blank" rel="noopener">Baixar arquivo</a>'
        : '';

    return '<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $titulo . '</title>
    <style>
        :root {
            color-scheme: light;
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2933;
        }

        .preview-page {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            padding: 18px;
            box-sizing: border-box;
            gap: 16px;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 18px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #eef3f8 100%);
            border: 1px solid #d9e2ec;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .preview-kicker {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #52606d;
            margin-bottom: 4px;
        }

        .preview-header h1 {
            margin: 0;
            font-size: 18px;
            line-height: 1.3;
            word-break: break-word;
        }

        .preview-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            padding: 10px 14px;
            border-radius: 999px;
            background: #1d4ed8;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .preview-action:hover {
            background: #1742b8;
            color: #fff;
            text-decoration: none;
        }

        .preview-body {
            flex: 1 1 auto;
            overflow: auto;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #d9e2ec;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            padding: 16px;
        }

        .preview-empty {
            padding: 24px;
            text-align: center;
            color: #52606d;
        }

        .preview-table-wrap {
            overflow: auto;
            margin: 0 0 24px;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .preview-table th,
        .preview-table td {
            border: 1px solid #cbd2d9;
            padding: 8px 10px;
            vertical-align: top;
        }

        .preview-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: left;
        }

        .preview-sheet h2 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .preview-doc p {
            margin: 0 0 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .preview-doc table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .preview-doc td,
        .preview-doc th {
            border: 1px solid #cbd2d9;
            padding: 8px 10px;
            vertical-align: top;
        }

        .preview-note {
            margin: 0 0 12px;
            color: #52606d;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="preview-page">
        <main class="preview-body">
            ' . $conteudoHtml . '
        </main>
    </div>
</body>
</html>';
}

function chamadosExtrairTextoDocxDoNodo(DOMNode $nodo)
{
    $texto = '';

    foreach ($nodo->childNodes as $filho) {
        if ($filho->nodeName === 'w:t' || $filho->nodeName === '#text') {
            $texto .= $filho->textContent;
            continue;
        }

        if ($filho->nodeName === 'w:tab') {
            $texto .= "\t";
            continue;
        }

        if ($filho->nodeName === 'w:br' || $filho->nodeName === 'w:cr') {
            $texto .= "\n";
            continue;
        }

        if ($filho->hasChildNodes()) {
            $texto .= chamadosExtrairTextoDocxDoNodo($filho);
        }
    }

    return $texto;
}

function chamadosRenderizarParagrafoDocx(DOMNode $paragrafo)
{
    $texto = trim(chamadosExtrairTextoDocxDoNodo($paragrafo));
    if ($texto === '') {
        return '';
    }

    return '<p>' . nl2br(chamadosEscaparHtml($texto)) . '</p>';
}

function chamadosRenderizarTabelaDocx(DOMNode $tabela, DOMXPath $xpath)
{
    $html = '<div class="preview-table-wrap"><table class="preview-table">';
    $linhas = $xpath->query('.//w:tr', $tabela);

    if (!$linhas || $linhas->length === 0) {
        return '';
    }

    foreach ($linhas as $linha) {
        $html .= '<tr>';
        $celulas = $xpath->query('./w:tc', $linha);
        if ($celulas) {
            foreach ($celulas as $celula) {
                $partes = array();
                foreach ($xpath->query('.//w:p', $celula) as $paragrafo) {
                    $textoParagrafo = trim(chamadosExtrairTextoDocxDoNodo($paragrafo));
                    if ($textoParagrafo !== '') {
                        $partes[] = $textoParagrafo;
                    }
                }

                $conteudo = trim(implode("\n", $partes));
                $html .= '<td>' . ($conteudo !== '' ? nl2br(chamadosEscaparHtml($conteudo)) : '&nbsp;') . '</td>';
            }
        }
        $html .= '</tr>';
    }

    $html .= '</table></div>';
    return $html;
}

function chamadosRenderizarPreviewDocx($conteudo, $nomeArquivo, $downloadUrl)
{
    $arquivoTemp = tempnam(sys_get_temp_dir(), 'chamados_docx_');
    if ($arquivoTemp === false) {
        return null;
    }

    file_put_contents($arquivoTemp, $conteudo);

    $zip = new ZipArchive();
    if ($zip->open($arquivoTemp) !== true) {
        @unlink($arquivoTemp);
        return null;
    }

    $documentoXml = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($arquivoTemp);

    if ($documentoXml === false || $documentoXml === '') {
        return null;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;

    libxml_use_internal_errors(true);
    $carregado = $dom->loadXML($documentoXml, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    if (!$carregado) {
        return null;
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $corpo = $xpath->query('/w:document/w:body')->item(0);
    if (!$corpo) {
        return null;
    }

    $partes = array();
    $totalTexto = '';

    foreach ($corpo->childNodes as $nodo) {
        if ($nodo->nodeName === 'w:p') {
            $paragrafoHtml = chamadosRenderizarParagrafoDocx($nodo);
            if ($paragrafoHtml !== '') {
                $partes[] = $paragrafoHtml;
                $totalTexto .= trim(chamadosExtrairTextoDocxDoNodo($nodo)) . "\n";
            }
            continue;
        }

        if ($nodo->nodeName === 'w:tbl') {
            $tabelaHtml = chamadosRenderizarTabelaDocx($nodo, $xpath);
            if ($tabelaHtml !== '') {
                $partes[] = $tabelaHtml;
            }
        }
    }

    $conteudoHtml = implode('', $partes);
    if ($conteudoHtml === '') {
        $conteudoHtml = '<div class="preview-empty">Nenhum conteúdo textual foi encontrado neste documento.</div>';
    } else {
        $conteudoHtml = '<div class="preview-doc">' . $conteudoHtml . '</div>';
    }

    return chamadosMontarHtmlPreviewBase($nomeArquivo, $downloadUrl, '<p class="preview-note">Visualização parcial do documento.</p>' . $conteudoHtml);
}

function chamadosRenderizarPreviewPlanilha($conteudo, $nomeArquivo, $downloadUrl)
{
    $arquivoTemp = tempnam(sys_get_temp_dir(), 'chamados_xls_');
    if ($arquivoTemp === false) {
        return null;
    }

    file_put_contents($arquivoTemp, $conteudo);

    try {
        $planilha = IOFactory::load($arquivoTemp);
    } catch (Throwable $e) {
        @unlink($arquivoTemp);
        return null;
    }

    @unlink($arquivoTemp);

    $partes = array();
    $abaIndex = 0;
    $limiteAbas = 5;
    $limiteLinhas = 200;
    $limiteColunas = 20;

    foreach ($planilha->getWorksheetIterator() as $aba) {
        if ($abaIndex >= $limiteAbas) {
            break;
        }

        $abaIndex++;

        $ultimaColuna = $aba->getHighestDataColumn();
        $ultimaColunaIndice = Coordinate::columnIndexFromString($ultimaColuna);
        if ($ultimaColunaIndice > $limiteColunas) {
            $ultimaColunaIndice = $limiteColunas;
        }

        $ultimaLinha = (int) $aba->getHighestDataRow();
        if ($ultimaLinha > $limiteLinhas) {
            $ultimaLinha = $limiteLinhas;
        }

        $partes[] = '<section class="preview-sheet">';
        $partes[] = '<h2>' . chamadosEscaparHtml($aba->getTitle()) . '</h2>';
        $partes[] = '<div class="preview-table-wrap"><table class="preview-table">';

        $partes[] = '<thead><tr><th>#</th>';
        for ($coluna = 1; $coluna <= $ultimaColunaIndice; $coluna++) {
            $partes[] = '<th>' . chamadosEscaparHtml(Coordinate::stringFromColumnIndex($coluna)) . '</th>';
        }
        $partes[] = '</tr></thead><tbody>';

        for ($linha = 1; $linha <= $ultimaLinha; $linha++) {
            $partes[] = '<tr><th>' . (int) $linha . '</th>';
            for ($coluna = 1; $coluna <= $ultimaColunaIndice; $coluna++) {
                $colunaLetra = Coordinate::stringFromColumnIndex($coluna);
                $celula = $aba->getCell($colunaLetra . $linha);
                $valor = $celula ? $celula->getFormattedValue() : '';
                $valor = trim((string) $valor);
                $partes[] = '<td>' . ($valor !== '' ? nl2br(chamadosEscaparHtml($valor)) : '&nbsp;') . '</td>';
            }
            $partes[] = '</tr>';
        }

        $partes[] = '</tbody></table></div></section>';
    }

    if (!$partes) {
        return null;
    }

    $conteudoHtml = implode('', $partes);
    return chamadosMontarHtmlPreviewBase($nomeArquivo, $downloadUrl, $conteudoHtml);
}

function chamadosRenderizarPreviewTexto($conteudo, $nomeArquivo, $downloadUrl)
{
    $texto = (string) $conteudo;
    $html = '<div class="preview-doc"><p>' . nl2br(chamadosEscaparHtml($texto)) . '</p></div>';
    return chamadosMontarHtmlPreviewBase($nomeArquivo, $downloadUrl, '<p class="preview-note">Visualização do conteúdo em texto puro.</p>' . $html);
}

function chamadosRenderizarPreviewNaoSuportado($nomeArquivo, $downloadUrl)
{
    $html = '<div class="preview-empty">';
    $html .= '<p>Este formato não possui pré-visualização automática neste sistema.</p>';
    $html .= '<p>Use o botão de download para abrir o arquivo completo.</p>';
    $html .= '</div>';

    return chamadosMontarHtmlPreviewBase($nomeArquivo, $downloadUrl, $html);
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit('Sessão expirada.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Arquivo inválido.');
}

$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';

try {
    $arquivo = ChamadosArquivos::instanciarPorId($id);

    if (!$arquivo) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }

    if (!empty($arquivo->id_chamado)) {
        $chamado = (new Chamados())->getChamado($arquivo->id_chamado);
        if (empty($chamado)) {
            http_response_code(403);
            exit('Sem permissão para acessar este arquivo.');
        }
    } elseif ((string)$arquivo->criado_por_id !== (string)ID_USER) {
        http_response_code(403);
        exit('Sem permissão para acessar este arquivo.');
    }

    $conteudo = (string) $arquivo->arquivo;
    $tipoArmazenado = trim((string)($arquivo->tipo ?? ''));
    $mime = chamadosExtensaoParaMimeArquivo($tipoArmazenado);
    if ($mime === '') {
        $mime = chamadosNormalizarMimeArquivo($tipoArmazenado);
    }
    if ($mime === '') {
        $mime = 'application/octet-stream';
    }
    $nomeSeguro = preg_replace('/[\r\n\x00]+/', '', (string)($arquivo->nome ?? 'arquivo'));
    $nomeSeguro = basename(trim($nomeSeguro)) ?: 'arquivo';
    $nomeCabecalho = str_replace(array('"', '\\'), '_', $nomeSeguro);
    $downloadUrl = '/webconfef/admin/content/chamados/download.php?id=' . urlencode((string) $id);
    $extensao = chamadosObterExtensaoArquivo($nomeSeguro, $tipoArmazenado);

    if ($isPreview) {
        $previewHtml = null;

        if ($extensao === 'txt') {
            $previewHtml = chamadosRenderizarPreviewTexto($conteudo, $nomeSeguro, $downloadUrl);
        } elseif (in_array($extensao, array('xls', 'xlsx', 'csv', 'ods'), true)) {
            $previewHtml = chamadosRenderizarPreviewPlanilha($conteudo, $nomeSeguro, $downloadUrl);
        } elseif (in_array($extensao, array('doc', 'docx'), true)) {
            $previewHtml = chamadosRenderizarPreviewDocx($conteudo, $nomeSeguro, $downloadUrl);
        }

        if ($previewHtml !== null) {
            header('X-Content-Type-Options: nosniff');
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="' . $nomeCabecalho . '.html"');
            echo $previewHtml;
            exit;
        }

        if (in_array($extensao, array('doc', 'docx', 'xls', 'xlsx', 'csv', 'ods', 'txt'), true)) {
            header('X-Content-Type-Options: nosniff');
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="' . $nomeCabecalho . '.html"');
            echo chamadosRenderizarPreviewNaoSuportado($nomeSeguro, $downloadUrl);
            exit;
        }
    }

    header('X-Content-Type-Options: nosniff');
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . ($isPreview ? 'inline' : 'attachment') . '; filename="' . $nomeCabecalho . '"');
    header('Content-Length: ' . strlen($conteudo));
    header('Content-Transfer-Encoding: binary');

    echo $conteudo;
    exit;
} catch (Throwable $e) {
    error_log('chamados download.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro interno.');
}
