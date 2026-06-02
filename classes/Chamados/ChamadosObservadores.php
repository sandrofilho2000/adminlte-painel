<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class ChamadosObservadores extends ClasseBase
{
    public $id_chamado;
    public $id;
    public $id_usuario;
    public $criado_em;
    public $apresentacao;

    protected $_tabela = array(
        'nome' => 'TBLChamados_Observadores',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id_chamado",
            "id_usuario",
            "criado_em",
        ),
        'permissao' => '00072'
    );

    public function __construct()
    {}

    public function getObsevadores($id_chamado){
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $this->queryCorrente = "SELECT o.*, u.apresentacao FROM confef1.TBLChamados_Observadores o LEFT JOIN confef1.TBLUsuarios u ON o.id_usuario = u.id WHERE 1=1 ";
        $this->filtrar("id_chamado", $id_chamado);
        $observadores = $this->buscar();
        return $observadores;
    }

    public function getObsevador($id_chamado, $id_usuario){
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $id_usuario = $id_usuario ?? $this->id_usuario;
        $this->queryCorrente = "SELECT id FROM confef1.TBLChamados_Observadores o WHERE 1=1 ";
        $this->filtrar("id_chamado", $id_chamado);
        $this->filtrar("id_usuario", $id_usuario);  
        $observador = $this->buscar()[0] ?? null;
        return $observador;
    }
}
