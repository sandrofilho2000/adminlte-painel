<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class LogsIgnorar extends ClasseBase
{
    public $id;
    public $id_usuario;
    public $campo;
    public $operador;
    public $valor;
    public $ativo;
    public $criado_em;
    public $atualizado_em;


    protected $_tabela = array(
        'nome' => 'logs_erros_ignorar',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_usuario",
            "campo",
            "operador",
            "valor",
            "ativo",
            "criado_em",
            "atualizado_em",
        ),
        'permissao' => '00108'
    );

    public function __construct() {}

    public function criaRegrar()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }

        $this->ativo = 1;
        $this->criado_em = (new DateTime())->format('Y-m-d H:i:s');
        $this->atualizado_em = (new DateTime())->format('Y-m-d H:i:s');
        $this->id_usuario = ID_USER;
        $incluir = $this->incluir();
        return $incluir;
    }

    public function editarRegrar()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }

        $existente = $this->instanciarPorId($this->id);
        if (empty($existente)) {
            return false;
        }

        $existente->campo = $this->campo ?? $existente->campo;
        $existente->operador = $this->operador ?? $existente->operador;
        $existente->valor = $this->valor ?? $existente->valor;
        $existente->ativo = $this->ativo ?? $existente->ativo;
        $salvar = $existente->salvar();
        return $salvar;
    }

    public function getRegras()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }
        $this->queryCorrente = "SELECT 
            l.id,
            l.id_usuario,
            u.apresentacao AS nome_usuario,
            u.estado_conselho,
            l.campo,
            l.operador,
            l.valor,
            l.ativo,
            l.criado_em,
            l.atualizado_em
        FROM confef1.logs_erros_ignorar l
        LEFT JOIN confef1.TBLUsuarios u 
            ON l.id_usuario = u.id
        WHERE 1=1
    ";

        $this->retornarComoArray = true;
        $result = $this->buscar();
        return $result;
    }

    public function getRegrasAtivas()
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }
        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar('ativo', 1);
        $this->gerar_log_query = true;
        $result = $this->buscar();
        return $result;
    }

    public function getRegra($id)
    {
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }
        $id = $this->id ?? $id;
        $regra = $this->instanciarPorId($id);
        return $regra;
    }



    public function excluirRegraLog($id)
    {
        $id = $this->id ?? $id;
        if (ESTADO_CONSELHO != "BR") {
            return false;
        }
        $log = $this->instanciarPorId($id);
        if (!empty($log)) {
            return $log->excluir();
        }
        return false;
    }
}
