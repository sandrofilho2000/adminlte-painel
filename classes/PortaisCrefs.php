<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class PortaisCrefs extends ClasseBase
{
    public $id;
    public $estado_conselho;
    public $dt_inclusao;
    public $ativo;

    protected $_tabela = array(
        'nome' => 'TBLPortaisCREFS',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "estado_conselho",
            "dt_inclusao",
            "ativo",
        ),
        'permissao' => '00014'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function getPortaisCrefs(){
        $this->queryCorrente = "SELECT * FROM portal.TBLPortaisCREFS WHERE 1=1 ";
        return $this->buscar();
    }
}
