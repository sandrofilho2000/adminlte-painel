<?php

namespace Classes;

use PDO;

class Usuarios extends ClasseBase
{
    public $id;
    public $id_usuario;
    public $tipo;
    public $nome_setor;
    public $nome_cargo;
    public $nome_usuario;
    public $email;
    public $apresentacao;
    public $instituicao;
    public $estado_conselho;
    public $status;
    public $criado_em;
    public $cargo;
    public $setor;
    public $termo;
    private $buscar_crefs;

    private $tipo_login;
    private $login;
    private $senha;
    private $primeiro_acesso;
    private $senha_expirada;
    private $resetcode;
    private $resettoken;
    private $resettokenexp;
    private $resetcodeexp;
    private $resetcodeattempts;
    private $remember_token;

    protected $_tabela = array(
        'nome' => 'TBLUsuarios',
        'chave_primaria' => array('id'),
        'colunas' => array(
            'tipo',
            'email',
            'apresentacao',
            'instituicao',
            'estado_conselho',
            'status',
            'criado_em'
        ),
        'permissao' => false
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function setPermissao($valor)
    {
        $this->_tabela['permissao'] = $valor;
    }

    public function getUsuarioPorString($termo = null, $buscar_crefs = false)
    {
        $termo = $termo ?? $this->termo;
        $buscar_crefs = $buscar_crefs ?? $this->buscar_crefs;
        $this->queryCorrente = "
            SELECT 
                u.id, 
                u.apresentacao, 
                c.nome_cargo, 
                s.nome_setor
            FROM TBLUsuarios u
            LEFT JOIN TBLUsuarios_Cargos uc ON u.id = uc.id_usuario
            LEFT JOIN TBLCargos c ON uc.id_cargo = c.id
            LEFT JOIN TBLSetores s ON c.id_setor = s.id
            WHERE 1=1
            AND u.apresentacao IS NOT NULL
            AND u.status = 1
        ";

        if ($buscar_crefs) {
            $this->filtrar("s.nome_setor", "Plenário", "DIFERENTE");
        }

        if ($_SESSION['estado_conselho'] != "BR") {
            $this->filtrar("u.estado_conselho", $_SESSION['estado_conselho']);
        }

        $this->filtrar("u.apresentacao", $termo, 'LIKE');
        $this->filtrar("u.id", ID_USER, 'DIFERENTE');
        $this->ordenar("u.apresentacao");
        $this->limitar(100);
        self::habilitarIgnorarPermissao();
        $result =  $this->buscar(true);
        self::desabilitarIgnorarPermissao();

        return $result;
    }

    public function getUsuarioPorStringSala($termo = null)
    {
        $termo = $termo ?? $this->termo;
        $this->queryCorrente = "
            SELECT 
                u.id, 
                u.apresentacao, 
                c.nome_cargo, 
                s.nome_setor
            FROM TBLUsuarios u
            LEFT JOIN TBLUsuarios_Cargos uc ON u.id = uc.id_usuario
            LEFT JOIN TBLCargos c ON uc.id_cargo = c.id
            LEFT JOIN TBLSetores s ON c.id_setor = s.id
            WHERE 1=1
            AND u.apresentacao IS NOT NULL
            AND u.status = 1
        ";

        $this->filtrar("u.apresentacao", $termo, 'LIKE');
        $this->filtrar("u.id", ID_USER, 'DIFERENTE');
        $this->filtrar("s.nome_setor", "NOT NULL", "IS");
        $this->filtrar("s.nome_setor", "Plenário", "DIFERENTE");
        $this->filtrar("u.estado_conselho", "BR");
        $this->filtrar("u.status", 1);


        $this->ordenar("u.apresentacao");
        $this->limitar(100);
        self::habilitarIgnorarPermissao();
        $result =  $this->buscar(true);
        self::desabilitarIgnorarPermissao();

        return $result;
    }

    public function getPrimeirosUsuarios()
    {
        if ($_SESSION['estado_conselho'] != "BR") {
            return [];
        }
        $this->queryCorrente = "
            SELECT 
                u.id, 
                u.apresentacao, 
                c.nome_cargo, 
                s.nome_setor
            FROM TBLUsuarios u
            LEFT JOIN TBLUsuarios_Cargos uc ON u.id = uc.id_usuario
            LEFT JOIN TBLCargos c ON uc.id_cargo = c.id
            LEFT JOIN TBLSetores s ON c.id_setor = s.id
            WHERE 1=1
        ";

        $this->filtrar("s.nome_setor", "NOT NULL", "IS");
        $this->filtrar("s.nome_setor", "Plenário", "DIFERENTE");
        $this->filtrar("u.estado_conselho", "BR");
        $this->filtrar("u.status", 1);
        $this->ordenar("u.apresentacao");
        $this->limitar(100);
        $result =  $this->buscar(true);
        return $result;
    }

    public function getUsuarioAtual()
    {
        $this->queryCorrente = " SELECT * FROM TBLUsuarios WHERE 1=1 ";
        $this->filtrar("id", $_SESSION['id']);
        $this->limitar("1");

        self::habilitarIgnorarPermissao();
        try {
            $result = $this->buscar(true) ?? null;
        } finally {
            self::desabilitarIgnorarPermissao();
        }

        return $result[0] ?? null;
    }

    public function getColegasdeSetor($idUsuario = null)
    {
        $idUsuario = (int)($idUsuario ?? ($_SESSION['id'] ?? 0));
        if ($idUsuario <= 0) {
            return [];
        }

        if (defined('ESTADO_CONSELHO') && ESTADO_CONSELHO != "BR") {
            $this->queryCorrente = "SELECT id, apresentacao FROM TBLUsuarios u WHERE 1=1 ";
            $this->filtrar("estado_conselho", ESTADO_CONSELHO);
        } else {
            $this->queryCorrente = "
                    SELECT DISTINCT u.id, u.apresentacao
                    FROM TBLUsuarios u
                    INNER JOIN TBLUsuarios_Cargos uc ON uc.id_usuario = u.id
                    INNER JOIN TBLCargos c ON c.id = uc.id_cargo
                    WHERE c.id_setor IN (
                        SELECT DISTINCT c2.id_setor
                        FROM TBLUsuarios_Cargos uc2
                        INNER JOIN TBLCargos c2 ON c2.id = uc2.id_cargo
                        WHERE uc2.id_usuario = {$idUsuario}
                    )
                ";
        }

        $this->filtrar("u.id", $idUsuario, "DIFERENTE");
        $this->filtrar("status", 1);
        $this->ordenar("apresentacao");
        $result = $this->buscar(true) ?? [];
        return $result;
    }

    public function getUsuariosPorSetor($idSetor = null)
    {
        $idSetor = (int)$idSetor;

        if ($idSetor <= 0) {
            return [];
        }

        $estadoConselhoUsuario = strtoupper(trim((string) ($_SESSION['estado_conselho'] ?? '')));
        $ignorarFiltroCref = $estadoConselhoUsuario !== '' && $estadoConselhoUsuario !== 'BR';

        $this->queryCorrente = "
            SELECT DISTINCT u.id, u.apresentacao
                , c.nome_cargo
                , s.nome_setor
            FROM TBLUsuarios u
            INNER JOIN TBLUsuarios_Cargos uc ON uc.id_usuario = u.id
            INNER JOIN TBLCargos c ON c.id = uc.id_cargo
            LEFT JOIN TBLSetores s ON s.id = c.id_setor
            WHERE 1=1
        ";

        $this->filtrar("c.id_setor", $idSetor);

        if ($ignorarFiltroCref) {
            $this->filtrar("u.estado_conselho", 'BR');
        }

        $this->filtrar("u.status", 1);
        $this->ordenar("u.apresentacao");

        if ($ignorarFiltroCref) {
            return $this->buscarIgnorandoFiltroEstadoConselho(true) ?? [];
        }

        return $this->buscar(true) ?? [];
    }

    private function validarApresentacaoPerfil(?string $nome): string
    {
        $nome = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $nome))) ?? '';
        $tamanho = function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome);
        $maxLength = 150;

        if ($nome === '') {
            throw new \Exception('Informe o nome.');
        }

        if ($tamanho < 3) {
            throw new \Exception('O nome deve ter no minimo 3 caracteres.');
        }

        if ($tamanho > $maxLength) {
            throw new \Exception('O nome deve ter no maximo ' . $maxLength . ' caracteres.');
        }

        if (!preg_match('/^(?=.*\p{L})[\p{L}\p{M}\p{N}]+(?: [\p{L}\p{M}\p{N}]+)*$/u', $nome)) {
            throw new \Exception('O nome deve conter apenas letras, numeros e espacos.');
        }

        return $nome;
    }

    public function editaPerfil()
    {
        $id = (int) (ID_USER ?? 0);

        if ($id <= 0) {
            return ['tipo' => 'fail', 'message' => 'Usuario invalido.'];
        }

        try {
            return Usuarios::executarComIgnorados(function () use ($id) {
                $usuario = $this->instanciarPorId($id);

                if (empty($usuario) || (int) $usuario->id !== $id) {
                    return ['tipo' => 'fail', 'message' => 'Usuario nao encontrado.'];
                }

                $usuario->apresentacao = $this->validarApresentacaoPerfil($this->apresentacao ?? $usuario->apresentacao);
                $salvar = $usuario->salvar();

                if ((int) ($salvar['row_count'] ?? 0) === 1) {
                    $_SESSION['nome'] = $usuario->apresentacao;
                }

                return $salvar;
            }, true, false);
        } catch (\Throwable $e) {
            return ['tipo' => 'fail', 'message' => $e->getMessage()];
        }
    }
}
