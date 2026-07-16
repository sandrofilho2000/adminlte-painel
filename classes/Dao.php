<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use Classes\ClasseBase;
use PDO;
use Exception;
use PDOException;
use RuntimeException;

class Dao
{
    private static $_questions = [];
    private static $_ordenacao = [];
    private static $limite = '';
    private static $agrupamento = '';
    private static $campos_distinct = [];
    private static $dataTable = null;
    public static $gerar_log_query = false;
    public static $ignorar_datatable = false;
    private $pdo;
    private static $_resultDataTable = [
        'draw' => null,
        'recordsTotal' => null,
        'recordsFiltered' => null,
        'data' => []
    ];

    private static function capturarEstado(): array
    {
        return [
            '_questions' => self::$_questions,
            '_ordenacao' => self::$_ordenacao,
            'limite' => self::$limite,
            'agrupamento' => self::$agrupamento,
            'campos_distinct' => self::$campos_distinct,
            'dataTable' => self::$dataTable,
            'gerar_log_query' => self::$gerar_log_query,
            'ignorar_datatable' => self::$ignorar_datatable,
            '_resultDataTable' => self::$_resultDataTable,
        ];
    }

    private static function restaurarEstado(array $estado): void
    {
        self::$_questions = $estado['_questions'] ?? [];
        self::$_ordenacao = $estado['_ordenacao'] ?? [];
        self::$limite = $estado['limite'] ?? '';
        self::$agrupamento = $estado['agrupamento'] ?? '';
        self::$campos_distinct = $estado['campos_distinct'] ?? [];
        self::$dataTable = $estado['dataTable'] ?? null;
        self::$gerar_log_query = $estado['gerar_log_query'] ?? false;
        self::$ignorar_datatable = $estado['ignorar_datatable'] ?? false;
        self::$_resultDataTable = $estado['_resultDataTable'] ?? [
            'draw' => null,
            'recordsTotal' => null,
            'recordsFiltered' => null,
            'data' => []
        ];
    }

    public static function executarComEstadoIsolado(callable $callback)
    {
        $estadoAnterior = self::capturarEstado();
        self::resetarPropriedades();

        try {
            return $callback();
        } finally {
            self::restaurarEstado($estadoAnterior);
        }
    }

    private static function getOrdem()
    {
        $ordenacao = "";
        if (count(self::$_ordenacao)) {
            $ordenacao .= " ORDER BY " . implode(', ', self::$_ordenacao) . " ";
        }
        return $ordenacao;
    }

    private static function getLimite($query = "")
    {
        if (empty($query)) {
            return "";
        }

        $query = strtolower($query);

        if (str_contains($query, 'limit')) {
            return "";
        }

        return self::$limite;
    }

    private static function getDataTableLimite($query = "")
    {
        if (empty($query)) {
            return "";
        }

        $query = strtolower($query);

        if (str_contains($query, 'limit')) {
            return "";
        }
        $limite = $_SESSION['datatable_limit'];
        return $limite;
    }

    private static function nomeParametroSeguro($nome): string
    {
        $nome = preg_replace('/[^A-Za-z0-9_]/', '_', (string) $nome);

        if ($nome === '' || preg_match('/^[A-Za-z_]/', $nome) !== 1) {
            $nome = 'param_' . $nome;
        }

        return $nome;
    }

    private static function getSubstituicao($obj, $query)
    {
        $substituicoes = $obj->_substituicoes;

        if (empty($query)) {
            return false;
        } else if (empty($substituicoes)) {
            return $query;
        }

        $valoresSubstituicao = [];
        foreach ($substituicoes as $substituicao) {
            if (!isset($substituicao['chave'])) {
                continue;
            }
            // Repetir a mesma chave deve reaproveitar o ultimo valor informado.
            $valoresSubstituicao[$substituicao['chave']] = $substituicao['valor'] ?? null;
        }

        $filtrosOriginais = array_values(self::$_questions);
        $indiceFiltro = 0;
        $parametrosFinais = [];

        $query = preg_replace_callback('/\?|\:[A-Za-z_][A-Za-z0-9_]*/', function ($match) use (
            &$indiceFiltro,
            $filtrosOriginais,
            &$parametrosFinais,
            $valoresSubstituicao
        ) {
            $token = $match[0];

            if ($token === '?') {
                if (!array_key_exists($indiceFiltro, $filtrosOriginais)) {
                    throw new Exception("Quantidade de parâmetros de filtro não corresponde aos placeholders da query.");
                }
                $parametrosFinais[] = $filtrosOriginais[$indiceFiltro];
                $indiceFiltro++;
                return ' ? ';
            }

            if (array_key_exists($token, $valoresSubstituicao)) {
                $parametrosFinais[] = $valoresSubstituicao[$token];
                return ' ? ';
            }

            return $token;
        }, $query);

        if ($indiceFiltro !== count($filtrosOriginais)) {
            throw new Exception("Quantidade de parâmetros de filtro não corresponde aos placeholders da query.");
        }

        self::$_questions = $parametrosFinais;
        $obj->_substituicoes = [];
        return $query;
    }

