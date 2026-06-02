<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class ChamadosAtualizacoes extends ClasseBase
{
    public $id;
    public $id_chamado;
    public $descricao;
    public $criado_por_id;
    public $criado_em;

    public $apresentacao;

    protected $_tabela = array(
        'nome' => 'TBLChamados_Atualizacoes',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_chamado",
            "descricao",
            "criado_por_id",
            "criado_em",
        ),
        'permissao' => '00072'
    );

    public function __construct() {}

    public function criaAtualizacao()
    {

        $Chamados = new Chamados();
        $chamado = $Chamados->instanciarPorId($this->id_chamado);
        $atributos_editaveis = $Chamados->getAtributosEditaveis($chamado->id);

        if ($atributos_editaveis['criar_atualizacoes_por_texto'] === false) {
            throw new \Exception('Você não tem permissão para criar atualizações por texto neste chamado.');
        }

        $this->criado_por_id = ID_USER;
        $this->criado_em = (new DateTime())->format('Y-m-d H:i:s');
        $this->descricao = trim($this->descricao);
        $incluir = $this->incluir();

        if (!empty($incluir['id'])) {
            $chamado = $Chamados->getChamado($this->id_chamado);
            $chamado['atualizacao'] = $this->descricao;
            $Chamados->criaNotificacoes('criar-atualizacao', $chamado);
        }
        
        return $incluir;
    }

    public function getAtualizacoes($id_chamado = null)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $this->queryCorrente = "SELECT a.*, u.apresentacao FROM confef1.TBLChamados_Atualizacoes a LEFT JOIN confef1.TBLUsuarios u ON a.criado_por_id = u.id WHERE 1=1 ";
        $this->filtrar("a.id_chamado", $id_chamado);
        $this->ordenar("a.criado_em", "DESC");
        $atualizacoes = $this->buscar();
        return $atualizacoes;
    }

    public function getUltimaAtualizacao($id_chamado = null)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_chamado", $id_chamado);
        $this->ordenar("criado_em", "DESC");
        $this->limitar(1);
        $atualizacao = $this->buscar();
        return $atualizacao ? $atualizacao[0] : null;
    }
}
