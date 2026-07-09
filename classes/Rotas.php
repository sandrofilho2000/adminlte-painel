<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use Exception;

class Rotas extends ClasseBase
{
    public $id;
    public $nome;
    public $url;
    public $ativo;
    public $id_pai;
    public $hierarquico;

    protected $_tabela = [
        'nome' => 'TBLRotas',
        'schema' => 'portal',
        'chave_primaria' => ['id'],
        'colunas' => [
            'id',
            'nome',
            'url',
            'ativo',
            'id_pai',
        ],
        'permissao' => '00008'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getRotas($hierarquico = false)
    {
        $hierarquico = $this->hierarquico ?? $hierarquico;
        if ($hierarquico) {
            $this->queryCorrente = "
                    WITH RECURSIVE rotas AS (
                        SELECT
                            id,
                            nome,
                            url,
                            ativo,
                            id_pai,
                            CAST(LPAD(id, 10, '0') AS CHAR(1000)) AS ordem,
                            0 AS nivel
                        FROM {$this->getNomeTabela()}
                        WHERE id_pai IS NULL

                        UNION ALL

                        SELECT
                            r.id,
                            r.nome,
                            r.url,
                            r.ativo,
                            r.id_pai,
                            CONCAT(p.ordem, '.', LPAD(r.id, 10, '0')),
                            p.nivel + 1
                        FROM {$this->getNomeTabela()} r
                        INNER JOIN rotas p
                            ON r.id_pai = p.id
                    )
                    SELECT
                        id,
                        nome,
                        url,
                        ativo,
                        id_pai,
                        nivel
                    FROM rotas r WHERE 1=1 ";
        } else {
            $this->queryCorrente = "
                SELECT
                    r.id,
                    r.nome,
                    r.url,
                    r.ativo,
                    r.id_pai
                FROM {$this->getNomeTabela()} r
                WHERE 1 = 1
            ";
        }

        return $this->buscar(true);
    }

    public function criaRota()
    {
        $this->nome = $this->normalizarTexto($this->nome);
        $this->url = $this->normalizarUrl($this->url);
        $this->ativo = (int) ($this->ativo ?? 0) === 1 ? 1 : 0;
        $this->id_pai = $this->normalizarIdPai($this->id_pai);

        if ($this->nome === null) {
            throw new Exception('Informe o nome da rota.');
        }

        if ($this->url === null) {
            throw new Exception('Informe a URL da rota.');
        }

        if (!empty($this->id)) {
            $rotaExistente = self::instanciarPorId((int) $this->id);

            if (empty($rotaExistente)) {
                throw new Exception('Rota não encontrada.');
            }

            $rotaExistente->nome = $this->nome;
            $rotaExistente->url = $this->url;
            $rotaExistente->ativo = $this->ativo;
            $rotaExistente->id_pai = $this->id_pai;

            $resultado = $rotaExistente->salvar();
            $resultado['tipo'] = 'success';
            $resultado['message'] = 'Rota atualizada com sucesso.';

            return $resultado;
        }

        $resultado = $this->incluir();
        $resultado['tipo'] = 'success';
        $resultado['message'] = 'Rota cadastrada com sucesso.';

        return $resultado;
    }

    private function normalizarTexto($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : null;
    }

    private function normalizarUrl($valor): ?string
    {
        $valor = trim((string) $valor);

        $valor = preg_replace('#^https?://[^/]+/?#i', '', $valor);
        $valor = trim($valor, '/');

        if ($valor === '') {
            return null;
        }

        return $valor . '/';
    }

    private function normalizarIdPai($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = (int) $valor;

        return $valor > 0 ? $valor : null;
    }
}
