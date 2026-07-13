<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$headers = array_change_key_case($headers, CASE_LOWER);

$csrf_token = (string) ($headers['x-csrf-token'] ?? $_POST['csrf_token'] ?? '');
$csrf_session = (string) ($_SESSION['csrf_token'] ?? '');

if ($csrf_session === '' || $csrf_token === '' || !hash_equals($csrf_session, $csrf_token)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['tipo' => 'erro', 'status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit;
}

header('Content-Type: application/json');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    throw new ErrorException(
        "$errstr in $errfile on line $errline",
        0,
        $errno,
        $errfile,
        $errline
    );
});

try {
    global $pdo;
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    if ($_SESSION['loggedin'] !== true) {
        throw new Exception('Acesso não autorizado. Faça login para continuar.');
    }

    $_result = [];

    $className = $_POST['objeto'] ?? null;

    $permitidas = [
        'Usuarios',
        'Funcionario',
        'Salas',
        'Agendamento',
        'AnoExercicio',
        'AgendamentoParticipantes',
        'FundoDesenvProjetos',
        'FundoDesenvProjetosArquivos',
        'FundoDesenvProjetosObjetivos',
        'FundoDesenvProjetosObjetivosMetas',
        'FundoDesenvProjetosObjetivosMetasEtapas',
        'FundoDesenvProjetosSolicitacaoAlteracao',
        'FundoDesenvProjetosSolicitacaoAlteracaoResposta',
        'FundoDesenvProjetosTramitacoes',
        'FundoDesenvPrestaContas',
        'FundoDesenvPrestaContasTipos',
        'FundoDesenvPrestaContasArquivosPagamentosTramitacoes',
        'FundoDesenvPrestaContasExtensaoPrazo',
        'FundoDesenvPrestaContasParametros',
        'FundoDesenvPrestaContasLogRecibo',
        'FundoDesenvPrestaContasArquivosPagamentosMateriaisBensServicos',
        'FundoDesenvPrestaContasArquivosPagamentosDiarias',
        'FundoDesenvPrestaContasArquivosPagamentosGratificacoes',
        'FundoDesenvPrestaContasArquivosPagamentosPassagens',
        'FundoDesenvPrestaContasArquivosTemp',
        'FundoDesenvPrestaContasArquivosPagamentosMateriaisBensServicosItens',
        'FundoDesenvPrestaContasArquivosPagamentosGratificacoesItens',
        'FundoDesenvPrestaContasArquivosPagamentosPassagensItens',
        'FundoDesenvPrestaContasArquivosPagamentosArquivos',
        'FundoDesenvPrestaContasArquivosBens',
        'FundoDesenvPrestaContasArquivosBensItens',
        'FundoDesenvPrestaContasArquivosDemonstrativoExecucao',
        'FundoDesenvPrestaContasArquivosDemonstrativoExecucaoItens',
        'FundoDesenvProjetosConfSis',
        'FundoDesenvProjetosPrevisaoDosRecursosFinanceiros',
        'FundoDesenvProjetosDespesas',
        'FundoDesenvProjetosReceitaPrevista',
        'FundoDesenvProjetosEquipeExecutora',
        'FundoDesenvProjetosEquipeExecutoraMembro',
        'FundoDesenvProjetosEtapasComentarios',
        'Protocolo',
        'Empresas',
        'ProtocoloLocalizacao',
        'ProtocoloArquivados',
        'ProtocoloTramitacoes',
        'Notificacoes',
        'Rotinas',
        'Icones',
        'Persistemas',
        'Portais',
        'Rotas',
        'PortaisRotas',
    ];

    if (!$className) {
        throw new Exception("Parâmetros inválidos");
    }

    if (!in_array($className, $permitidas)) {
        throw new Exception("Classe não permitida.");
    }


    $namespace = "Classes\\";
    $className = $namespace . $className;


    $obj = new $className();


    foreach ($_POST as $key => $value) {
        if (property_exists($obj, $key)) {
            $obj->$key = $value;
        }
    }

    //Métodos vindos do JS bloqueados pois precisam de validações
    $metodos_nao_permitidos = [
        'salvar',
        'incluir',
        'excluir',
        'pesquisar',
        'buscar',
        'criaEmail',
        'instanciarPorId',
        'getColunas',
        'getPermissao',
        'addColuna',
        'getNomeChavePrimaria',
        'getNomeTabela',
        'getQueryCorrente',
        'carregarNomesColunas',
        'extrairAliasTabelaPrincipal',
        'habilitarIgnorarPermissao',
        'desabilitarIgnorarPermissao',
        'ignorarPermissaoAtiva',
        'setPropriedadesDadosPost',
        'formatarPropriedades',
        'validarCamposObrigatorios',
        'setPropriedadesVaziasParaNull',
        'ordenar',
        'limitar',
        'agrupar',
        'removerFiltros',
        'converterArray',
        'getValorChavePrimaria',
        'getProtocolo',
        'setarParametroDoDataTable',
        'aplicarFiltros',
        'filtrar',
        'substituir',
        'validarChavePrimaria',
        'instanciarPorAtributo',
        'getSetor',
        'getSetorPorUsuario',
        'getUsuariosPorSetor',
        'getAtualizacoes',
        'getChecklistPorChamado',
        'getUltimaAtualizacao',
        'executarComIgnorados',
    ];

    $metodo = $_POST['metodo'] ?? null;

    if (in_array($metodo, $metodos_nao_permitidos)) {
        throw new Exception("Método não permitido.");
    }

    if (!$metodo || !method_exists($obj, $metodo) || !(new ReflectionMethod($obj, $metodo))->isPublic()) {
        throw new Exception("Método inválido ou não existe.");
    }

    $obj->aplicarFiltros();

    $params = $_POST;
    unset($params['objeto'], $params['metodo']);
    $_result = call_user_func_array([$obj, $metodo], $params ? array_values($params) : []);
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $rbErr) {
            error_log('[controle_default][rollback] ' . $rbErr->getMessage());
        }
    }

    try {
        $loggerClass = '\\Classes\\Logs';

        if (!class_exists($loggerClass, false)) {
            $LogsFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/classes/Logs.php';
            if (is_file($LogsFile)) {
                require_once $LogsFile;
            }
        }

        if (class_exists($loggerClass) && method_exists($loggerClass, 'gravarErro')) {
            $objeto_metodo = (string)($_POST['objeto'] ?? '') . '->' . (string)($_POST['metodo'] ?? '');
            $loggerClass::gravarErro($e, $objeto_metodo, $_POST);
        }
    } catch (Throwable $logErr) {
        error_log('[controle_default][Logs] ' . $logErr->getMessage());
    }

    if (http_response_code() === 200) {
        http_response_code(500);
    }

    if (
        $e instanceof PDOException ||
        stripos($e->getMessage(), 'SQLSTATE[') !== false
    ) {
        if (
            $e->getCode() === '22001' ||
            stripos($e->getMessage(), 'Data too long') !== false
        ) {
            echo json_encode([
                'tipo' => 'sql',
                'sqlstate' => '22001',
                'erro' => $e->getMessage()
            ]);
        } else {
            echo json_encode([
                'tipo' => 'sql',
                'sqlstate' => $e->getCode(),
                // 'erro' => $e->getMessage(),
                'erro' => 'Erro desconhecido. Por favor, entre em contato com o suporte.'
            ]);
        }
    } else {
        echo json_encode([
            'tipo' => 'aplicacao',
            'erro' => $e->getMessage()
        ]);
    }

    exit;
}

echo json_encode($_result);
