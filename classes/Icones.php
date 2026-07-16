<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class Icones extends ClasseBase
{
    public $id;
    public $nome;
    public $classes;

    protected $_tabela = array(
        'nome' => 'TBLIcons',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "nome",
            "classes",
        ),
        'permissao' => "00000"
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function getIcones(){
        return $this->buscar();
    }
}
