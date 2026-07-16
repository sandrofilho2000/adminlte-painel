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

    private $cpf_hmac;
    private $cpf_encrypted ;
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
        'schema' => 'confef1',
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

    public function autenticar(string $credencial, string $senha): array
    {
        $credencial = trim($credencial);

        if ($credencial === '' || $senha === '' || strlen($credencial) > 190 || strlen($senha) > 4096) {
            return $this->resultadoAutenticacaoInvalida();
        }

        $consulta = $this->pdo->prepare("
            SELECT id, login, email, tipo_login, apresentacao, instituicao,
                   estado_conselho, senha, senha_expirada, primeiro_acesso
            FROM TBLUsuarios
            WHERE status = 1
              AND (login = :credencial_login OR email = :credencial_email)
        ");
        $consulta->execute([
            'credencial_login' => $credencial,
            'credencial_email' => $credencial,
        ]);
        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $usuario = $this->resolverUsuarioLogin($usuarios, $credencial);

        if ($usuario === null) {
            password_verify($senha, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            return $this->resultadoAutenticacaoInvalida();
        }

        $tipoLogin = strtolower(trim((string) ($usuario['tipo_login'] ?? 'local')));
        $autenticado = $tipoLogin === 'ad'
            ? $this->autenticarNoDiretorioAtivo((string) $usuario['login'], $senha)
            : password_verify($senha, (string) ($usuario['senha'] ?? ''));

        if (!$autenticado) {
            return $this->resultadoAutenticacaoInvalida();
        }

        if ($tipoLogin !== 'ad' && password_needs_rehash((string) $usuario['senha'], PASSWORD_DEFAULT)) {
            $this->atualizarHashSenha((int) $usuario['id'], $senha);
        }

        $trocarSenha = (int) ($usuario['senha_expirada'] ?? 0) === 1
            || (int) ($usuario['primeiro_acesso'] ?? 0) === 1;
        unset($usuario['senha']);

        return [
            'sucesso' => true,
            'usuario' => $usuario,
            'trocar_senha' => $trocarSenha,
            'erro' => null,
        ];
    }

    private function resolverUsuarioLogin(array $usuarios, string $credencial): ?array
    {
        if (count($usuarios) === 1) {
            return $usuarios[0];
        }

        $usuariosPorLogin = array_values(array_filter(
            $usuarios,
            static fn(array $usuario): bool => strcasecmp((string) ($usuario['login'] ?? ''), $credencial) === 0
        ));

        return count($usuariosPorLogin) === 1 ? $usuariosPorLogin[0] : null;
    }

    private function autenticarNoDiretorioAtivo(string $login, string $senha): bool
    {
        $servidor = trim((string) env('AD_SERVER', ''));
        $dominio = trim((string) env('AD_DOMAIN', ''));

        if ($servidor === '' || $dominio === '' || !function_exists('ldap_connect')) {
            return false;
        }

        $conexao = @ldap_connect($servidor);
        if ($conexao === false) {
            return false;
        }

        try {
            ldap_set_option($conexao, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conexao, LDAP_OPT_REFERRALS, 0);

            if (!@ldap_start_tls($conexao)) {
                return false;
            }

            return @ldap_bind($conexao, $dominio . '\\' . $login, $senha);
        } finally {
            ldap_unbind($conexao);
        }
    }

    private function atualizarHashSenha(int $idUsuario, string $senha): void
    {
        $consulta = $this->pdo->prepare('UPDATE TBLUsuarios SET senha = :senha WHERE id = :id AND status = 1');
        $consulta->execute([
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'id' => $idUsuario,
        ]);
    }

    private function resultadoAutenticacaoInvalida(): array
    {
        return [
            'sucesso' => false,
            'usuario' => null,
            'trocar_senha' => false,
            'erro' => 'Usuário/e-mail ou senha inválidos.',
        ];
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
            throw new \Exception('O nome deve ter no mínimo 3 caracteres.');
        }

        if ($tamanho > $maxLength) {
            throw new \Exception('O nome deve ter no máximo ' . $maxLength . ' caracteres.');
        }

        if (!preg_match('/^(?=.*\p{L})[\p{L}\p{M}\p{N}]+(?: [\p{L}\p{M}\p{N}]+)*$/u', $nome)) {
            throw new \Exception('O nome deve conter apenas letras, números e espaços.');
        }

        return $nome;
    }

    public function editaPerfil()
    {
        $id = (int) (ID_USER ?? 0);

        if ($id <= 0) {
            return ['tipo' => 'fail', 'message' => 'Usuário inválido.'];
        }

        try {
            return Usuarios::executarComIgnorados(function () use ($id) {
                $usuario = $this->instanciarPorId($id);

                if (empty($usuario) || (int) $usuario->id !== $id) {
                    return ['tipo' => 'fail', 'message' => 'Usuário não encontrado.'];
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

    public function getUsuarios(){
        $this->queryCorrente = "
            SELECT 
                u.id, 
                u.apresentacao, 
                u.estado_conselho, 
                u.email, 
                u.criado_em, 
                u.status 
            FROM TBLUsuarios u
            WHERE 1=1

        ";
        $this->ordenar("u.estado_conselho");
        $buscar = $this->buscar(true) ?? [];
        return $buscar;
    }
}
