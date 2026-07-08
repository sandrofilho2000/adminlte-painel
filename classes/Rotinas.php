<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

class Rotinas extends ClasseBase
{
    public $id;
    public $Rotina;
    public $Descricao;
    public $icon;
    public $rota;
    public $link;
    public $url_tutorial;
    public $grupo;
    public $status;
    public $id_pai;
    public $em_manutencao;
    public $tipo_sistema;
    public $exibir_menu;

    public $itens_menu;

    protected $_tabela = array(
        'nome' => 'TBLRotinas',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "Rotina",
            "Descricao",
            "icon",
            "rota",
            "link",
            "url_tutorial",
            "grupo",
            "status",
            "id_pai",
            "em_manutencao",
            "tipo_sistema",
            "exibir_menu",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function criaRotina()
    {

        if (!empty($this->id)) {
            $rotina = $this->editRotina();
            return $rotina;
        }
        $this->Descricao = $this->normalizarTexto($this->Descricao);
        $this->status = (int) ($this->status ?? 0) === 1 ? 1 : 0;
        $this->em_manutencao = (int) ($this->em_manutencao ?? 0) === 1 ? 1 : 0;
        $this->exibir_menu = (int) ($this->exibir_menu ?? 0) === 1 ? 1 : 0;
        $this->id_pai = $this->id_pai !== null && $this->id_pai !== '' ? (int) $this->id_pai : null;

        if ($this->id_pai !== null && $this->id_pai <= 0) {
            $this->id_pai = null;
        }

        $ultima_rotina = RotinasConfig::obterUltimoCodigo(true);
        $this->Rotina = sprintf('%05d', $ultima_rotina);

        $resultado = $this->incluir();
        $resultado['tipo'] = 'success';
        $resultado['mensagem'] = 'Rotina cadastrada com sucesso.';
        return $resultado;
    }

    public function getRotinas()
    {
        $this->queryCorrente = "SELECT
            r.id,
            r.Rotina,
            r.Descricao,
            r.tipo_sistema,
            r.icon,
            r.rota,
            r.status,
            r.id_pai,
            r.em_manutencao,
            r.exibir_menu,
            pai.Rotina AS rotina_pai
        FROM {$this->getNomeTabela()} r
        LEFT JOIN {$this->getNomeTabela()} pai ON pai.id = r.id_pai
        WHERE 1=1 ";

        $result = $this->buscar(true);
        return $result;
    }

    public function editRotina() {
        $rotina_existente = $this->instanciarPorId($this->id);

        $rotina_existente->Descricao = $this->normalizarTexto($this->Descricao) ?? $rotina_existente->Descricao;
        $rotina_existente->icon = $this->normalizarTexto($this->icon) ?? $rotina_existente->icon;
        $rotina_existente->id_pai = $this->id_pai;
        $rotina_existente->rota = $this->normalizarTexto($this->rota) ?? $rotina_existente->rota;
        $rotina_existente->tipo_sistema = $this->normalizarTexto($this->tipo_sistema) ?? $rotina_existente->tipo_sistema;
        $rotina_existente->status = (int) ($this->status ?? 0) === 1 ? 1 : 0 ?? $rotina_existente->status;
        $rotina_existente->em_manutencao = (int) ($this->em_manutencao ?? 0) === 1 ? 1 : 0 ?? $rotina_existente->em_manutencao;
        $rotina_existente->exibir_menu = (int) ($this->exibir_menu ?? 0) === 1 ? 1 :0 ?? $rotina_existente->exibir_menu;

        $salvar = $rotina_existente->salvar();
        return $salvar;
    }

    private function normalizarTexto($valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    public function getItensMenu()
     {
        $this->queryCorrente = "
            SELECT
                r.id,
                r.Rotina,
                r.tipo_sistema,
                r.Descricao,
                r.tipo_sistema,
                r.icon,
                r.rota,
                r.link,
                r.url_tutorial,
                r.grupo,
                r.status,
                r.id_pai,
                r.em_manutencao,
                r.exibir_menu,
                COALESCE(NULLIF(TRIM(r.rota), ''), NULLIF(TRIM(r.link), '')) AS url
            FROM {$this->getNomeTabela()} r
            RIGHT JOIN portal.TBLPersistemas p ON r.Rotina = p.Rotina
            WHERE COALESCE(NULLIF(TRIM(r.rota), ''), NULLIF(TRIM(r.link), '')) IS NOT NULL
        ";

        $this->filtrar("p.Usuario", ID_USER);
        $this->filtrar("r.status", 1);
        $this->filtrar("r.exibir_menu", 1);
        $this->filtrar("r.tipo_sistema", 'portal');
        $this->ordenar("r.Descricao");
        
        $registros = $this->buscar(true);
        $arvore_registros =  self::montarArvoreRotinas($registros);
        return $arvore_registros;
    }

    private static function montarArvoreRotinas(array $registros): array
    {
        $registrosPorId = [];
        $filhosPorPai = [];

        foreach ($registros as $registro) {
            $id = (int) $registro['id'];
            $registro['id'] = $id;
            $registro['id_pai'] = !empty($registro['id_pai'])
                ? (int) $registro['id_pai']
                : null;
            $registro['filhas'] = [];
            $registrosPorId[$id] = $registro;
        }

        foreach ($registrosPorId as $registro) {
            $idPai = $registro['id_pai'];

            if ($idPai !== null && isset($registrosPorId[$idPai]) && $idPai !== $registro['id']) {
                $filhosPorPai[$idPai][] = $registro['id'];
            }
        }

        $idsAdicionados = [];
        $montarNo = function (int $id, array $ancestrais = []) use (
            &$montarNo,
            &$idsAdicionados,
            $registrosPorId,
            $filhosPorPai
        ): ?array {
            if (isset($idsAdicionados[$id]) || isset($ancestrais[$id])) {
                return null;
            }

            $ancestrais[$id] = true;
            $item = $registrosPorId[$id];

            foreach ($filhosPorPai[$id] ?? [] as $idFilha) {
                $filha = $montarNo($idFilha, $ancestrais);
                if ($filha !== null) {
                    $item['filhas'][] = $filha;
                }
            }

            $idsAdicionados[$id] = true;
            return $item;
        };

        $arvore = [];

        foreach ($registrosPorId as $id => $registro) {
            $idPai = $registro['id_pai'];
            if ($idPai === null || !isset($registrosPorId[$idPai]) || $idPai === $id) {
                $item = $montarNo($id);
                if ($item !== null) {
                    $arvore[] = $item;
                }
            }
        }

        foreach (array_keys($registrosPorId) as $id) {
            $item = $montarNo($id);
            if ($item !== null) {
                $arvore[] = $item;
            }
        }

        return $arvore;
    }
}
