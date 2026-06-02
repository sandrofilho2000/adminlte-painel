<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;
use Exception;


class Setores extends ClasseBase
{
    public $id;
    public $nome_setor;
    public $nome;
    public $id_empresa;
    public $tipo;
    public $estado_conselho;

    protected $_tabela = array(
        'nome' => 'TBLSetores',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "nome_setor",
            "id_empresa"
        ),
        'permissao' => '00081'
    );

    public function __construct()
    {
    }
    

    public function getSetor($id_empresa){
        $id_empresa = $this->id_empresa ?? $id_empresa;
        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_empresa", $id_empresa);
        return $this->buscar();
    }

    public function getSetorPorUsuario($id_user){
        $this->queryCorrente = "SELECT s.nome_setor
            FROM TBLSetores s
            LEFT JOIN TBLCargos c 
                ON s.id = c.id_setor
            LEFT JOIN TBLUsuarios_Cargos uc 
                ON c.id = uc.id_cargo
            LEFT JOIN TBLUsuarios u 
                ON uc.id_usuario = u.id
            WHERE 1=1 "; 

        $this->filtrar("u.id", $id_user);
        $result = $this->buscar()[0];
        $nome_setor = !empty($result) ? $result->nome_setor : false;
        return $nome_setor;
    }

}
