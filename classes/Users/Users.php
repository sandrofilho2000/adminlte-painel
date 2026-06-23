<?php

class Users extends \Classes\ClasseBase
{
    public $id;
    public $email;
    public $first_name;
    public $last_name;
    public $password_hash;
    public $status;
    public $created_at;
    public $updated_at;

    protected $_tabela = [
        'nome' => 'users',
        'schema' => null,
        'chave_primaria' => ['id'],
        'colunas' => [
            'id',
            'email',
            'first_name',
            'last_name',
            'password_hash',
            'status',
            'created_at',
            'updated_at',
        ],
        'permissao' => false,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getUsuarioPorEmailLogin($email = null)
    {
        $email = strtolower(trim((string) ($email ?? $this->email ?? '')));

        if ($email === '') {
            return null;
        }

        $this->queryCorrente = "
            SELECT
                id,
                email,
                first_name,
                last_name,
                password_hash,
                status,
                created_at,
                updated_at
            FROM users
            WHERE 1=1
        ";
        $this->filtrar('email', $email);
        $this->limitar(1);

        $result = $this->buscar(true);
        return $result[0] ?? null;
    }

    public static function autenticarPorEmail(string $email, string $password): ?array
    {
        $users = new self();
        $user = $users->getUsuarioPorEmailLogin($email);

        if (empty($user) || ($user['status'] ?? '') !== 'active') {
            return null;
        }

        if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            global $pdo;

            if ($pdo instanceof \PDO) {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $stmt->execute([
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]);
            }
        }

        unset($user['password_hash']);
        return $user;
    }
}
