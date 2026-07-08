<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;
use Exception;

class Persistemas extends ClasseBase
{
    public $Sistema;
    public $Rotina;
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
            "Rotina",
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
            throw new Exception("Informe uma permissao valida para editar.");
        }

        $permissao = self::instanciarPorId($id_permissao);

        if (empty($permissao) || (int) ($permissao->id ?? 0) !== $id_permissao) {
            throw new Exception("Permissao nao encontrada.");
        }

        $permissao->Consulta = (int) ($this->Consulta ?? 0) === 1 ? 1 : 0;
        $permissao->Incluir = (int) ($this->Incluir ?? 0) === 1 ? 1 : 0;
        $permissao->Excluir = (int) ($this->Excluir ?? 0) === 1 ? 1 : 0;
        $permissao->Alterar = (int) ($this->Alterar ?? 0) === 1 ? 1 : 0;

        $salvar = $permissao->salvar();
        return $salvar;
    }

    public function editPersistemaseMassa()
    {
        $permissoes = $this->permissoes;
        foreach ($permissoes as $permissao) {
            $this->Consulta = (int) ($permissao['Consulta'] ?? 0) === 1 ? 1 : 0;
            $this->Incluir = (int) ($permissao['Incluir'] ?? 0) === 1 ? 1 : 0;
            $this->Excluir = (int) ($permissao['Excluir'] ?? 0) === 1 ? 1 : 0;
            $this->Alterar = (int) ($permissao['Alterar'] ?? 0) === 1 ? 1 : 0;
            $this->editPersistemas($permissao['id']);
        }
        self::carregarPermissoes();
        return true;
    }


    public function criaPersistemas()
    {
        $this->Rotina = trim((string) ($this->Rotina ?? ''));
        $this->Consulta = (int) ($this->Consulta ?? 0) === 1 ? 1 : 0;
        $this->Incluir = (int) ($this->Incluir ?? 0) === 1 ? 1 : 0;
        $this->Excluir = (int) ($this->Excluir ?? 0) === 1 ? 1 : 0;
        $this->Alterar = (int) ($this->Alterar ?? 0) === 1 ? 1 : 0;

        if ($this->Rotina === '') {
            throw new Exception("Informe a rotina da permissao.");
        }

        if (
            $this->Consulta !== 1
            && $this->Incluir !== 1
            && $this->Excluir !== 1
            && $this->Alterar !== 1
        ) {
            throw new Exception("Informe ao menos uma acao de permissao.");
        }

        $usuariosInformados = $this->Usuarios ?? $this->Usuario ?? '';
        $Usuarios = is_array($usuariosInformados)
            ? $usuariosInformados
            : explode(",", (string) $usuariosInformados);

        if (empty(array_filter($Usuarios, static fn($UsuarioId) => trim((string) $UsuarioId) !== ''))) {
            throw new Exception("Informe ao menos um usuario.");
        }

        foreach ($Usuarios as $UsuarioId) {
            $UsuarioId = trim((string) $UsuarioId);

            if ($UsuarioId === '') {
                continue;
            }

            try {
                $Usuario = Usuarios::instanciarPorId($UsuarioId);

                if (empty($Usuario) || $Usuario->estado_conselho === "ABC") {
                    continue;
                }

                $permissao_existe = self::getPermissoesUsuario($UsuarioId, $this->Rotina);

                if (!empty($permissao_existe)) {
                    $this->editPersistemas($permissao_existe);
                    continue;
                }

                $this->id = null;
                $this->Usuario = $UsuarioId;
                $this->incluir();
                self::carregarPermissoes();
            } catch (\Throwable $e) {
                $mensagem = $e->getMessage();
                $codigo = (string) $e->getCode();

                if ($codigo === '23000' || str_contains($mensagem, '1062 Duplicate entry')) {
                    continue;
                }

                continue;
            }
        }

        return true;
    }

    public static function carregarPermissoes(bool $forcarRecarregamento = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
            $Persistemas->queryCorrente = $Persistemas->getQuerybase('Rotina, Consulta, Incluir, Excluir, Alterar');
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
        $Persistemas->filtrar("Rotina", $rotina);

        $permissao = $Persistemas->buscar(true) ?: [];
        $permissao = $permissao[0] ?? null;
        return $permissao['id'] ?? null;
    }

    public function getPermissoes()
    {
        $tabela = $this->getNomeTabela();
        $this->queryCorrente = "SELECT
        p.id,
        r.Descricao,
        r.tipo_sistema,
        r.Rotina,
        u.apresentacao,
        u.estado_conselho,
        p.Consulta,
        p.Incluir,
        p.Excluir,
        p.Alterar
        FROM $tabela p LEFT JOIN portal.TBLRotinas r ON p.Rotina = r.Rotina LEFT JOIN  confef1.TBLUsuarios u ON p.Usuario = u.id WHERE 1=1 ";
        $result = $this->buscar(true);
        return $result;
    }


    public function deletePermissao($id)
    {
        $id = (int) $id ?? $this->id;
        $permissao = self::instanciarPorId($id);
        if (!empty($permissao)) {
            if (ESTADO_CONSELHO !== "BRRRR") {
                $usuario = Usuarios::instanciarPorId($permissao->Usuario);
                if (empty($usuario)) {
                    if ($usuario->estado_conselho !== ESTADO_CONSELHO) {
                        throw new Exception("Não é possível excluir permissões de outras pessoas.");
                    }
                    throw new Exception("Usuário não encontrado.");
                }
            }

            $excluir = $permissao->excluir();
            self::carregarPermissoes();
            return $excluir;
        }
        return false;
    }
}
