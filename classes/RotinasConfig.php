<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class RotinasConfig extends ClasseBase
{
    public $id;
    public $created_at;
    public $updated_at;
    public $last_code;

    protected $_tabela = array(
        'nome' => 'rotinas_config',
        'schema' => 'aurora_tech',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "created_at",
            "updated_at",
            "last_code",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public static function getLastCode()
    {
        $last_code = self::instanciarPorId(1);
        return $last_code->last_code;
    }


    public static function incrementLastRotina()
    {
        $last_code = self::instanciarPorId(1);
        $last_code->last_code = (int)$last_code->last_code++;
        $last_code->salvar();
    }
}
