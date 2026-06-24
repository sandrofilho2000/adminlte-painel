<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class Rotinas extends ClasseBase
{
    public $id;
    public $nome;
    public $descricao;
    public $url;
    public $icone;
    public $rotina_pai_id;
    public $ordem;
    public $ativo;

    protected $_tabela = array(
        'nome' => 'rotinas',
        'schema' => 'aurora_tech',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "nome",
            "descricao",
            "url",
            "icone",
            "rotina_pai_id",
            "ordem",
            "ativo",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function criaRotina(){
        $incluir = $this->incluir();

        return $incluir;
    }
}
