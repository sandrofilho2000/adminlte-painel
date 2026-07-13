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
    public $rotas_ascendentes;
    public $rotas_descendentes;

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
                            CAST('' AS CHAR(1000)) AS rota_ascendentes,
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
                            CONCAT(p.rota_ascendentes, COALESCE(p.url, '')),
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
                        ordem,
                        rota_ascendentes,
                        nivel
                    FROM rotas r WHERE 1=1 ";

            $this->ordenar("ordem", "asc");
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

    public function getRotasAscendentes($id_rota = null)
    {
        $id_rota = (int) ($id_rota ?? $this->id ?? 0);

        if ($id_rota <= 0) {
            throw new Exception('Informe o ID da rota.');
        }

        $this->queryCorrente = "
        WITH RECURSIVE ascendentes AS (
            SELECT
                pai.id,
                pai.nome,
                pai.url,
                pai.ativo,
                pai.id_pai,
                0 AS nivel
            FROM {$this->getNomeTabela()} rota
            INNER JOIN {$this->getNomeTabela()} pai
                ON pai.id = rota.id_pai
            WHERE rota.id = {$id_rota}

            UNION ALL

            SELECT
                pai.id,
                pai.nome,
                pai.url,
                pai.ativo,
                pai.id_pai,
                a.nivel + 1 AS nivel
            FROM {$this->getNomeTabela()} pai
            INNER JOIN ascendentes a
                ON a.id_pai = pai.id
        )
        SELECT
            id,
            nome,
            url,
            ativo,
            id_pai,
            nivel
        FROM ascendentes
        WHERE 1 = 1
    ";

        $this->ordenar('nivel', 'desc');

        return $this->buscar(true);
    }

    public function getRotasDescendentes($id_rota = null)
    {
        $id_rota = (int) ($id_rota ?? $this->id ?? 0);

        if ($id_rota <= 0) {
            throw new Exception('Informe o ID da rota.');
        }

        $this->queryCorrente = "
        WITH RECURSIVE descendentes AS (
            SELECT
                filha.id,
                filha.nome,
                filha.url,
                filha.ativo,
                filha.id_pai,
                1 AS nivel,
                CAST(LPAD(filha.id, 10, '0') AS CHAR(1000)) AS ordem
            FROM {$this->getNomeTabela()} filha
            WHERE filha.id_pai = {$id_rota}

            UNION ALL

            SELECT
                filha.id,
                filha.nome,
                filha.url,
                filha.ativo,
                filha.id_pai,
                d.nivel + 1 AS nivel,
                CONCAT(d.ordem, '.', LPAD(filha.id, 10, '0')) AS ordem
            FROM {$this->getNomeTabela()} filha
            INNER JOIN descendentes d
                ON filha.id_pai = d.id
        )
        SELECT
            id,
            nome,
            url,
            ativo,
            id_pai,
            nivel,
            ordem
        FROM descendentes
        WHERE 1 = 1
    ";

        $this->ordenar('ordem', 'asc');

        return $this->buscar(true);
    }


    public function getRota($id_rota = null)
    {
        $id_rota = (int) ($id_rota ?? $this->id ?? 0);

        if ($id_rota <= 0) {
            throw new Exception('Informe o ID da rota.');
        }

        $rota = self::instanciarPorId($id_rota);

        if (empty($rota)) {
            throw new Exception('Rota não encontrada.');
        }

        $rota->rotas_ascendentes = (new self())->getRotasAscendentes($id_rota);
        $rota->rotas_descendentes = (new self())->getRotasDescendentes($id_rota);

        return $rota;
    }

    public function criaRota()
    {
        $this->nome = $this->normalizarTexto($this->nome);
        $this->url = $this->normalizarUrlRota($this->url);
        $this->ativo = (int) ($this->ativo ?? 0) === 1 ? 1 : 0;
        $this->id_pai = $this->normalizarIdPai($this->id_pai);

        if ($this->nome === null) {
            throw new Exception('Informe o nome da rota.');
        }

        if ($this->url === null) {
            $this->url = "/";
        }

        $idRota = !empty($this->id) ? (int) $this->id : null;

        if ($idRota !== null) {
            $this->validarRotaPai($idRota, $this->id_pai);
        }

        $this->validarNomeDuplicado($this->nome, $idRota);
        $this->validarRotaFinalDuplicada($this->url, $this->id_pai, $idRota);

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

    private function normalizarUrlRota($valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $valor = preg_split('/[?#]/', $valor, 2)[0];
        $valor = trim($valor);
        $valor = trim($valor, '/');

        if ($valor === '') {
            return null;
        }

        $this->validarUrlRota($valor);

        return $valor . '/';
    }

    private function validarUrlRota(string $valor): void
    {
        if (preg_match('/\s/', $valor)) {
            throw new Exception('A URL da rota não pode conter espaços.');
        }

        if (
            preg_match('#^[a-z][a-z0-9+\-.]*://#i', $valor)
            || strpos($valor, '//') === 0
            || preg_match('#^(www\.)#i', $valor)
            || preg_match('#^https?(/|$)#i', $valor)
        ) {
            throw new Exception('Informe apenas o caminho da rota, sem https, domínio ou link completo.');
        }

        if (preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)*\.(com|org|net|gov|edu|br|info)(\.[a-z]{2})?(/|$)#i', $valor)) {
            throw new Exception('Informe apenas o caminho interno da rota, sem domínio.');
        }

        if (strpos($valor, '\\') !== false || strpos($valor, '?') !== false || strpos($valor, '#') !== false) {
            throw new Exception('A URL da rota deve ser um caminho limpo, sem parâmetros, âncora ou barras invertidas.');
        }

        if (!preg_match('#^[A-Za-z0-9._~/-]+$#', $valor)) {
            throw new Exception('A URL da rota possui caracteres inválidos. Use apenas letras, números, hífen, underline, ponto e barra.');
        }

        $partes = array_filter(explode('/', trim($valor, '/')), 'strlen');

        foreach ($partes as $parte) {
            if ($parte === '.' || $parte === '..') {
                throw new Exception('A URL da rota não pode conter segmentos "." ou "..".');
            }
        }

        if (strpos($valor, '//') !== false) {
            throw new Exception('A URL da rota não pode conter barras duplicadas.');
        }
    }

    private function normalizarIdPai($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = (int) $valor;

        return $valor > 0 ? $valor : null;
    }

    private function normalizarNomeParaComparacao(string $nome): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($nome)), 'UTF-8');
    }

    private function obterRotaFinal(?int $idPai, string $url): string
    {
        if ($idPai === null) {
            return $url;
        }

        $rotaPai = $this->getRota($idPai);
        $rotaAscendente = '';

        if (!empty($rotaPai->rotas_ascendentes) && is_array($rotaPai->rotas_ascendentes)) {
            foreach ($rotaPai->rotas_ascendentes as $rotaAscendenteItem) {
                $rotaAscendente .= $this->normalizarUrlRota($rotaAscendenteItem['url'] ?? '');
            }
        }

        return $rotaAscendente . $this->normalizarUrlRota($rotaPai->url ?? '') . $url;
    }

    private function validarNomeDuplicado(string $nome, ?int $idRotaAtual = null): void
    {
        $sql = "
            SELECT id, nome
            FROM {$this->getNomeTabela()}
            WHERE 1 = 1
        ";

        $params = [];

        if ($idRotaAtual !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $idRotaAtual;
        }

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($params);

        $nomeNormalizado = $this->normalizarNomeParaComparacao($nome);

        while ($rota = $consulta->fetch()) {
            if ($this->normalizarNomeParaComparacao($rota['nome'] ?? '') === $nomeNormalizado) {
                throw new Exception('Já existe uma rota cadastrada com este nome.');
            }
        }
    }

    private function validarRotaFinalDuplicada(string $url, ?int $idPai, ?int $idRotaAtual = null): void
    {
        if ($url === '/') {
            return;
        }

        $rotaFinal = $this->obterRotaFinal($idPai, $url);
        $rotasAfetadas = [];
        $rotasAfetadas[$idRotaAtual ?? 0] = $rotaFinal;

        if ($idRotaAtual !== null) {
            $descendentes = $this->getRotasDescendentes($idRotaAtual);

            foreach ($descendentes as $descendente) {
                $idDescendente = (int) ($descendente['id'] ?? 0);
                $idPaiDescendente = (int) ($descendente['id_pai'] ?? 0);
                $rotaPaiDescendente = $idPaiDescendente === $idRotaAtual
                    ? $rotaFinal
                    : ($rotasAfetadas[$idPaiDescendente] ?? null);

                if ($idDescendente <= 0 || $rotaPaiDescendente === null) {
                    continue;
                }

                $rotasAfetadas[$idDescendente] = $rotaPaiDescendente . $this->normalizarUrlRota($descendente['url'] ?? '');
            }
        }

        $rotasFinais = array_values($rotasAfetadas);

        if (count($rotasFinais) !== count(array_unique($rotasFinais))) {
            throw new Exception('A alteração gera rotas finais duplicadas entre a rota e seus descendentes.');
        }

        $sql = "
            WITH RECURSIVE rotas AS (
                SELECT
                    id,
                    url,
                    id_pai,
                    CAST('' AS CHAR(1000)) AS rota_ascendentes
                FROM {$this->getNomeTabela()}
                WHERE id_pai IS NULL

                UNION ALL

                SELECT
                    r.id,
                    r.url,
                    r.id_pai,
                    CONCAT(p.rota_ascendentes, COALESCE(p.url, ''))
                FROM {$this->getNomeTabela()} r
                INNER JOIN rotas p
                    ON r.id_pai = p.id
            )
            SELECT
                id,
                CONCAT(rota_ascendentes, COALESCE(url, '')) AS rota_final
            FROM rotas
            WHERE 1 = 1
        ";

        $params = [];

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($params);

        $idsAfetados = array_map('intval', array_keys($rotasAfetadas));

        while ($rota = $consulta->fetch()) {
            $idRota = (int) ($rota['id'] ?? 0);

            if (in_array($idRota, $idsAfetados, true)) {
                continue;
            }

            if (in_array($rota['rota_final'] ?? '', $rotasFinais, true)) {
                throw new Exception('Já existe uma rota cadastrada com este caminho final.');
            }
        }
    }

    private function validarRotaPai(int $idRota, ?int $idPai): void
    {
        if ($idPai === null) {
            return;
        }

        if ($idPai === $idRota) {
            throw new Exception('A rota não pode ser filha dela mesma.');
        }

        $descendentes = $this->getRotasDescendentes($idRota);

        foreach ($descendentes as $descendente) {
            $idDescendente = (int) ($descendente['id'] ?? 0);

            if ($idDescendente === $idPai) {
                throw new Exception('A rota pai selecionada é descendente desta rota. Escolha outra rota pai.');
            }
        }
    }
}
