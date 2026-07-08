<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;
use Exception;

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

    public function salvarPortalCref()
    {
        $this->estado_conselho = strtoupper(trim((string) ($this->estado_conselho ?? '')));
        $this->ativo = (int) ($this->ativo ?? 0) === 1 ? 1 : 0;
        $this->dt_inclusao = trim((string) ($this->dt_inclusao ?? ''));

        if ($this->estado_conselho === '') {
            throw new Exception("Informe o estado conselho.");
        }

        if ($this->dt_inclusao === '') {
            $this->dt_inclusao = null;
        } else {
            $this->dt_inclusao = str_replace('T', ' ', $this->dt_inclusao);
        }

        if (!empty($this->id)) {
            $portal = self::instanciarPorId((int) $this->id);

            if (empty($portal)) {
                throw new Exception("Portal nao encontrado.");
            }

            $portal->estado_conselho = $this->estado_conselho;
            $portal->dt_inclusao = $this->dt_inclusao;
            $portal->ativo = $this->ativo;

            return $portal->salvar();
        }

        $resultado = $this->incluir();
        $resultado['tipo'] = 'success';
        $resultado['message'] = 'Portal salvo com sucesso.';
        return $resultado;
    }

    public function getPortaisCrefs()
    {
        $this->queryCorrente = "SELECT * FROM portal.TBLPortaisCREFS WHERE 1=1 ";
        return $this->buscar();
    }
}