    private static function getGroupBy()
    {
        if (!empty(self::$agrupamento)) {
            return 'GROUP BY ' . self::$agrupamento;
        }
        return '';
    }

    private static function getCondicoes(ClasseBase $obj)
    {
        $condicoes = '';
        self::$_questions = array();
        if (count($obj->_filtros)) {
            foreach ($obj->_filtros as $clausula => $valor) {
                $condicoes .= $clausula;
                if (is_array($valor)) {
                    foreach ($valor as $val) {
                        array_push(self::$_questions, $val);
                    }
                } else {
                    array_push(self::$_questions, $valor);
                }
            }
        }
        if (count($obj->_filtros_direto)) {
            $condicoes .= implode(' ', $obj->_filtros_direto);
        }
        $obj->_filtros = array();
        $obj->_filtros_direto = array();
        return $condicoes;
    }

    private static function getQueryDistinct($query)
    {
        if (count(self::$campos_distinct) == 0) {
            return $query;
        }
        $campos = join(', ', self::$campos_distinct);
        $query_distinct = "SELECT DISTINCT $campos FROM ({$query}) tabela ";
        self::$campos_distinct = array();
        return $query_distinct;
    }

    public static function setParamsObjDataTable(DataTable $dataTable, $obj = NULL)
    {
        if ($dataTable->start != '' && $dataTable->length != '' && $dataTable->length != '-1') {
            self::setLimite($dataTable->start, $dataTable->length, $dataTable->draw);
        }

        if (count($dataTable->_order)) {
            foreach ($dataTable->_order as $ordem) {
                $campoOrdem = $dataTable->getNameColumn($ordem['column']);
                if (!empty($campoOrdem)) {
                    self::setOrdem($campoOrdem, $ordem['dir']);
                }
            }
        }

        $_condicoesBuscaGlobal = array();
        $_condicoesBuscaColuna = array();

        foreach ($dataTable->_columns as $indice => $_column) {
            if ($_column['searchable'] == 'true' or $_column['searchable'] == true) {
                $campoBusca = trim((string) ($_column['name'] ?? $_column['data'] ?? ''));
                $pesquisaColuna = $_column["search"]["value"] ?? '';
                $pesquisaGlobal = $dataTable->_search;
                $columnControl = !empty($_column["columnControl"]) ? $_column["columnControl"] : null;

                if (!empty($pesquisaGlobal)) {
                    if (!self::campoSqlSeguro($campoBusca)) {
                        continue;
                    }

                    $searchLogic = "LIKE";
                    $operadorSql = $obj->getOperadorSql($searchLogic);
                    $parametroBusca = self::nomeParametroSeguro($campoBusca) . '_global_' . $indice;
                    $_condicoesBuscaGlobal[] = "{$campoBusca} $operadorSql :{$parametroBusca}";
                    $obj->substituir($parametroBusca, $pesquisaGlobal, $searchLogic);
                }

                if (!empty($pesquisaColuna)) {
                    if (!self::campoSqlSeguro($campoBusca)) {
                        continue;
                    }

                    $searchLogic = !empty($columnControl['search']) ? $columnControl['search']['logic'] : "LIKE";
                    $operadorSql = $obj->getOperadorSql($searchLogic);
                    $parametroBusca = self::nomeParametroSeguro($campoBusca) . '_coluna_' . $indice;
                    $_condicoesBuscaColuna[] = "{$campoBusca} $operadorSql :{$parametroBusca}";
                    $obj->substituir($parametroBusca, $pesquisaColuna, $searchLogic);
                }
            }
        }

        if (count($_condicoesBuscaGlobal)) {
            $tx_condicoes = '(' . join(' OR ', $_condicoesBuscaGlobal) . ')';
            $obj->filtrar(date('U') . '_global', $tx_condicoes, 'EXPLICITO');
        }

        if (count($_condicoesBuscaColuna)) {
            $tx_condicoes = '(' . join(' AND ', $_condicoesBuscaColuna) . ')';
            $obj->filtrar(date('U') . '_coluna', $tx_condicoes, 'EXPLICITO');
        }

        self::$dataTable = $dataTable;
    }

