<?php
namespace Classes;

class DataTable {
    public $draw;
    public $length;
    public $start;
    public $recordsTotal;
    public $recordsFiltered;
    public $data;
    public $classe;
    public $metodo;
    public $objeto;
    /**
     * Array dos arrays de índices:
     * 'data' nome da variável que retornará o resultado para o valor coluna;
     * 'name' opcional, nome da coluna no DataTable
     * 'orderable' 
     * 'search'
     * 'searchable'
     * @var type 
     */
    public $_columns = array();
    /**
     * Array dos array de índices:
     * 'column' - indica o número da coluna a ser ordenada
     * 'dir' - indica a direção da ordenação: asc ou desc
     * exemplo enviado pelo POST:
     * order[0][column]	= 0
     * order[0][dir] = desc
     * order[1][column]	= 1
     * order[1][dir] = asc
     */
    public $_order = array();
    
    public $_search = array();
    
    public function __construct(){
        
    }
    /* public function __construct($_dados = NULL) {
        if(is_null($_dados) or !is_array($_dados)){
            die('O construtor da classe '. __CLASS__ .' exige os parâmetros como o array enviado via POST pelo componente do DataTable.');
        }
        $this->draw = $_dados['draw'];
        $this->start = $_dados['start'];
        $this->length = $_dados['length'];
        $this->_order = $_dados['order'];
        $this->_columns = $_dados['columns'];
        $this->recordsTotal = 0;
        $this->recordsFiltered = 0;
    } */
    public function getOrdenacao(){
        $str_ordenacao = '';
        if(count($this->_order)){
            $_ordenacao = array();
            foreach($this->_order as $ordem){
                $campo = trim((string) $this->getNameColumn($ordem['column']));
                $direcao = strtoupper(trim((string)($ordem['dir'] ?? 'ASC')));

                if (!self::campoSqlSeguro($campo) || !in_array($direcao, ['ASC', 'DESC'], true)) {
                    continue;
                }

                array_push($_ordenacao, $campo .' '. $direcao);
            }
            $str_ordenacao = implode(', ', $_ordenacao) ." ";
        }
        return $str_ordenacao;
    }
    public function getPaginacao(){
        $str_paginacao = '';
        if($this->start != '' and $this->length != ''){
            $inicio = max(0, (int) $this->start);
            $qtde = min(1000, (int) $this->length);

            if ($qtde < 0) {
                return '';
            }

            $str_paginacao = "LIMIT {$inicio}, {$qtde} ";
        }
        return $str_paginacao;
    }
    public function getNameColumn($nr){
        if(!isset($this->_columns[$nr])){
            return null;
        }
        
        $col = $this->_columns[$nr];

        if(($col['data'] ?? '') === 'function'){
            if(empty($col['name'])){
                $nrHuman = intval($nr) + 1;
                $msg = "No javascript, a {$nrHuman}ª coluna da listagem está manipulando os dados com \"function(data)\" e para isso é necessário definir a propriedade \"name\".";
                die($msg);
            }
            return $col['name'];
        }
        
        if(!empty($col['name'])){
            return $col['name'];
        }

        return $col['data'] ?? null;
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
    public static function getResultVazio(){
        return array(
            'draw' => 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => array()
        );
    }
    public static function gerarFromPost(){
        $_params = filter_input_array(INPUT_POST);
        $dataTable = new DataTable();
        $dataTable->draw = $_params['draw'] ?? null;//filter_input(INPUT_POST, 'draw');
        $dataTable->objeto = $_params['objeto'] ?? null;//filter_input(INPUT_POST, 'draw');
        $dataTable->metodo = $_params['metodo'] ?? null;//filter_input(INPUT_POST, 'draw');
        $dataTable->draw = $_params['draw'] ?? null;//filter_input(INPUT_POST, 'draw');
        $dataTable->start = $_params['start'] ?? null;//filter_input(INPUT_POST, 'start');
        $dataTable->length = $_params['length'] ?? null;//filter_input(INPUT_POST, 'length');
        $dataTable->_order = $_params['order'] ?? [];//filter_input(INPUT_POST, 'order');
        $dataTable->_columns = $_params['columns'] ?? [null];//filter_input(INPUT_POST, 'columns');
        $dataTable->_search = $_params['search']['value'] ?? null;//filter_input(INPUT_POST, 'columns');
        $dataTable->recordsTotal = 0;
        $dataTable->recordsFiltered = 0;
        return $dataTable;
    }
}
