<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use DateTime;

class ChamadosChecklist extends ClasseBase
{
    public $id;
    public $id_chamado;
    public $texto;
    public $concluido;
    public $ordem;
    public $criado_por_id;
    public $criado_em;
    public $atualizado_em;

    protected $_tabela = array(
        'nome' => 'TBLChamados_Checklist',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            'id',
            'id_chamado',
            'texto',
            'concluido',
            'ordem',
            'criado_por_id',
            'criado_em',
            'atualizado_em',
        ),
        'permissao' => false,
    );

    public function __construct()
    {
    }

    private function normalizarTextoChecklist($texto)
    {
        return trim((string) $texto);
    }

    private function normalizarBooleano($valor)
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        $valor = strtolower(trim((string) $valor));
        if ($valor === '') {
            return 0;
        }

        return in_array($valor, array('1', 'true', 'on', 'sim', 'yes'), true) ? 1 : 0;
    }

    private function normalizarOrdem($ordem, $fallback = 0)
    {
        $ordem = (int) $ordem;
        if ($ordem > 0) {
            return $ordem;
        }

        return (int) $fallback;
    }

    private function validarPermissaoDoChamado($id_chamado)
    {
        $id_chamado = trim((string) $id_chamado);
        if ($id_chamado === '') {
            throw new \Exception('Informe o chamado para executar esta ação.');
        }

        $Chamados = new Chamados();
        $atributos_editaveis = $Chamados->getAtributosEditaveis($id_chamado);

        if (empty($atributos_editaveis) || empty($atributos_editaveis['gerenciar_checklist'])) {
            throw new \Exception('Voce nao possui permissao para editar este checklist.');
        }

        return $atributos_editaveis;
    }

    private function obterProximaOrdemPorChamado($id_chamado)
    {
        $id_chamado = trim((string) $id_chamado);
        if ($id_chamado === '') {
            return 1;
        }

        $this->queryCorrente = "SELECT COALESCE(MAX(c.ordem), 0) AS maior_ordem FROM confef1.TBLChamados_Checklist c WHERE 1=1 ";
        $this->filtrar('c.id_chamado', $id_chamado);
        $this->retornarComoArray = true;
        $resultado = $this->buscar();

        return ((int) ($resultado[0]['maior_ordem'] ?? 0)) + 1;
    }

    public function getChecklistPorChamado($id_chamado = null)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $id_chamado = trim((string) $id_chamado);

        if ($id_chamado === '') {
            return array();
        }

        $this->queryCorrente = "SELECT c.*, u.apresentacao
            FROM confef1.TBLChamados_Checklist c
            LEFT JOIN confef1.TBLUsuarios u ON c.criado_por_id = u.id
            WHERE 1=1 ";
        $this->filtrar('c.id_chamado', $id_chamado);
        $this->ordenar('c.ordem', 'ASC');
        $this->ordenar('c.criado_em', 'ASC');
        $this->ordenar('c.id', 'ASC');
        $this->retornarComoArray = true;

        $resultado = $this->buscar();
        return is_array($resultado) ? $resultado : array();
    }

    public function criaChecklistItem()
    {
        $id_chamado = trim((string) ($this->id_chamado ?? ''));
        $texto = $this->normalizarTextoChecklist($this->texto ?? '');

        if ($id_chamado === '') {
            throw new \Exception('Informe o chamado para criar o item do checklist.');
        }

        if ($texto === '') {
            throw new \Exception('Informe o texto do item do checklist.');
        }

        $this->validarPermissaoDoChamado($id_chamado);

        $agora = (new DateTime())->format('Y-m-d H:i:s');
        $this->id_chamado = $id_chamado;
        $this->texto = $texto;
        $this->concluido = $this->normalizarBooleano($this->concluido ?? 0);
        $ordemEnviada = $this->normalizarOrdem($this->ordem ?? 0, 0);
        $this->ordem = $ordemEnviada > 0 ? $ordemEnviada : $this->obterProximaOrdemPorChamado($id_chamado);
        $this->criado_por_id = ID_USER;
        $this->criado_em = $agora;
        $this->atualizado_em = $agora;

        $incluir = $this->incluir();

        return array(
            'status' => 'success',
            'id' => $incluir['id'] ?? null,
            'item' => $incluir,
        );
    }

    public function deletaChecklistItem()
    {
        $item = self::instanciarPorId($this->id);

        if (empty($item)) {
            throw new \Exception('Item do checklist nao encontrado.');
        }

        $this->validarPermissaoDoChamado($item->id_chamado);

        $exclusao = Dao::excluir($item);
        if ($exclusao <= 0) {
            throw new \Exception('Nao foi possivel excluir o item do checklist.');
        }

        return array(
            'status' => 'success',
            'id' => (string) $item->id,
            'id_chamado' => (string) $item->id_chamado,
        );
    }

    public function atualizaChecklistItem()
    {
        $item = self::instanciarPorId($this->id);

        if (empty($item)) {
            throw new \Exception('Item do checklist nao encontrado.');
        }

        $this->validarPermissaoDoChamado($item->id_chamado);

        $temTexto = array_key_exists('texto', $_POST);
        $temConcluido = array_key_exists('concluido', $_POST);
        $temOrdem = array_key_exists('ordem', $_POST);

        if (!$temTexto && !$temConcluido && !$temOrdem) {
            throw new \Exception('Nenhum dado informado para atualizar o item do checklist.');
        }

        if ($temTexto) {
            $texto = $this->normalizarTextoChecklist($this->texto ?? '');
            if ($texto === '') {
                throw new \Exception('Informe o texto do item do checklist.');
            }
            $item->texto = $texto;
        }

        if ($temConcluido) {
            $item->concluido = $this->normalizarBooleano($this->concluido ?? 0);
        }

        if ($temOrdem) {
            $ordem = $this->normalizarOrdem($this->ordem ?? 0, 0);
            if ($ordem > 0) {
                $item->ordem = $ordem;
            }
        }

        $item->atualizado_em = (new DateTime())->format('Y-m-d H:i:s');
        $item->salvar();

        return array(
            'status' => 'success',
            'id' => (string) $item->id,
            'item' => $item->converterArray(),
        );
    }
}