    public static function getPrimitivoColuna($tabela, $coluna)
    {
        if (empty($tabela) || empty($coluna)) {
            return [];
        }

        $pdo = self::getPDO();

        if (strpos($tabela, '.') !== false) {
            [$schema, $table] = explode('.', $tabela, 2);
            $sql = "SHOW COLUMNS FROM `{$schema}`.`{$table}` LIKE :coluna";
        } else {
            $sql = "SHOW COLUMNS FROM `{$tabela}` LIKE :coluna";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['coluna' => $coluna]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $primitivo = $resultado ? $resultado['Type'] : null;
        return $primitivo;
    }

    public static function buscar(ClasseBase $obj)
    {
        //var_dump($obj); die();
        try {
            $query = $obj->getQueryCorrente();
            if (empty($query)) {
                $query = 'SELECT ';
                $chaves = $obj->getNomeChavePrimaria();
                if (!empty($chaves)) {
                    $query .= $chaves;
                }
                $_colunas = $obj->getColunas();
                if (count($_colunas)) {
                    if (!empty($chaves)) {
                        $query .= ', ';
                    }
                    $query .= join(', ', $obj->getColunas());
                }
                $query .= ' FROM ' . $obj->getNomeTabela() . ' WHERE 1=1 ';
            }

            $className = get_class($obj);

            $query .= self::getCondicoes($obj);
            $query .= self::getGroupBy();
            $query .= self::getOrdem();
            $query = self::getSubstituicao($obj, $query);
            $query = self::getQueryDistinct($query);

            $tipo = (gettype($obj) == 'object' && !$obj->retornarComoArray) ? get_class($obj) : NULL;
            self::$gerar_log_query = $obj->gerar_log_query;
            $_result = self::select($query, array_values(self::$_questions), $tipo, $className);
            return $_result;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function salvar(ClasseBase $obj)
    {
        $_nm_campos = $obj->getColunas();
        $_values = array();
        $_campos = array();
        $_chaves_primarias = array();
        #prepara os campos a serem atualizados, constantes do indice 'campos' do array $_tabela da classe
        foreach ($_nm_campos as $nm_campo) {
            array_push($_values, $obj->get($nm_campo));
            array_push($_campos, "$nm_campo = ?");
        }
        #prepara os campos que sao chaves primarias, constantes do indice 'campos' do array $_tabela da classe que pode ser composta
        $chaves_primarias = $obj->getNomeChavePrimaria();
        foreach (explode(', ', $chaves_primarias) as $chave) {
            array_push($_values, $obj->$chave);
            array_push($_chaves_primarias, "$chave = ?");
        }
        self::$gerar_log_query = $obj->gerar_log_query;
        $query = "UPDATE {$obj->getNomeTabela()} SET " . join(", ", $_campos) . " WHERE " . join(" AND ", $_chaves_primarias);

        if (ESTADO_CONSELHO !== '' && ESTADO_CONSELHO !== 'BR') {
            if (self::tabelaTemColuna($obj->getNomeTabela(), 'estado_conselho')) {
                $query .= " AND estado_conselho = '" . ESTADO_CONSELHO . "'";
            }
        }

        try {
            return self::update($query, $_values);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function excluir(ClasseBase $obj)
    {
        $_values = array();
        $_chaves_primarias = array();
        $nomeTabela = $obj->getNomeTabela();
        $chavesPrimarias = explode(', ', $obj->getNomeChavePrimaria());
        foreach ($chavesPrimarias as $chave) {
            array_push($_values, $obj->$chave);
            array_push($_chaves_primarias, "$chave = ?");
        }
        $query = "DELETE FROM $nomeTabela WHERE " . join(', ', $_chaves_primarias);

        if (ESTADO_CONSELHO !== '' && ESTADO_CONSELHO !== 'BR') {
            if (self::tabelaTemColuna($nomeTabela, 'estado_conselho')) {
                $query .= " AND estado_conselho = '" . ESTADO_CONSELHO . "'";
            }
        }

        self::$gerar_log_query = $obj->gerar_log_query;
        try {
            return self::delete($query, $_values);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function incluir(ClasseBase $obj)
    {
        $_campos = $obj->getColunas();
        $_values = array();
        foreach ($_campos as $campo) {
            $tipo = gettype($obj->get($campo));
            if (!empty($obj->get($campo))) {
                $contar = strlen($obj->get($campo));
            }
            array_push($_values, $obj->get($campo));
        }
        $valChavePrimaria = $obj->getValorChavePrimaria();
        if (!empty($valChavePrimaria)) {
            if (!in_array($obj->getNomeChavePrimaria(), $_campos)) {
                array_push($_campos, $obj->getNomeChavePrimaria());
            }
            if (!in_array($obj->getValorChavePrimaria(), $_values)) {
                array_push($_values, $obj->getValorChavePrimaria());
            }
        }
        $questionMarks = join(', ', array_pad(array(), count($_values), "?"));
        $query = "INSERT INTO {$obj->getNomeTabela()} (" . join(', ', $_campos) . ") VALUES ($questionMarks)";
        $log = self::debugSQL($query, $_values);
        $log = preg_replace('/\s+/', ' ', $log);
        try {
            $id = $obj->getNomeChavePrimaria();
            $obj->$id = self::insert($query, $_values);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function sanitizeRichHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $sanitized = preg_replace(
            [
                '#<(script|style|iframe|object|embed|form|meta|link|base)(?:[^>]*)>.*?</\1>#is',
                '#<(script|style|iframe|object|embed|form|meta|link|base)(?:[^>]*)/?>#is',
                '/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                '/\s(?:href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\1/i',
                '/\sstyle\s*=\s*(["\'])(?:(?!\1).)*(expression|javascript:)(?:(?!\1).)*\1/i',
            ],
            [
                '',
                '',
                '',
                '',
                '',
            ],
            $html
        );

        return is_string($sanitized) ? $sanitized : '';
    }

    private static function valorAssociadoAoCampo($chave, ?array $campos): bool
    {
        return $campos === null || in_array((string) $chave, $campos, true);
    }

    private static function sanitizarValorHtml($valor, ?array $campos = null, $chaveAtual = null)
    {
        if (is_string($valor)) {
            return self::valorAssociadoAoCampo($chaveAtual, $campos)
                ? self::sanitizeRichHtml($valor)
                : $valor;
        }

        if (is_array($valor)) {
            foreach ($valor as $chave => $item) {
                $valor[$chave] = self::sanitizarValorHtml($item, $campos, $chave);
            }

            return $valor;
        }

        if (is_object($valor)) {
            foreach (get_object_vars($valor) as $chave => $item) {
                $valor->$chave = self::sanitizarValorHtml($item, $campos, $chave);
            }

            return $valor;
        }

        return $valor;
    }

    public static function sanitizeHtmlFields($row, ?array $fields = null)
    {
        return self::sanitizarValorHtml($row, $fields);
    }

    private static function contarResultado($sqlOriginal, $parametros = NULL)
    {
        $stmt = self::getPDO();
        $stmt = $stmt->prepare("SELECT COUNT(*) as total FROM ($sqlOriginal) x");
        $stmt->execute(array_values($parametros));
        $result =  (int) ($stmt->fetchColumn());
        $stmt->closeCursor();
        return $result;
    }

    public static function select($sql, $params = [], $tipo = null, $className = "")
    {
        try {
            if (!empty(self::$dataTable) && $GLOBALS['className'] == $className) {
                self::$dataTable->recordsTotal = self::contarResultado($sql, $params);
                self::$dataTable->recordsFiltered = self::$dataTable->recordsTotal;
                $sql .= self::getLimite($sql);
                $stmt = self::getPDO()->prepare($sql);
                $log = self::debugSQL($sql, $params);
                $log = preg_replace('/\s+/', ' ', $log);
                $stmt->execute($params);
                unset($_SESSION['datatable_limit']);
                if (empty($tipo)) {
                    self::$dataTable->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    self::$dataTable->data = $stmt->fetchAll(PDO::FETCH_CLASS, $tipo);
                }

                self::$dataTable->data = self::sanitizeHtmlFields(self::$dataTable->data);

                $result = self::$dataTable;
            } else {
                $sql .= self::getLimite($sql);

                $stmt = self::getPDO()->prepare($sql);

                $log = self::debugSQL($sql, $params);
                $log = preg_replace('/\s+/', ' ', $log);

                $stmt->execute($params);
                $result = empty($tipo)
                    ? $stmt->fetchAll(PDO::FETCH_ASSOC)
                    : $stmt->fetchAll(PDO::FETCH_CLASS, $tipo);

                $result = self::sanitizeHtmlFields($result);
            }
            self::resetarPropriedades();
            return $result;
        } catch (PDOException $ex) {
            throw new Exception($ex->getMessage());
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private static function getPDO(): PDO
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new RuntimeException("PDO não inicializado.");
        }
        return $pdo;
    }

    public static function tabelaTemColuna($tabela, $coluna): bool
    {
        $pdo = self::getPDO();

        $partesTabela = explode('.', (string) $tabela, 2);
        $nomeTabela = array_pop($partesTabela);
        $nomeEsquema = $partesTabela[0] ?? null;

        if (
            preg_match('/^[A-Za-z0-9_]+$/', $nomeTabela) !== 1
            || ($nomeEsquema !== null && preg_match('/^[A-Za-z0-9_]+$/', $nomeEsquema) !== 1)
        ) {
            throw new \InvalidArgumentException('Nome de tabela ou esquema inválido.');
        }

        $sql = 'SELECT 1
              FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = COALESCE(?, DATABASE())
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1';

        $parametros = [$nomeEsquema, $nomeTabela, (string) $coluna];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $tem_coluna = $stmt->fetchColumn() !== false;
        return $tem_coluna;
    }

    private static function debugSQL($sql, $_params = null)
    {
        if (empty($_params)) return $sql;
        $_parts = explode('?', $sql);
        $query = '';
        foreach ($_parts as $i => $part) {
            $query .= $part;
            if (isset($_params[$i])) {
                $query .= "'{$_params[$i]}' ";
            }
        }
        return $query;
    }

    public static function carregarNomesColunas(ClasseBase $obj)
    {
        $tablename = $obj->getNomeTabela();
        $sql = "SHOW COLUMNS FROM $tablename";
        $_result = Dao::select($sql, []);
        foreach ($_result as $_row) {
            if ($_row['Field'] != $obj->getNomeChavePrimaria()) {
                $obj->addColuna($_row['Field']);
            }
        }
    }

    public static function insert($sql, $params = null, $omitir_codificacao = false)
    {
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        return self::getPDO()->lastInsertId();
    }

    public static function update($sql, $params = null, $omitir_codificacao = false)
    {
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        $row_count =  $stmt->rowCount();
        return $row_count;
    }

    public static function delete($sql, $params = null)
    {
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private static function resetarPropriedades()
    {
        self::$_questions = [];
        self::$_ordenacao = [];
        self::$agrupamento = '';
        self::$limite = '';
        self::$_resultDataTable = [
            'draw' => null,
            'recordsTotal' => null,
            'recordsFiltered' => null,
            'data' => []
        ];
    }

    public static function setLimite($inicio, $qtde = "", $draw = 1)
    {
        $inicio = max(0, (int) $inicio);
        $limite  = "LIMIT $inicio";

        if ($qtde !== '' && $qtde !== null) {
            $qtde = min(1000, max(0, (int) $qtde));
            $limite  .= ", $qtde";
        }

        self::$limite = $limite;
    }

    private static function campoSqlSeguro($campo): bool
    {
        if (!is_string($campo)) {
            return false;
        }

        $campo = trim($campo);

        if ($campo === '') {
            return false;
        }

        $preg_match = preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/', $campo) === 1;
        return $preg_match;
    }

    private static function direcaoOrdenacaoValida($direcao): ?string
    {
        $direcao = strtoupper(trim((string) $direcao));

        if (in_array($direcao, ['ASC', 'DESC'], true)) {
            return $direcao;
        }

        return null;
    }

    public static function setOrdem($campo, $direcao)
    {
        $campo = trim((string) $campo);
        $direcao = self::direcaoOrdenacaoValida($direcao);

        if ($direcao === null || !self::campoSqlSeguro($campo)) {
            return;
        }

        self::$_ordenacao[] = "$campo " . $direcao;
    }

    public static function setAgrupamento($agrupamento)
    {
        self::$agrupamento = $agrupamento;
    }
}
