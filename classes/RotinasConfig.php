<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

class RotinasConfig extends ClasseBase
{
    public $id;
    public $criado_em;
    public $atualizado_em;
    public $ultimo_codigo;

    protected $_tabela = array(
        'nome' => 'TBLRotinasConfig',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "criado_em",
            "atualizado_em",
            "ultimo_codigo",
        ),
        'permissao' => ''
    );

    public function __construct()
    {
        parent::__construct();
    }

    public static function obterUltimoCodigo($incrementar = false): int
    {
        $config = self::instanciarPorId(1);
        $ultimoCodigo = $config->ultimo_codigo;
        $ultimoCodigo++;

        if (!$incrementar) {
            return $ultimoCodigo;
        }
        
        $config->ultimo_codigo = $ultimoCodigo;
        $salvar = $config->salvar();
        if ($salvar['row_count'] === 1) {
            return $ultimoCodigo;
        }

        throw new \RuntimeException('Falha ao atualizar o último código da rotina.');
    }
}
