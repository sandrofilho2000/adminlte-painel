<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class PortaisRotas extends ClasseBase
{
    public $id;
    public $id_portal;
    public $id_rota;
    public $rotas = [];
    public $ativo;

    protected $_tabela = array(
        'nome' => 'TBLPortaisRotas',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_portal",
            "id_rota",
            "ativo",
        ),
        'permissao' => '00008'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function getPortalRotaPorIdPortal($id_portal = null)
    {
        $id_portal = (int) $id_portal ?? (int) $this->id_portal;

        if (empty($id_portal)) {
            throw new \Exception('Erro! Informe o ID do portal e o ID da rota.');
        }

        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_portal", $id_portal);
        $result = $this->buscar() ?? [];
        return $result;
    }

    public function getPortalRota($id_portal = null, $id_rota = null)
    {
        $id_portal = (int) $id_portal ?? (int) $this->id_portal;
        $id_rota = (int) $id_rota ?? (int) $this->id_rota;

        if (empty($id_rota) || empty($id_portal)) {
            throw new \Exception('Erro! Informe o ID do portal e o ID da rota.');
        }

        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_portal", $id_portal);
        $this->filtrar("id_rota", $id_rota);
        $this->limitar(1);
        $portal_rota = $this->buscar() ?? [];
        $portal_rota = $portal_rota[0] ?? null;
        return $portal_rota;
    }

    public function upsertPortalRotaEmMassa()
    {
        $rotas = $this->rotas;
        $totalAtualizados = 0;

        foreach ($rotas as $rota) {
            $portal_rota = self::getPortalRota($this->id_portal, $rota['id']);
            $ativo = $rota['selecionada'] == "true";
            

            if (empty($portal_rota)) {
                if(!$ativo){
                    continue;
                }else{
                    $PortaisRotas = new PortaisRotas();
                    $PortaisRotas->id_portal =  $this->id_portal;
                    $PortaisRotas->id_rota =  $rota['id'];
                    $PortaisRotas->ativo = $ativo;
                    $incluir = $PortaisRotas->incluir();
                    $totalAtualizados++;
                }
            }else{
                $portal_rota->ativo = $ativo;
                $salvar = $portal_rota->salvar();
                $totalAtualizados += $salvar['row_count'];
            }
        }

        $mensagemItens = $totalAtualizados === 1 ? 'item atualizado' : 'itens atualizados';

        return [
            'tipo' => 'success',
            'message' => "{$totalAtualizados} {$mensagemItens} com sucesso.",
            'total_atualizados' => $totalAtualizados,
        ];
    }
}
