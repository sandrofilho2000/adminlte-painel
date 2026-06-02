<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;
use Exception;

class Cargos extends ClasseBase
{
    public $id;
    public $id_empresa;
    public $nome_cargo;
    public $descricao;
    public $id_setor;

    protected $_tabela = array(
        'nome' => 'TBLCargos',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_empresa",
            "nome_cargo",
            "descricao",
            "id_setor",
        ),
        'permissao' => '00081'
    );

    public function __construct()
    {}
}
