<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;
use Exception;

class Persistemas extends ClasseBase
{
    public $Sistema;
    public $rotina;
    public $Usuario;
    public $Usuarios;
    public $Consulta;
    public $Incluir;
    public $Excluir;
    public $Alterar;
    public $id;
    public $criado_em;
    public $permissoes;

    protected $_tabela = array(
        'nome' => 'TBLPersistemas',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "Sistema",
            "rotina",
            "Usuario",
            "Consulta",
            "Incluir",
            "Excluir",
            "Alterar",
            "id",
            "criado_em",
        ),
        'permissao' => '00013'
    );

    public function __construct()
    {
        parent::__construct();
    }


    public function editPersistemas($id_permissao)
    {
        $id_permissao = (int) $id_permissao;

        if ($id_permissao <= 0) {
            throw new Exception("Informe uma permissão válida para editar.");
        }

        $permissao = self::instanciarPorId($id_permissao);

        if (ESTADO_CONSELHO != "BR") {
            $Usuarios = new Usuarios();
            $Usuarios->habilitarIgnorarPermissao();
            $usuario = $Usuarios::instanciarPorId($permissao->Usuario);
            $Usuarios->desabilitarIgnorarPermissao();
            if ($usuario->estado_conselho != ESTADO_CONSELHO) {
                return 0;
            }
            if ($usuario->id == ID_USER) {
                return 0;
            }
        }

        if (empty($permissao) || (int) ($permissao->id ?? 0) !== $id_permissao) {
            throw new Exception("Permissão não encontrada.");
        }

        $permissao->Consulta = (int) ($this->Consulta ?? 0) === 1 ? 1 : 0;
        $permissao->Incluir = (int) ($this->Incluir ?? 0) === 1 ? 1 : 0;
        $permissao->Excluir = (int) ($this->Excluir ?? 0) === 1 ? 1 : 0;
        $permissao->Alterar = (int) ($this->Alterar ?? 0) === 1 ? 1 : 0;

        $salvar = $permissao->salvar();
        self::carregarPermissoes(true);
        return $salvar['row_count'];
    }

    public function editPersistemaseMassa()
    {
        if (!verificaPermissao("00013", "Alterar")) {
            throw new Exception("Você não possui permissão para realizar esta operação.");
        }

        $permissoes = $this->permissoes ?? [];

        if (!is_array($permissoes) || empty($permissoes)) {
            throw new Exception("Informe ao menos uma permissão para atualizar.");
        }

        $permissoesAtualizadas = 0;

        foreach ($permissoes as $permissao) {
            if (!is_array($permissao) || empty($permissao['id'])) {
                throw new Exception("Uma das permissões informadas é inválida.");
            }

            $permissaoAtual = self::instanciarPorId((int) $permissao['id']);

            if (empty($permissaoAtual)) {
                throw new Exception("Uma das permissões informadas não foi encontrada.");
            }

            foreach (['Consulta', 'Incluir', 'Excluir', 'Alterar'] as $acao) {
                $valor = array_key_exists($acao, $permissao)
                    ? $permissao[$acao]
                    : ($permissaoAtual->$acao ?? 0);
                $this->$acao = (int) $valor === 1 ? 1 : 0;
            }

            $permissoesAtualizadas += (int) $this->editPersistemas($permissao['id']);
        }

        if ($permissoesAtualizadas === 0) {
            throw new Exception("Nenhuma permissão foi atualizada.");
        }

        self::carregarPermissoes(true);

        $mensagem = $permissoesAtualizadas === 1
            ? '1 permissão atualizada com sucesso.'
            : "{$permissoesAtualizadas} permissões atualizadas com sucesso.";

        return [
            'tipo' => 'success',
            'message' => $mensagem,
            'total_atualizadas' => $permissoesAtualizadas,
        ];
    }


    public function criaPersistemas()
    {
        $this->rotina = trim((string) ($this->rotina ?? ''));
        $this->Consulta = (int) ($this->Consulta ?? 0) === 1 ? 1 : 0;
        $this->Incluir = (int) ($this->Incluir ?? 0) === 1 ? 1 : 0;
        $this->Excluir = (int) ($this->Excluir ?? 0) === 1 ? 1 : 0;
        $this->Alterar = (int) ($this->Alterar ?? 0) === 1 ? 1 : 0;

        if ($this->rotina === '') {
            return [
                'tipo' => 'error',
                'message' => 'Informe a rotina da permissão.',
            ];
        }

        if (
            $this->Consulta !== 1
            && $this->Incluir !== 1
            && $this->Excluir !== 1
            && $this->Alterar !== 1
        ) {
            return [
                'tipo' => 'error',
                'message' => 'Informe ao menos uma ação de permissão.',
            ];
        }

        $usuariosInformados = $this->Usuarios ?? $this->Usuario ?? '';
        $usuarios = is_array($usuariosInformados)
            ? $usuariosInformados
            : explode(',', (string) $usuariosInformados);
        $usuarios = array_values(array_unique(array_filter(
            array_map(static fn($idUsuario) => trim((string) $idUsuario), $usuarios),
            static fn($idUsuario) => $idUsuario !== ''
        )));

        if (empty($usuarios)) {
            return [
                'tipo' => 'error',
                'message' => 'Informe ao menos um usuário.',
            ];
        }

        $totalIncluidas = 0;
        $totalAtualizadas = 0;
        $erros = [];

        foreach ($usuarios as $idUsuario) {
            try {
                $usuario = Usuarios::instanciarPorId($idUsuario);

                if (empty($usuario)) {
                    $erros[] = "Usuário não encontrado.";
                    continue;
                }

                if (ESTADO_CONSELHO !== 'BR' && $usuario->estado_conselho !== ESTADO_CONSELHO) {
                    $erros[] = "O usuário não pertence ao seu conselho.";
                    continue;
                }

                if (ESTADO_CONSELHO !== 'BR') {
                    if ($usuario->id == ID_USER) {
                        $erros[] = "Não é possível dar permissão a sí próprio.";
                        continue;
                    }
                }

                $idPermissaoExistente = self::getPermissoesUsuario($idUsuario, $this->rotina);

                if (!empty($idPermissaoExistente)) {
                    $this->editPersistemas($idPermissaoExistente);
                    $totalAtualizadas++;
                    continue;
                }

                $this->id = null;
                $this->Usuario = $idUsuario;
                $this->incluir();
                $totalIncluidas++;
            } catch (\Throwable $e) {
                $mensagem = $e->getMessage();
                $codigo = (string) $e->getCode();

                if ($codigo === '23000' || str_contains($mensagem, '1062 Duplicate entry')) {
                    $erros[] = 'Uma das permissões informadas já existe.';
                    continue;
                }

                error_log('[Persistemas::criaPersistemas] ' . $mensagem);
                $erros[] = 'Não foi possível processar uma das permissões informadas.';
            }
        }

        $totalProcessadas = $totalIncluidas + $totalAtualizadas;

        if ($totalProcessadas === 0) {
            return [
                'tipo' => 'error',
                'message' => $erros
                    ? implode(' ', $erros)
                    : 'Nenhuma permissão foi cadastrada ou atualizada.',
            ];
        }

        self::carregarPermissoes(true);

        $partesMensagem = [];

        if ($totalIncluidas > 0) {
            $partesMensagem[] = $totalIncluidas === 1
                ? '1 permissão cadastrada'
                : "{$totalIncluidas} permissões cadastradas";
        }

        if ($totalAtualizadas > 0) {
            $partesMensagem[] = $totalAtualizadas === 1
                ? '1 permissão atualizada'
                : "{$totalAtualizadas} permissões atualizadas";
        }

        $mensagem = ucfirst(implode(' e ', $partesMensagem)) . ' com sucesso.';

        if ($erros) {
            $mensagem .= ' Não processados: ' . implode(' ', array_unique($erros));
        }

        return [
            'tipo' => 'success',
            'message' => $mensagem,
        ];
    }

    public static function carregarPermissoes(bool $forcarRecarregamento = false)
    {
        require_once dirname(__DIR__) . '/includes/session.php';

        if (!$forcarRecarregamento && isset($_SESSION['Permissoes']) && is_array($_SESSION['Permissoes'])) {
            return $_SESSION['Permissoes'];
        }

        $idUsuario = (int) ($_SESSION['id'] ?? $_SESSION['user_id'] ?? 0);

        if ($idUsuario <= 0) {
            $_SESSION['Permissoes'] = [];
            return false;
        }

        self::habilitarIgnorarPermissao();

        try {
            $Persistemas = new Persistemas();
            $Persistemas->queryCorrente = $Persistemas->getQuerybase('rotina, Consulta, Incluir, Excluir, Alterar');
            $Persistemas->filtrar("Usuario", $idUsuario);
            $permissoes = $Persistemas->buscar(true) ?: [];
            $_SESSION['Permissoes'] = $permissoes;

            return $permissoes ?: false;
        } finally {
            self::desabilitarIgnorarPermissao();
        }
    }

    private static function getPermissoesUsuario($id_usuario, $rotina)
    {
        $id_usuario = (int) $id_usuario;
        $rotina = trim((string) $rotina);

        if ($id_usuario <= 0 || $rotina === '') {
            return null;
        }

        $Persistemas = new Persistemas();
        $Persistemas->queryCorrente = $Persistemas->getQuerybase('id');
        $Persistemas->filtrar("Usuario", $id_usuario);
        $Persistemas->filtrar("rotina", $rotina);

        $permissao = $Persistemas->buscar(true) ?: [];
        $permissao = $permissao[0] ?? null;
        return $permissao['id'] ?? null;
    }

    public function getPermissoes()
    {
        $tabela = $this->getNomeTabela();
        $this->queryCorrente = "SELECT
        p.id,
        r.descricao,
        r.rotina,
        u.apresentacao,
        u.estado_conselho,
        p.Consulta,
        p.Incluir,
        p.Excluir,
        p.Alterar
        FROM $tabela p LEFT JOIN portal.TBLRotinas r ON p.rotina = r.rotina LEFT JOIN  confef1.TBLUsuarios u ON p.Usuario = u.id WHERE 1=1 ";
        if(ESTADO_CONSELHO !== "BR"){
            $this->filtrar("u.estado_conselho", ESTADO_CONSELHO);
        }
        $result = $this->buscar(true);
        return $result;
    }


    public function deletePermissao($id)
    {
        self::carregarPermissoes();
        $id = (int) $id ?? $this->id;
        $permissao = self::instanciarPorId($id);
        if (!empty($permissao)) {
            if (ESTADO_CONSELHO !== "BR") {
                $usuario = Usuarios::instanciarPorId($permissao->Usuario);
                if (empty($usuario)) {
                    throw new Exception("Usuário não encontrado.");
                } else if ($usuario->estado_conselho !== ESTADO_CONSELHO) {
                    throw new Exception("Não é possível excluir permissões de outras pessoas.");
                } else if ($usuario->id !== ID_USER) {
                    throw new Exception("Não é possível excluir permissões de sí mesmo.");
                }
            }

            $excluir = $permissao->excluir();
            self::carregarPermissoes();
            return $excluir;
        }
        return false;
    }
}
