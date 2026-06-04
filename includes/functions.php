<?php

/*function logAction($table, $action, $id_usuario, $data = null) {
    require_once($_SERVER['include_once __DIR__ . '../config.php';'] .  '/intranet2/admin/content/config.php');

    $stmt = $pdo->prepare("INSERT INTO TBLLogs_Sistemas (data_modificacao, Tabela, Action, id_usuario, data) VALUES (:data_modificacao,:table_name, :action, :row_id, :data)");
    
    // Obtém o esquema da tabela
    $schema_stmt = $pdo->prepare("DESCRIBE $table");
    $schema_stmt->execute();
    $table_schema = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);
  
    // Cria uma string com as informações modificadas na tabela
    $data_str = "";
    if ($data) {
      foreach ($table_schema as $column) {
        if (isset($data[$column['Field']])) {
          $data_str .= $column['Field'] . " = " . $data[$column['Field']] . ", ";
        }
      }
      $data_str = substr($data_str, 0, -2);
    }
    
    // Insere o registro na tabela de log
    $stmt->bindValue(':data_modificacao', date('Y-m-d H:i:s'));
    $stmt->bindParam(':table_name', $table);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':row_id', $id_usuario);
    $stmt->bindParam(':data', $data_str);
    $stmt->execute();
  }

  function executeActionAndLog($actionType, $tableName, $pdoStatement, $id_usuario) {
    
    // Executa a ação
    $pdoStatement->execute();

    // Registra os detalhes na tabela de log

    $logStatement = $pdo->prepare("INSERT INTO TBLLogs_Sistemas (data_modificacao, Action, Tabela, data, id_usuario) VALUES (:data_hora, :tipo_acao, :tabela, :detalhes, :id_usuario)");

    // Define as informações que serão registradas no log
    $logDetails = [];
    $logDetails['tipo_acao'] = $actionType;
    $logDetails['tabela'] = $tableName;

    if ($actionType == 'INSERT') {
        $logDetails['detalhes'] = 'Nova linha adicionada: ' . json_encode($pdoStatement->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($actionType == 'UPDATE') {
        $logDetails['detalhes'] = 'Linha atualizada: ' . json_encode($pdoStatement->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($actionType == 'DELETE') {
        $logDetails['detalhes'] = 'Linha excluída: ' . json_encode($pdoStatement->fetchAll(PDO::FETCH_ASSOC));
    }

    // Insere os detalhes no log
    $logStatement->execute([
        ':data_hora' => date('Y-m-d H:i:s'),
        ':tipo_acao' => $actionType,
        ':tabela' => $tableName,
        ':detalhes' => $logDetails['detalhes'],
        ':id_usuario' => $id_usuario,
    ]);
}

function logAction2($table, $action, $data) {
    global $pdo; // Permite o acesso à conexão criada acima
    
    // Cria a string de descrição para o log
    $description = $action . " na tabela " . $table . " - Dados afetados: " . implode(", ", $data);
    
    // Insere o log na tabela de logs
    $stmt = $pdo->prepare("INSERT INTO TBLLogs_Sistemas (data_modificacao, data) VALUES (:data_hora, :descricao)");
    $stmt->bindParam(':data_hora', date('Y-m-d H:i:s'));
    $stmt->bindParam(':descricao', $description);
    $stmt->execute();
  }*/



if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Define @usuario_atual no MySQL de forma segura (sem interpolação de string).
 * Substitui o padrão: $pdo->query("SET @usuario_atual = $usuario_id")
 */
