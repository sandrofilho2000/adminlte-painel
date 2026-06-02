<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;
use Exception;


class UsuariosCargos extends ClasseBase
{
    public $id_usuario;
    public $id_cargo;
    public $data_inicio;
    public $data_fim;

    protected $_tabela = array(
        'nome' => 'TBLUsuarios_Cargos',
        'chave_primaria' => array(''),
        'colunas' => array(
            "id_usuario",
            "id_cargo",
            "data_inicio",
            "data_fim",
        ),
        'permissao' => '00081'
    );

    public function __construct()
    {}

    public function getCargosPorUsuario($id_usuario)
    {
        $id_usuario = $this->id_usuario ?? $id_usuario;
        $this->queryCorrente = $this->getQuerybase();
        $this->gerar_log_query = True;
        $this->filtrar("id_usuario", $id_usuario);
        $cargos = $this->buscar();

        if(empty($cargos[0])){
            return '';
        }

        $cargos = $cargos[0];

        $cargo = new Cargos();
        $cargo = $cargo->instanciarPorId($cargos->id_cargo);
        return $cargo;
    }
}
