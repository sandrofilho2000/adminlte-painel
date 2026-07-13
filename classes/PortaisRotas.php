<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class PortaisRotas extends ClasseBase
{
    public $id;
    public $id_portal;
    public $rotina;
    public $rotas = [];
    public $ativo;

    protected $_tabela = array(
        'nome' => 'TBLPortaisRotas',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_portal",
            "rotina",
            "ativo",
        ),
        'permissao' => '00008'
    );

    public function __construct()
    {
        parent::__construct();
    }

    private static function normalizarRotina($rotina): string
    {
        $rotina = trim((string) $rotina);

        if ($rotina === '' || !ctype_digit($rotina)) {
            throw new \Exception('Informe um código de rotina numérico válido.');
        }

        $numeroRotina = (int) $rotina;

        if ($numeroRotina <= 0 || $numeroRotina > 99999) {
            throw new \Exception('O código da rotina deve possuir até cinco dígitos.');
        }

        return str_pad((string) $numeroRotina, 5, '0', STR_PAD_LEFT);
    }

    public function getPortalRotaPorIdPortal($id_portal = null)
    {
        $id_portal = (int) ($id_portal ?? $this->id_portal);

        if (empty($id_portal)) {
            throw new \Exception('Erro! Informe o ID do portal.');
        }

        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_portal", $id_portal);
        $result = $this->buscar() ?? [];
        return $result;
    }

    public function getPortalRota($id_portal = null, $rotina = null)
    {
        $id_portal = (int) ($id_portal ?? $this->id_portal);
        $rotina = self::normalizarRotina($rotina ?? $this->rotina);

        if (empty($id_portal)) {
            throw new \Exception('Erro! Informe o ID do portal e o código da rotina.');
        }

        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_portal", $id_portal);
        $this->filtrar("rotina", $rotina);
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
            $rotina = self::normalizarRotina($rota['rotina'] ?? '');

            $portal_rota = self::getPortalRota($this->id_portal, $rotina);
            $ativo = $rota['selecionada'] == "true";
            

            if (empty($portal_rota)) {
                if(!$ativo){
                    continue;
                }else{
                    $PortaisRotas = new PortaisRotas();
                    $PortaisRotas->id_portal =  $this->id_portal;
                    $PortaisRotas->rotina = $rotina;
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
