<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use Exception;
use DateTime;

class ClasseBase implements \JsonSerializable
{
    public $queryCorrente = NULL;
    public $_filtros = array();
    public $_filtros_direto = array();
    public $_substituicoes = array();
    public $retornarComoArray = FALSE;
    public $_obrigatorios = array();
    public $_campos_remover_mascara = array();
    public $_campos_formato_data = array();
    public $_campos_formato_data_hora = array();
    public $_campos_formato_json = array();
    public $_campos_formato_moeda = array();
    public $_campos_distinct = array();
    public $_params_execute = array();
    public $gerar_log_query = FALSE;

    public $autor_obrigatorio = FALSE;
    public $ano_letivo_session;
    public $pagina_atual;
    public $itens_por_pagina;
    protected static $ignorar_permissao = false;
    protected static $ignorar_filtro_estado_conselho = false;

    /** @var PDO */
    protected $pdo;
    public $log_prioridade = NULL;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        if (!empty($this->_tabela['schema'])) {
            $this->_tabela['nome'] = $this->_tabela['schema'] . "." . $this->_tabela['nome'];
        }
    }

    /** @var array{name: string|null, chave_primaria: array, colunas: array} */
    protected $_tabela = [
        'nome' => null,
        'schema' => null,
        'chave_primaria' => [],
        'colunas' => []
    ];

    function jsonUnescapedUnicode($json)
    {
        $encoded = json_encode($json);
        $unescaped = preg_replace_callback('/\\\u(\w{4})/', function ($matches) {
            return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
        }, $encoded);
        return $unescaped;
    }

    public function get($nome)
    {
        return $this->$nome;
    }

    public function filtrarDestinatarios($responsaveis, $idsIgnorar = [])
    {
        $filtrados = array_filter($responsaveis, function ($usuario) use ($idsIgnorar) {
            $usuario = (array) $usuario;

            if (isset($usuario['id_usuario'])) {
                $id = (int) $usuario['id_usuario'];
            } elseif (isset($usuario['id'])) {
                $id = (int) $usuario['id'];
            } elseif (isset($usuario[0])) {
                $id = (int) $usuario[0];
            } else {
                return false;
            }

            if ($id <= 0) {
                return false;
            }

            return !in_array($id, $idsIgnorar);
        });

        return array_values($filtrados);
    }

    public function isDate($input, $format = 'Y-m-d')
    {
        return (DateTime::createFromFormat($format, $input) !== FALSE);
    }

    function parseDate($date, $outputFormat = 'd/m/Y')
    {
        if (empty($date)) {
            return '';
        }
        $formats = array(
            'd/m/Y',
            'd/m/Y H',
            'd/m/Y H:i',
            'd/m/Y H:i:s',
            'Y-m-d',
            'Y-m-d H',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'YmdHis'
        );
        foreach ($formats as $format) {
            $dateObj = DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                break;
            }
        }
        if ($dateObj === false) {
            throw new Exception('Data inválida: ' . $date);
        }
        return $dateObj->format($outputFormat);
    }

    function removerMascara($valor)
    {
        $caracteres = array(".", "/", "-", "(", ")", " ");
        return str_replace($caracteres, "", $valor);
    }

    function parseMoney($input)
    {
        if (substr($input, -3, 1) == ',') {
            $_search = array('.', ',');
            $_replace = array('', '.');
            $output = str_replace($_search, $_replace, $input);
            return $output;
        }
        return $input;
    }

    public function getColunas()
    {
        return $this->_tabela['colunas'];
    }

    public function getPermissao()
    {
        if (isset($this->_tabela['permissao'])) {
            return $this->_tabela['permissao'];
        } else {
            return false;
        }
    }

    public function addColuna($coluna)
    {
        $this->_tabela['colunas'][] = $coluna;
    }

    public function getNomeChavePrimaria()
    {
        if (count($this->_tabela['chave_primaria']) === 1) {
            return $this->_tabela['chave_primaria'][0];
        }
        return join(', ', $this->_tabela['chave_primaria']);
    }

    public function getNomeTabela()
    {
        $_tabela_nome = $this->_tabela['nome'];
        $_tabela_schema = $this->_tabela['schema'] ?? '';
        if (!empty($_tabela_schema)) {
            if (!str_contains($_tabela_nome, "$_tabela_schema.")) {
                $_tabela_nome = $_tabela_schema . "." . $_tabela_nome;
            }
        }
        return $_tabela_nome;
    }

    public function getQueryCorrente()
    {
        return $this->queryCorrente;
    }

    public function getQuerybase($campos = "*")
    {
        $class = get_called_class();
        $obj = new $class();
        $tablename = $obj->getNomeTabela();
        return "SELECT $campos FROM $tablename WHERE 1=1 ";
    }

    public static function carregarNomesColunas(ClasseBase $obj)
    {
        $tablename = $obj->getNomeTabela();
        $sql = "SHOW COLUMNS FROM $tablename";
        $_result = Dao::select($sql, NULL);
        foreach ($_result as $_row) {
            if ($_row['Field'] <> $obj->getNomeChavePrimaria()) {
                array_push($obj->_tabela['colunas'], $_row['Field']);
            }
        }
    }

    protected function extrairAliasTabelaPrincipal()
    {
        $query = $this->queryCorrente  ?? $this->getQuerybase();

        if (empty($query)) {
            return null;
        }

        // Expressão mais robusta: para ANTES de palavras que não são parte da definição da tabela
        $padrao = '/\bFROM\s+([^\s]+)(?:\s+(?:AS\s+)?(\w+))?(?=\s+(?:WHERE|JOIN|LEFT|RIGHT|INNER|OUTER|GROUP|ORDER|LIMIT|$))/i';

        if (preg_match($padrao, $query, $match)) {
            $tabela = trim($match[1]);
            $alias  = $match[2] ?? null;

            return [
                'tabela' => $tabela,
                'alias'  => $alias ?: $tabela
            ];
        }

        return null;
    }

    public static function habilitarIgnorarPermissao()
    {
        self::$ignorar_permissao = true;
    }

    public static function desabilitarIgnorarPermissao()
    {
        self::$ignorar_permissao = false;
    }

    protected static function ignorarPermissaoAtiva()
    {
        return self::$ignorar_permissao;
    }

    protected static function habilitarIgnorarFiltroEstadoConselho()
    {
        self::$ignorar_filtro_estado_conselho = true;
    }

    protected static function desabilitarIgnorarFiltroEstadoConselho()
    {
        self::$ignorar_filtro_estado_conselho = false;
    }

    protected static function ignorarFiltroEstadoConselhoAtiva()
    {
        return self::$ignorar_filtro_estado_conselho;
    }

    public static function executarComIgnorados(callable $callback, bool $ignorarPermissao = false, bool $ignorarFiltroEstadoConselho = false)
    {
        $estadoPermissao = self::$ignorar_permissao;
        $estadoFiltro = self::$ignorar_filtro_estado_conselho;

        if ($ignorarPermissao) {
            self::$ignorar_permissao = true;
        }

        if ($ignorarFiltroEstadoConselho) {
            self::$ignorar_filtro_estado_conselho = true;
        }

        try {
            return $callback();
        } finally {
            self::$ignorar_permissao = $estadoPermissao;
            self::$ignorar_filtro_estado_conselho = $estadoFiltro;
        }
    }

    protected function buscarIgnorandoFiltroEstadoConselho($retornarComoArray = false)
    {
        return self::executarComIgnorados(function () use ($retornarComoArray) {
            return $this->buscar($retornarComoArray);
        }, false, true);
    }

    public function buscar($retornarComoArray = false)
    {
        try {
            $estado_conselho = env('ESTADO_CONSELHO', $_ENV['ESTADO_CONSELHO'] ?? null);

            if (!empty($estado_conselho)) {
                $alias = $this->extrairAliasTabelaPrincipal();

                if ($alias) {
                    $tabela = $alias['tabela'];
                    $alias  = $alias['alias'];

                    $tem_coluna = Dao::tabelaTemColuna($tabela, 'estado_conselho');
                    if (!self::ignorarFiltroEstadoConselhoAtiva()) {
                        if ($tem_coluna) {
                            $this->filtrar("$alias.estado_conselho", $estado_conselho);
                        } else {
                            $tem_coluna = Dao::tabelaTemColuna($tabela, 'uf');
                            if ($tem_coluna) {
                                $this->filtrar("$alias.uf", $estado_conselho);
                            }
                        }
                    }
                }
            }

            if (!self::ignorarPermissaoAtiva() && !empty($this->_tabela['permissao']) && !verificaPermissao($this->_tabela['permissao'])) {
                $ignorar_permissao = self::ignorarPermissaoAtiva();
                if ($ignorar_permissao != true) {
                    if (!empty($this->_tabela['permissao'])) {
                        throw new Exception("Você não possui privilégios para acessar esses dados.");
                    }
                }
            }

            $this->retornarComoArray = $retornarComoArray;
            $_result = Dao::buscar($this);
            return $_result;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function instanciarPorId($id, $ignorar = [])
    {
        try {
            $class = get_called_class();
            $obj = new $class();
            $chave = $obj->getNomeChavePrimaria();
            $tabela = $obj->getNomeTabela();
            $obj->queryCorrente = "SELECT * FROM $tabela WHERE 1=1 ";
            $obj->filtrar($chave, $id);
            $results = $obj->buscar();
            if (empty($results)) {
                return null;
            }
            $row = $results[0];
            foreach ((array)$row as $campo => $valor) {
                if (property_exists($obj, $campo)) {
                    if (!in_array($campo, $ignorar)) {
                        $obj->$campo = $valor;
                    }
                }
            }
            return $obj;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function instanciarPorAtributo($value, $chave, $ignorar = [])
    {
        try {
            $class = get_called_class();
            $obj = new $class();
            $tabela = $obj->getNomeTabela();
            $colunas = $obj->getColunas();

            if (!in_array($chave, $colunas)) {
                throw new Exception("Atributo inválido para instanciar o objeto.");
            }

            $obj->queryCorrente = "SELECT * FROM $tabela WHERE 1=1 ";
            $obj->filtrar($chave, $value);
            $results = $obj->buscar();
            if (empty($results)) {
                return null;
            }
            $row = $results[0];
            foreach ((array)$row as $campo => $valor) {
                if (property_exists($obj, $campo)) {
                    if (!in_array($campo, $ignorar)) {
                        $obj->$campo = $valor;
                    }
                }
            }
            return $obj;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function getPrimitivoColuna($tabela, $coluna)
    {
        return Dao::getPrimitivoColuna($tabela, $coluna);
    }

    public function setPropriedadesDadosPost($fonte = INPUT_POST)
    {
        $_dados = filter_input_array($fonte);
        foreach ($_dados as $chave => $valor) {
            if (property_exists($this, $chave)) {
                if (is_array($valor)) {
                    $this->$chave = $_dados[$chave];
                } else {
                    $this->$chave = filter_input($fonte, $chave);
                    #var_dump("$chave = ". filter_input($fonte, $chave));
                }
            }
        }
    }

    public function formatarPropriedades()
    {
        $_propriedades_data_hora = $this->_campos_formato_data_hora;
        $_propriedades_data = $this->_campos_formato_data;
        $_propriedades_json = $this->_campos_formato_json;
        $_propriedades_moeda = $this->_campos_formato_moeda;
        $_propriedades_mascara = $this->_campos_remover_mascara;
        try {
            if (count($_propriedades_data)) {
                foreach ($_propriedades_data as $propriedade) {
                    if (!empty($this->$propriedade) && $this->isDate($this->$propriedade, 'd/m/Y')) {
                        $this->$propriedade = $this->parseDate($this->$propriedade, 'Y-m-d');
                    }
                }
            }
            if (count($_propriedades_data_hora)) {
                foreach ($_propriedades_data_hora as $propriedade) {
                    if (!empty($this->$propriedade) && $this->isDate($this->$propriedade, 'd/m/Y H:i:s')) {
                        $this->$propriedade = $this->parseDate($this->$propriedade, 'Y-m-d H:i:s');
                    }
                }
            }
            if (count($_propriedades_json)) {
                foreach ($_propriedades_json as $propriedade) {
                    if (is_array($this->$propriedade) && count($this->$propriedade) == 0 or empty($this->$propriedade)) {
                        $this->$propriedade = NULL;
                    }
                    if (is_array($this->$propriedade)) {
                        //$this->$propriedade = json_encode($this->$propriedade);
                        $this->$propriedade = $this->jsonUnescapedUnicode($this->$propriedade);
                    }
                }
            }
            if (count($_propriedades_moeda)) {
                foreach ($_propriedades_moeda as $propriedade) {
                    if (!empty($this->$propriedade)) {
                        $this->$propriedade = $this->parseMoney($this->$propriedade);
                    } else {
                        $this->$propriedade = 0.00;
                    }
                }
            }
            if (count($_propriedades_mascara)) {
                foreach ($_propriedades_mascara as $propriedade) {
                    if (!empty($this->$propriedade)) {
                        $this->$propriedade = $this->removerMascara($this->$propriedade);
                    }
                }
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function validarCamposObrigatorios()
    {
        if (property_exists($this, '_obrigatorios') && is_array($this->_obrigatorios) && count($this->_obrigatorios)) {
            foreach ($this->_obrigatorios as $campo => $descricao) {
                $conteudo_campo = trim($this->$campo);
                if (empty($conteudo_campo) and $conteudo_campo !== '0' and $conteudo_campo !== 0) {
                    throw new Exception("O campo $descricao deve ser preenchido.");
                }
            }
        }
    }

    public function setPropriedadesVaziasParaNull()
    {
        $_propriedades = $this->_tabela['colunas'];
        foreach ($_propriedades as $propriedade) {
            if (property_exists($this, $propriedade)) {
                // Se estiver vazio, define NULL
                if ($this->$propriedade === '') {
                    $this->$propriedade = NULL;
                }
                // Se for checkbox enviado como 'on', transforma em 1
                else if ($this->$propriedade === 'on') {
                    $this->$propriedade = 1;
                }
                // Se for qualquer outro valor booleano falso, define 0
                else if (in_array($this->$propriedade, [false, '0', 0], true)) {
                    $this->$propriedade = 0;
                }
            }
        }
    }

    public function ordenar($nm_campo, $direcao = 'asc')
    {
        //$campo_ordem = $this->getNmTabela() .'.'. $nm_campo;
        Dao::setOrdem($nm_campo, $direcao);
    }

    public function limitar($inicio, $qtde = "")
    {
        Dao::setLimite($inicio, $qtde);
    }

    public function agrupar($agrupamento)
    {
        Dao::setAgrupamento($agrupamento);
    }

    public function removerFiltros()
    {
        $this->_filtros = array();
        $this->_filtros_direto = array();
    }

    public function converterArray()
    {
        $_propriedades = get_object_vars($this);
        $nomePk = $this->getNomeChavePrimaria();
        $_propriedades[$nomePk] = $this->getValorChavePrimaria();

        foreach (array_keys($_propriedades) as $prop) {
            if (str_starts_with($prop, '_')) {
                unset($_propriedades[$prop]);
            }
        }

        $_remover = array(
            'queryCorrente',
            'retornarComoArray',
            'gerar_log_query',
            'autor_obrigatorio',
            'ano_letivo_session',
            'pagina_atual',
            'itens_por_pagina',
            'pdo',
            'log_prioridade',
            'dao'
        );
        foreach ($_remover as $prop) {
            unset($_propriedades[$prop]);
        }

        return $_propriedades;
    }

    public function jsonSerialize(): array
    {
        return $this->converterArray();
    }

    public function getValorChavePrimaria()
    {
        if (count($this->_tabela['chave_primaria']) === 1) {
            $chave_primaria = $this->getNomeChavePrimaria();
            $this_chave_primaria = $this->$chave_primaria;
            return $this_chave_primaria;
        } else if (count($this->_tabela['chave_primaria']) > 1) {
            die('A tabela ' . $this->getNomeTabela() . ' possui chave primária composta.');
        }
        die('A classe não possui o índice "chave_primaria" da propriedade "$_tabela" definido.');
    }

    public function setarParametroDoDataTable(&$_result)
    {
        // $dataTable = $_result;
        // $_result->draw = $dataTable->draw;
        // $_result->length = $dataTable->length;
        // $_result->start = $dataTable->start;
        // $_result->recordsTotal = $dataTable->recordsTotal;
        // $_result->recordsFiltered = $dataTable->recordsFiltered;
    }

    public function incluir()
    {
        try {
            $this->formatarPropriedades();
            $this->validarCamposObrigatorios();
            $this->setPropriedadesVaziasParaNull();

            if (!self::ignorarPermissaoAtiva() && !empty($this->_tabela['permissao']) && !verificaPermissao($this->getPermissao(), "Incluir")) {
                if (!empty($this->_tabela['permissao'])) {
                    $ignorar_permissao = self::ignorarPermissaoAtiva();
                    if ($ignorar_permissao != true) {
                        if (!empty($this->_tabela['permissao'])) {
                            throw new Exception("Você não possui permissão para realizar esta operação!");
                        }
                    }
                }
            }

            Dao::incluir($this);

            $_result = $this->converterArray();
            return $_result;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function aplicarFiltros($fonte = INPUT_POST)
    {
        if (filter_has_var($fonte, 'aplicarPaginacaoNoResultado')) {
            $dataTable = DataTable::gerarFromPost();
            Dao::setParamsObjDataTable($dataTable, $this);
        }

        $_dados = filter_input_array($fonte);

        if (filter_has_var($fonte, 'ordem') && isset($_dados['ordem']) && is_array($_dados['ordem'])) {
            foreach ($_dados['ordem'] as $campo => $direcao) {
                $this->ordenar($campo, $direcao);
            }
        }

        if (filter_has_var($fonte, 'retornarArray')) {
            $this->retornarComoArray = TRUE;
        }

        if (filter_has_var($fonte, 'aplicarDistinct')) {
            $this->aplicarDistinct($_dados['aplicarDistinct']);
        }

        if (filter_has_var($fonte, 'filtros') && isset($_dados['filtros']) && is_array($_dados['filtros'])) {
            $tipos_filtro_permitidos = [
                'MAIOR',
                'MENOR',
                'MAIOR_IGUAL',
                'MENOR_IGUAL',
                'DIFERENTE',
                'IN',
                'NOT_IN',
                'LIKE',
                'LIKE_START',
                'LIKE_END',
                'NOT LIKE',
                'NOT_LIKE_START',
                'NOT_LIKE_END',
                'IGUAL',
                'NOT_NULL',
            ];

            foreach ($_dados['filtros'] as $tipoFiltro => $_filtro) {
                $tipoFiltro = strtoupper(trim((string) $tipoFiltro));
                if (!in_array($tipoFiltro, $tipos_filtro_permitidos, true)) {
                    continue;
                }

                if (!is_array($_filtro)) {
                    continue;
                }

                foreach ($_filtro as $campo => $valor) {
                    if ($valor != '') {
                        $this->filtrar($campo, $valor, $tipoFiltro);
                    }
                }
            }
        }
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

        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/', $campo) === 1;
    }

    public function getOperadorSql($tipo)
    {
        $tipo = trim((string) $tipo);

        switch ($tipo) {

            case 'MAIOR':
                return '>';

            case 'MENOR':
                return '<';

            case 'MAIOR_IGUAL':
                return '>=';

            case 'MENOR_IGUAL':
                return '<=';

            case 'DIFERENTE':
            case 'notEqual':
                return '<>';

            case 'IGUAL':
            case 'equal':
                return '=';

            case 'IN':
                return 'IN';

            case 'NOT_IN':
                return 'NOT IN';

            case 'LIKE':
            case 'contains':
            case 'LIKE_START':
            case 'starts':
            case 'LIKE_END':
            case 'ends':
                return 'LIKE';

            case 'NOT LIKE':
            case 'notContains':
            case 'NOT_LIKE_START':
            case 'NOT_LIKE_END':
                return 'NOT LIKE';

            case 'empty':
                return 'IS NULL';

            case 'notEmpty':
            case 'NOT_NULL':
                return 'IS NOT NULL';

            default:
                return null;
        }
    }

    public function filtrar($nm_campo, $vl_valor, $tipo = 'IGUAL', $operador = "AND")
    {
        $campo = trim((string) $nm_campo);
        $count_ponto = substr_count($campo, ".");

        if ($count_ponto >= 2) {
            $partes = explode(".", $campo);
            array_shift($partes);
            $campo = implode(".", $partes);
        }

        $valor = is_array($vl_valor) ? filter_var_array($vl_valor) : filter_var($vl_valor);
        $operador = strtoupper($operador);
        $tipo = trim((string) $tipo);

        $operadores_permitidos = ['AND', 'OR'];

        if (!in_array($operador, $operadores_permitidos)) {
            throw new Exception("Operador não permitido!");
        }

        if (strtoupper($tipo) !== 'EXPLICITO' && !self::campoSqlSeguro($campo)) {
            throw new Exception("Campo de filtro nao permitido!");
        }

        switch ($tipo) {

            case 'MAIOR':
                $this->_filtros[" $operador $campo > ? "] = $valor;
                break;

            case 'MENOR':
                $this->_filtros[" $operador $campo < ? "] = $valor;
                break;

            case 'MAIOR_IGUAL':
                $this->_filtros[" $operador $campo >= ? "] = $valor;
                break;

            case 'MENOR_IGUAL':
                $this->_filtros[" $operador $campo <= ? "] = $valor;
                break;

            case 'DIFERENTE':
            case 'notEqual':
                $this->_filtros[" $operador $campo <> ? "] = $valor;
                break;

            case 'IGUAL':
            case 'equal':
                $this->_filtros[" $operador $campo = ? "] = $valor;
                break;

            case 'IN':
                $this->_filtros[" $operador $campo IN (" . implode(', ', array_fill(0, count($valor), '?')) . ") "] = $valor;
                break;

            case 'NOT_IN':
                $this->_filtros[" $operador $campo NOT IN (" . implode(', ', array_fill(0, count($valor), '?')) . ") "] = $valor;
                break;

            case 'LIKE':
            case 'contains':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) LIKE ? "] = "%$valor%";
                break;

            case 'LIKE_START':
            case 'starts':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) LIKE ? "] = "$valor%";
                break;

            case 'LIKE_END':
            case 'ends':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) LIKE ? "] = "%$valor";
                break;

            case 'NOT LIKE':
            case 'notContains':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) NOT LIKE ? "] = "%$valor%";
                break;

            case 'NOT_LIKE_START':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) NOT LIKE ? "] = "$valor%";
                break;

            case 'NOT_LIKE_END':
                $valor = mb_strtoupper($valor);
                $this->_filtros[" $operador UPPER($campo) NOT LIKE ? "] = "%$valor";
                break;

            case 'EMPTY':
                array_push($this->_filtros_direto, "AND ($campo IS NULL OR $campo = '') ");
                break;

            case 'notEmpty':
            case 'NOT_NULL':
                array_push($this->_filtros_direto, "AND ($campo IS NOT NULL AND $campo <> '') ");
                break;

            case 'EXPLICITO':
                array_push($this->_filtros_direto, "AND $valor ");
                break;
        }
    }

    public function substituir($chave, $valor, $tipo = 'IGUAL')
    {
        $chave = str_replace(':', '', $chave);

        $obj['chave'] = ":$chave";

        switch ($tipo) {

            case 'LIKE':
            case 'contains':
            case 'NOT LIKE':
            case 'notContains':
                $obj['valor'] = "%$valor%";
                break;

            case 'LIKE_START':
            case 'starts':
            case 'NOT_LIKE_START':
                $obj['valor'] = "$valor%";
                break;

            case 'LIKE_END':
            case 'ends':
            case 'NOT_LIKE_END':
                $obj['valor'] = "%$valor";
                break;

            default:
                $obj['valor'] = $valor;
                break;
        }

        $this->_substituicoes[] = $obj;
    }

    private function validarChavePrimaria()
    {
        $valorChave = $this->getValorChavePrimaria();
        if (empty($valorChave)) {
            throw new Exception("A chave primária do objeto (" . $this->getNomeChavePrimaria() . ") não foi carregada.");
        }
    }

    public function salvar()
    {
        try {
            $this->formatarPropriedades();
            $this->validarCamposObrigatorios();
            $this->validarChavePrimaria();
            $this->setPropriedadesVaziasParaNull();

            if (!self::ignorarPermissaoAtiva() && !empty($this->_tabela['permissao']) && !verificaPermissao($this->getPermissao(), "Alterar")) {
                if (!empty($this->_tabela['permissao'])) {
                    throw new Exception("Você não possui permissão para realizar esta operação");
                }
            }

            $row_count = Dao::salvar($this);
            if ($row_count) {
                $_result = $this->converterArray();
            }

            $message = $row_count > 0 ? "Dados atualizados com sucesso." : false;

            return ['tipo' => 'success', 'row_count' => $row_count, 'message'=> $message];
            
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function excluir()
    {
        try {
            $this->validarChavePrimaria();
            if (!self::ignorarPermissaoAtiva() && !empty($this->_tabela['permissao']) && !verificaPermissao($this->getPermissao(), "Excluir")) {
                if (!empty($this->_tabela['permissao'])) {
                    throw new Exception("Você não possui permissão para realizar esta operação!");
                }
            }
            $exclusao = Dao::excluir($this);
            if ($exclusao > 0) {
                return ['message' => 'Dados removidos com sucesso.', 'status' => 'success', 'result' => $exclusao];
            }
            return ['message' => 'Não foi possível remover o item solicitado.', 'status' => 'fail'];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function booleanQuery($query = "")
    {
        if (empty($query)) {
            return  "";
        }
        $parts = preg_split('/\s+/u', trim($query));
        $tokens = [];

        foreach ($parts as $p) {
            $p = trim($p);

            if ($p === '') continue;
            if (mb_strlen($p, 'UTF-8') < 3) continue;

            $tokens[] = '+' . $p . '*';
        }

        return $tokens ? implode(' ', $tokens) : '';
    }
}