function setUsuarioAtual(PDO $pdo): void
{
    $uid = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    $stmt = $pdo->prepare("SET @usuario_atual = ?");
    $stmt->execute([$uid]);
}

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logAction3($pdo, $tableName, $action, $idColumnName, $idValue, $id_usuario)
{
  $stmt = $pdo->prepare("SHOW COLUMNS FROM $tableName");
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $columnNames = [];
  foreach ($rows as $row) {
    $type = strtolower($row['Type']);
    if (!strpos($type, 'blob') && !strpos($type, 'longblob') && !strpos($type, 'binary') && !strpos($type, 'varbinary')) {
      $columnNames[] = $row['Field'];
    }
  }
  $columns = implode(',', $columnNames);

  $stmt = $pdo->prepare("SELECT $columns FROM $tableName WHERE $idColumnName = :idValue");
  $stmt->bindParam(':idValue', $idValue);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    return; // Não há linha correspondente para registrar
  }




  $description = "$action: {$tableName} ({$idColumnName}={$idValue}) - ";

  foreach ($row as $columnName => $columnValue) {
    if ($columnValue !== null && strpos($columnValue, "\0") === false) {
      $cleanValue = str_replace(["\0"], '', $columnValue); // Limpeza de null bytes
      if ($date = DateTime::createFromFormat('Y-m-d H:i:s', $cleanValue)) {
        $columnValue = $date->format('Y-m-d H:i:s');
        $description .= "{$columnName}={$columnValue}, ";
      } else {
        $description .= "{$columnName}={$cleanValue}, ";
      }
    } else {
      $description .= "{$columnName}=NULL, ";
    }
  }

  $description = rtrim($description, ', '); // Remove a vírgula extra no final

  $stmt = $pdo->prepare("INSERT INTO TBLLogs_Sistemas (data_modificacao, Tabela, Action, data, id_usuario) VALUES (:data_hora, :tabela, :acao, :descricao, :id_usuario)");
  $stmt->bindParam(':data_hora', date('Y-m-d H:i:s'));
  $stmt->bindParam(':tabela', $tableName);
  $stmt->bindParam(':acao', $action);
  $stmt->bindParam(':descricao', $description);
  $stmt->bindParam(':id_usuario', $id_usuario);
  $stmt->execute();
}

