<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class Banner extends ClasseBase
{
    public $id;
    public $caminho_imagem;
    public $link;
    public $active;
    public $dt_inclusao;
    public $descricao;
    public $estado_conselho;


    protected $_tabela = array(
        'nome' => 'TBLBanner',
        'schema' => 'aurora_tech',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "caminho_imagem",
            "link",
            "active",
            "dt_inclusao",
            "estado_conselho",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function getBannersAtivos()
    {
        $query = "SELECT caminho_imagem, link, active FROM {$this->_tabela['nome']} WHERE 1=1 ";
        $this->ordenar('active', 'DESC');
        $result = $this->buscar();
        return $result;
    }
}
