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
    public $code;
    public $tipo;

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
            "code",
            "tipo",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function criaRotina()
    {
        $last_code = RotinasConfig::getLastCode();
        $last_code = sprintf('%05d', $last_code);
        $this->code = $last_code;
        $incluir = $this->incluir();
        RotinasConfig::incrementLastRotina();
        return $incluir;
    }

    public function getRotinas()
    {
        $this->queryCorrente = "SELECT
            r.id,
            r.nome,
            r.code,
            r.descricao,
            r.url,
            r.icone,
            r.rotina_pai_id,
            r.ordem,
            r.tipo,
            r.ativo
        FROM {$this->getNomeTabela()} r
        WHERE 1=1";

        return $this->buscar(true);
    }
}