function exibirAvisoEFechar($mensagem = 'Acesso negado.')
{
  echo '<!DOCTYPE html><html lang="pt-BR"><head>
        <meta charset="utf-8">
        <title>Aviso</title>
        <style>
            body { font-family: sans-serif; background: #f4f4f4; color: #444; display: flex; align-items: center; justify-content: center; height: 100vh; }
            .aviso { background: #fffbe6; border: 1px solid #ffe58f; padding: 40px 60px; border-radius: 10px; box-shadow: 0 2px 10px #0001; font-size: 1.2rem; text-align: center; }
        </style>
    </head><body>
        <div class="aviso">' . $mensagem . '<br><small>Esta janela será fechada automaticamente.</small></div>
        <script>
            setTimeout(function() {
                window.close();
            }, 3000);
        </script>
    </body></html>';
  exit;
}

function carregarPermissoesSessao(): bool
{
  if (isset($_SESSION['Permissoes']) && is_array($_SESSION['Permissoes'])) {
    return true;
  }

  if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    return false;
  }

  global $pdo;
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    return false;
  }

  try {
    $stmt = $pdo->prepare('SELECT Rotina, Consulta, Incluir, Excluir, Alterar FROM TBLPersistemas WHERE Usuario = :id');
    $stmt->execute(['id' => (int) $_SESSION['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $_SESSION['Permissoes'] = array_values($rows ?: []);
    return !empty($_SESSION['Permissoes']);
  } catch (Throwable $e) {
    return false;
  }
}

function temPermissao($rotina, $acao)
{
  if (!carregarPermissoesSessao()) {
    return false;
  }

  foreach ($_SESSION['Permissoes'] as $permissao) {
    if ($permissao['Rotina'] === $rotina && $permissao[$acao] == 1) {
      return true;
    }
  }

  return false;
}

function search($haystack, $needle, $index = NULL)
{

  if (is_null($haystack) || (!is_array($haystack) && !($haystack instanceof \Traversable))) {
    return -1;
  }

  $arrayIterator = new \RecursiveArrayIterator($haystack);

  $iterator = new \RecursiveIteratorIterator($arrayIterator);

  while ($iterator->valid()) {

    if (((isset($index) and ($iterator->key() == $index)) or
      (!isset($index))) and ($iterator->current() == $needle)) {

      return $arrayIterator->key();
    }

    $iterator->next();
  }

  return -1;
}

function verificaPermissao($codigoRotina, $tipo_operacao = "Consulta")
{
  $logado = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
  if (!$logado) return false;

  if (!carregarPermissoesSessao()) {
    return false;
  }

  $indiceRotina = search($_SESSION["Permissoes"], $codigoRotina, 'Rotina');
  if ($indiceRotina === -1) return false;

  $temPermissao = search($_SESSION["Permissoes"][$indiceRotina], '1', $tipo_operacao) != -1;

  return $temPermissao;
}

class EmailService
{
  private \PDO $pdo;

  public function __construct(\PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Gera o HTML completo do e‑mail a partir do assunto e do corpo.
   */
  private function buildTemplate(string $subject, string $body): string
  {
    $preheader = htmlspecialchars(strip_tags($body), ENT_QUOTES, 'UTF-8');
    $subjectEsc = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

    $html  = '<!DOCTYPE html>';
    $html .= '<html lang="pt-BR">';
    $html .= '<head>';
    $html .= '  <meta charset="UTF-8">';
    $html .= '  <meta name="viewport" content="width=device-width,initial-scale=1.0">';
    $html .= "  <title>{$subjectEsc}</title>";
    $html .= '  <style>';
    $html .= '    body{margin:0;padding:0;background:#f4f4f4;}';
    $html .= '    .wrapper{width:100%;table-layout:fixed;padding:20px 0;}';
    $html .= '    .container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;';
    $html .= '               box-shadow:0 2px 8px rgba(0,0,0,0.1);}';
    $html .= '    .header{padding:30px 0;text-align:center;background:#fff;}';
    $html .= '    .content{padding:30px;font-family:Arial,sans-serif;color:#333;line-height:1.6;font-size:15px;}';
    $html .= '    .footer{padding:20px 30px;font-family:Arial,sans-serif;font-size:12px;color:#777;';
    $html .= '             text-align:center;border-top:1px solid #eee;background:#fff;}';
    $html .= '    .btn td{background:#0055A2;border-radius:4px;text-align:center;}';
    $html .= '    .btn a{display:block;padding:12px 24px;font-family:Arial,sans-serif;';
    $html .= '           color:#fff;text-decoration:none;font-weight:bold;}';
    $html .= '  </style>';
    $html .= '</head>';
    $html .= '<body>';
    // invisível preheader
    $html .= "<div style=\"display:none;font-size:1px;color:#f4f4f4;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;\">"
      . $preheader
      . '</div>';
    // wrapper externo
    $html .= '<table class="wrapper" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">';
    // container principal
    $html .= '<table class="container" width="100%" cellpadding="0" cellspacing="0">';
    // header com logo
    $html .= '  <tr><td class="header" style="font-family:Arial,sans-serif;font-size:24px;font-weight:bold;color:#0055A2;">Sistema';
    $html .= '  </td></tr>';
    // conteúdo
    $html .= '  <tr><td class="content">';
    $html .=      $body;
    // botão bulletproof
    $html .= '<table class="btn" cellpadding="0" cellspacing="0" align="center" style="margin:30px auto;"><tr>';
    $html .= '<td>';
    $html .= '  <a href="/adminlte-painel">Acessar Sistema</a>';
    $html .= '</td></tr></table>';
    $html .= '  </td></tr>';
    // footer
    $html .= '  <tr><td class="footer">';
    $html .= '    <p>Este é um e-mail automático do Sistema.</p>';
    $html .= '    <p><a href="/" style="color:#0055A2;text-decoration:none;">'
      . 'Sistema</a></p>';
    $html .= '  </td></tr>';
    $html .= '</table>';        // fecha container
    $html .= '</td></tr></table>'; // fecha wrapper externo
    $html .= '</body></html>';

    return $html;
  }

  /**
   * Enfileira e‑mail na tabela EmailQueue para cada destinatário informado.
   *
   * @param string   $sistema    Identificador do sistema (ex.: 'Avisos_FundoDesenvolvimento')
   * @param string   $subject    Assunto do e‑mail
   * @param string   $bodyPlain  Corpo da mensagem em HTML (sem template)
   * @param string[] $recipients Array de e‑mails obrigatória
   * @throws \Exception Se não houver destinatários
   */
  public function queueEmails(
    string $sistema,
    string $subject,
    string $bodyPlain,
    array $recipients
  ): void {
    if (empty($recipients)) {
      throw new \Exception("Nenhum destinatário informado.");
    }

    // gera o HTML completo com template
    $fullHtml = $this->buildTemplate($subject, $bodyPlain);

    // prepara inserção
    $sql  = "INSERT INTO EmailQueue (to_email, subject, body, sistema) ";
    $sql .= "VALUES (:to_email, :subject, :body, :sistema)";
    $stmt = $this->pdo->prepare($sql);

    $this->pdo->beginTransaction();
    foreach ($recipients as $email) {
      $stmt->execute([
        'to_email' => $email,
        'subject'  => $subject,
        'body'     => $fullHtml,
        'sistema'  => $sistema,
      ]);
    }
    $this->pdo->commit();
  }
}

if (!function_exists('getCREF')) {
  function getCREF(string $estadoConselho): string
  {
    static $mapa = [
      'BR' => 'Painel',
      'RJ' => 'CREF1/RJ',
      'RS' => 'CREF2/RS',
      'SC' => 'CREF3/SC',
      'SP' => 'CREF4/SP',
      'CE' => 'CREF5/CE',
      'MG' => 'CREF6/MG',
      'DF' => 'CREF7/DF',
      'AM' => 'CREF8/AM-AC-RO-RR',
      'PR' => 'CREF9/PR',
      'PB' => 'CREF10/PB',
      'MS' => 'CREF11/MS',
      'PE' => 'CREF12/PE',
      'BA' => 'CREF13/BA',
      'GO' => 'CREF14/GO-TO',
      'PI' => 'CREF15/PI',
      'RN' => 'CREF16/RN',
      'MT' => 'CREF17/MT',
      'PA' => 'CREF18/PA-AP',
      'AL' => 'CREF19/AL',
      'SE' => 'CREF20/SE',
      'MA' => 'CREF21/MA',
      'ES' => 'CREF22/ES'
    ];
    $estadoConselho = strtoupper(trim($estadoConselho));
    return $mapa[$estadoConselho] ?? 'Desconhecido';
  }
}

function sqlCaseCref(string $field): string
{
  return "CASE {$field}
    WHEN 'BR' THEN 'Painel'
    WHEN 'RJ' THEN 'CREF1/RJ'
    WHEN 'RS' THEN 'CREF2/RS'
    WHEN 'SC' THEN 'CREF3/SC'
    WHEN 'SP' THEN 'CREF4/SP'
    WHEN 'CE' THEN 'CREF5/CE'
    WHEN 'MG' THEN 'CREF6/MG'
    WHEN 'DF' THEN 'CREF7/DF'
    WHEN 'AM' THEN 'CREF8/AM-AC-RO-RR'
    WHEN 'PR' THEN 'CREF9/PR'
    WHEN 'PB' THEN 'CREF10/PB'
    WHEN 'MS' THEN 'CREF11/MS'
    WHEN 'PE' THEN 'CREF12/PE'
    WHEN 'BA' THEN 'CREF13/BA'
    WHEN 'GO' THEN 'CREF14/GO-TO'
    WHEN 'PI' THEN 'CREF15/PI'
    WHEN 'RN' THEN 'CREF16/RN'
    WHEN 'MT' THEN 'CREF17/MT'
    WHEN 'PA' THEN 'CREF18/PA-AP'
    WHEN 'AL' THEN 'CREF19/AL'
    WHEN 'SE' THEN 'CREF20/SE'
    WHEN 'MA' THEN 'CREF21/MA'
    WHEN 'ES' THEN 'CREF22/ES'
    ELSE 'Desconhecido' END";
}

function gerarId($min = 1000, $max = 9999999)
{
  return random_int($min, $max);
}


function passToCurrency($num = 0)
{
  if (is_string($num)) {
    if (str_contains($num, 'R$')) {
      return $num;
    }
  }

  $num = number_format((float)$num, 2, '.', '');

  if (!is_numeric($num)) {
    return 0;
  }

  $strSplit = explode('.', (string)$num);
  $valor = $num ? (string)$num : '0';

  $valor = str_replace('.', '', $valor);
  $valor = str_replace(',', '.', $valor);
  $valor = str_replace('R$', '', $valor);
  $valor = (float)trim($valor);

  $decLen = strlen(end($strSplit));

  if ($decLen === 2) {
    $valor = $valor / 100;
  }

  if ($decLen === 1) {
    $valor = $valor / 10;
  }

  return 'R$ ' . number_format($valor, 2, ',', '.');
}

function passToNumber($str = '0')
{
  $str = (string)$str;
  $str = preg_replace('/[^\d.,]/', '', $str);

  $hasCommas = str_contains($str, ',');
  $hasDots = str_contains($str, '.');

  if ($hasCommas && !$hasDots) {
    $number = str_replace(',', '.', $str);
  } elseif (!$hasCommas && $hasDots) {
    $number = (float)$str;
  } elseif ($hasCommas && $hasDots) {
    $number = str_replace('.', '', $str);
    $number = str_replace(',', '.', $number);
  } else {
    $number = $str;
  }

  return (float)$number;
}

function sismsg_sanitize_html(string $html): string
{
  $html = trim($html);
  if ($html === '') return '';

  // Normaliza para UTF-8
  if (!mb_detect_encoding($html, 'UTF-8', true)) {
    $html = mb_convert_encoding($html, 'UTF-8', 'auto');
  }

  libxml_use_internal_errors(true);

  $doc = new DOMDocument();
  // Wrapper para o DOMDocument aceitar fragmento HTML
  $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

  $allowedTags = [
    'div' => true,
    'p' => true,
    'br' => true,
    'span' => true,
    'b' => true,
    'strong' => true,
    'i' => true,
    'em' => true,
    'u' => true,
    's' => true,
    'ul' => true,
    'ol' => true,
    'li' => true,
    'blockquote' => true,
    'pre' => true,
    'code' => true,
    'h1' => true,
    'h2' => true,
    'h3' => true,
    'h4' => true,
    'h5' => true,
    'h6' => true,
    'hr' => true,
    'a' => true,
  ];

  $allowedAttrs = [
    'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
    // demais tags: sem atributos (você pode liberar class se precisar)
  ];

  $walker = function (DOMNode $node) use (&$walker, $doc, $allowedTags, $allowedAttrs) {
    if ($node->nodeType === XML_ELEMENT_NODE) {
      $tag = strtolower($node->nodeName);

      if (!isset($allowedTags[$tag])) {
        // Remove a tag mas preserva texto/filhos
        $frag = $doc->createDocumentFragment();
        while ($node->firstChild) $frag->appendChild($node->firstChild);
        $node->parentNode->replaceChild($frag, $node);
        return;
      }

      // Remove atributos perigosos (on*, style etc)
      if ($node->hasAttributes()) {
        $toRemove = [];
        foreach ($node->attributes as $attr) {
          $name = strtolower($attr->name);
          if (strpos($name, 'on') === 0) {
            $toRemove[] = $attr->name;
            continue;
          } // onclick, onerror...
          if ($name === 'style') {
            $toRemove[] = $attr->name;
            continue;
          }

          $allowedForTag = $allowedAttrs[$tag] ?? [];
          if (!isset($allowedForTag[$name])) {
            $toRemove[] = $attr->name;
          }
        }
        foreach ($toRemove as $name) $node->removeAttribute($name);
      }

      // Sanitiza href de links
      if ($tag === 'a') {
        $href = $node->getAttribute('href');
        $hrefTrim = trim($href);

        // Permite http(s), mailto, #, /relativo
        $ok =
          $hrefTrim === '' ||
          $hrefTrim[0] === '#' ||
          $hrefTrim[0] === '/' ||
          preg_match('#^(https?://|mailto:)#i', $hrefTrim);

        if (!$ok) $node->removeAttribute('href');

        // Se target=_blank, força rel seguro
        if (strtolower($node->getAttribute('target')) === '_blank') {
          $node->setAttribute('rel', 'noopener noreferrer');
        }
      }
    }

    // percorre filhos (cópia porque removemos nós)
    $children = [];
    foreach ($node->childNodes as $c) $children[] = $c;
    foreach ($children as $c) $walker($c);
  };

  $root = $doc->getElementsByTagName('div')->item(0);
  if (!$root) return '';

  $walker($root);

  // Retorna somente o conteúdo dentro do wrapper <div>
  $out = '';
  foreach ($root->childNodes as $child) {
    $out .= $doc->saveHTML($child);
  }

  libxml_clear_errors();
  return $out;
}

if (!function_exists('addGroupHeader')) {
  function addGroupHeader(\TCPDF $pdf, string $label): void
  {
    $margins = $pdf->getMargins();
    $pageWidth = $pdf->getPageWidth();
    $effectiveWidth = $pageWidth - ($margins['left'] + $margins['right']);

    $groupHeaderHeight = $pdf->getStringHeight($effectiveWidth, $label);

    $firstProjectHeader = 'acc';
    $firstProjectHeaderHeight = $pdf->getStringHeight($effectiveWidth, $firstProjectHeader);
    $tableHeaderHeight = 10;
    $minDataRowHeight = 10;
    $minGroupBlock = $groupHeaderHeight + $firstProjectHeaderHeight + $tableHeaderHeight + $minDataRowHeight;

    if ($pdf->GetY() + $minGroupBlock > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
      $pdf->AddPage();
    }

    $pdf->SetFillColor(220, 220, 220);
    $pdf->MultiCell(
      $effectiveWidth,
      $groupHeaderHeight,
      $label,
      0,
      'L',
      1,
      1,
      '',
      '',
      true,
      0,
      false,
      true,
      $groupHeaderHeight,
      'M',
      true
    );
    $pdf->Ln(2);
    $pdf->SetFillColor(255, 255, 255);
  }
}

if (!function_exists('getEstadoConselhoNomeExibicao')) {
  function getEstadoConselhoNomeExibicao($estadoConselho)
  {
    $mapaEstadoConselho = [
      'BR' => 'Painel',
      'RJ' => 'CREF1/RJ',
      'RS' => 'CREF2/RS',
      'SC' => 'CREF3/SC',
      'SP' => 'CREF4/SP',
      'CE' => 'CREF5/CE',
      'MG' => 'CREF6/MG',
      'DF' => 'CREF7/DF',
      'AM' => 'CREF8/AM-AC-RO-RR',
      'PR' => 'CREF9/PR',
      'PB' => 'CREF10/PB',
      'MS' => 'CREF11/MS',
      'PE' => 'CREF12/PE',
      'BA' => 'CREF13/BA',
      'GO' => 'CREF14/GO-TO',
      'PI' => 'CREF15/PI',
      'RN' => 'CREF16/RN',
      'MT' => 'CREF17/MT',
      'PA' => 'CREF18/PA-AP',
      'AL' => 'CREF19/AL',
      'SE' => 'CREF20/SE',
      'MA' => 'CREF21/MA',
      'ES' => 'CREF22/ES',
    ];

    return $mapaEstadoConselho[$estadoConselho] ?? 'Desconhecido';
  }
}

if (!function_exists('exibirMensagemTela')) {
  function exibirMensagemTela(string $mensagem, int $statusCode = 404, string $tituloPagina = 'Relatorio'): void
  {
    if (!headers_sent()) {
      http_response_code($statusCode);
      header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') . '</title>
    </head>
    <body>
        <p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>
    </body>
    </html>';
    exit;
  }
}

if (!function_exists('formatarMoedaBR')) {
  function formatarMoedaBR($valor): string
  {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
  }
}

if (!function_exists('normalizarTextoCelulaTabela')) {
  function normalizarTextoCelulaTabela($valor): string
  {
    if ($valor === null) {
      return '-';
    }

    $texto = trim((string)$valor);
    return $texto !== '' ? $texto : '-';
  }
}

if (!function_exists('formatarDataBRSePossivel')) {
  function formatarDataBRSePossivel($valor, string $formato = 'd/m/Y'): string
  {
    if ($valor === null || trim((string)$valor) === '') {
      return '-';
    }

    $timestamp = strtotime((string)$valor);
    return $timestamp ? date($formato, $timestamp) : normalizarTextoCelulaTabela($valor);
  }
}

if (!function_exists('renderTituloSecaoTabela')) {
  function renderTituloSecaoTabela(\TCPDF $pdf, float $larguraUtil, string $tituloSecao): void
  {
    $espacoAntesTitulo = 2.0;
    $margensPagina = $pdf->getMargins();
    if ($pdf->GetY() > (($margensPagina['top'] ?? 0) + 0.1) && $espacoAntesTitulo > 0) {
      if ($pdf->GetY() + $espacoAntesTitulo > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
        $pdf->AddPage();
      } else {
        $pdf->Ln($espacoAntesTitulo);
      }
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->setCellPaddings(0, 0, 0, 0);
    $alturaTitulo = $pdf->getStringHeight($larguraUtil, $tituloSecao);

    if ($pdf->GetY() + $alturaTitulo > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
      $pdf->AddPage();
    }

    $pdf->MultiCell(
      $larguraUtil,
      $alturaTitulo,
      $tituloSecao,
      0,
      'L',
      0,
      1,
      $margensPagina['left'] ?? '',
      '',
      true,
      0,
      false,
      false,
      $alturaTitulo,
      'M',
      true
    );
    $pdf->Ln(1);
  }
}

if (!function_exists('renderTabelaMultilinha')) {
  function renderTabelaMultilinha(
    \TCPDF $pdf,
    float $larguraUtil,
    array $cabecalhos,
    array $largurasColunas,
    array $linhas,
    array $alinhamentosColunas = [],
    string $mensagemSemRegistros = 'Nenhum registro encontrado.',
    $aoQuebrarPagina = null,
    $resolverEstiloCelula = null
  ): void {
    $totalColunas = count($cabecalhos);
    if ($totalColunas === 0 || $totalColunas !== count($largurasColunas)) {
      throw new InvalidArgumentException('Cabecalhos e larguras das colunas estao invalidos.');
    }

    if (empty($alinhamentosColunas)) {
      $alinhamentosColunas = array_fill(0, $totalColunas, 'L');
    }

    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.3);
    $pdf->setCellPaddings(1.2, 1.0, 1.2, 1.0);
    $pdf->SetCellHeightRatio(1);

    $renderCabecalho = function () use ($pdf, $cabecalhos, $largurasColunas, $totalColunas) {
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetFillColor(230, 230, 230);
      $pdf->SetTextColor(0, 0, 0);

      $alturasCabecalho = [];
      foreach ($cabecalhos as $indiceColuna => $textoCabecalho) {
        $alturasCabecalho[] = $pdf->getStringHeight($largurasColunas[$indiceColuna], normalizarTextoCelulaTabela($textoCabecalho));
      }
      $alturaLinhaCabecalho = max($alturasCabecalho) + 1;

      foreach ($cabecalhos as $indiceColuna => $textoCabecalho) {
        $isLast = ($indiceColuna === $totalColunas - 1);
        $pdf->MultiCell(
          $largurasColunas[$indiceColuna],
          $alturaLinhaCabecalho,
          normalizarTextoCelulaTabela($textoCabecalho),
          1,
          'C',
          1,
          $isLast ? 1 : 0,
          '',
          '',
          true,
          0,
          false,
          true,
          $alturaLinhaCabecalho,
          'M',
          false
        );
      }

      $pdf->SetFont('helvetica', '', 8);
    };

    $renderCabecalho();

    if (empty($linhas)) {
      $pdf->SetFont('helvetica', '', 9);
      $pdf->MultiCell($larguraUtil, 8, $mensagemSemRegistros, 1, 'L', 0, 1, '', '', true, 0, false, true, 8, 'M', true);
      return;
    }

    $margemAlturaLinha = 1.5;

    foreach ($linhas as $indiceLinha => $linha) {
      $linhaNormalizada = [];
      for ($i = 0; $i < $totalColunas; $i++) {
        $linhaNormalizada[] = normalizarTextoCelulaTabela($linha[$i] ?? null);
      }

      $alturasCelulas = [];
      foreach ($linhaNormalizada as $indiceColuna => $textoCelula) {
        $alturasCelulas[] = $pdf->getStringHeight($largurasColunas[$indiceColuna], $textoCelula);
      }
      $alturaLinha = max($alturasCelulas) + $margemAlturaLinha;

      if ($pdf->GetY() + $alturaLinha > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
        $pdf->AddPage();
        if (is_callable($aoQuebrarPagina)) {
          $aoQuebrarPagina();
        }
        $renderCabecalho();
      }

      foreach ($linhaNormalizada as $indiceColuna => $textoCelula) {
        $isLast = ($indiceColuna === $totalColunas - 1);
        $alinhamento = $alinhamentosColunas[$indiceColuna] ?? 'L';
        $usarPreenchimento = 0;

        if (is_callable($resolverEstiloCelula)) {
          $estiloCelula = $resolverEstiloCelula($indiceLinha, $indiceColuna, $textoCelula, $linhaNormalizada);
          if (is_array($estiloCelula)) {
            if (isset($estiloCelula['textColor']) && is_array($estiloCelula['textColor']) && count($estiloCelula['textColor']) === 3) {
              $pdf->SetTextColor($estiloCelula['textColor'][0], $estiloCelula['textColor'][1], $estiloCelula['textColor'][2]);
            } else {
              $pdf->SetTextColor(0, 0, 0);
            }

            if (isset($estiloCelula['fillColor']) && is_array($estiloCelula['fillColor']) && count($estiloCelula['fillColor']) === 3) {
              $pdf->SetFillColor($estiloCelula['fillColor'][0], $estiloCelula['fillColor'][1], $estiloCelula['fillColor'][2]);
            }

            if (!empty($estiloCelula['fill'])) {
              $usarPreenchimento = 1;
            }
          } else {
            $pdf->SetTextColor(0, 0, 0);
          }
        } else {
          $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->MultiCell(
          $largurasColunas[$indiceColuna],
          $alturaLinha,
          $textoCelula,
          1,
          $alinhamento,
          $usarPreenchimento,
          $isLast ? 1 : 0,
          '',
          '',
          true,
          0,
          false,
          true,
          $alturaLinha,
          'M',
          false
        );
        $pdf->SetTextColor(0, 0, 0);
      }
    }
  }
}
