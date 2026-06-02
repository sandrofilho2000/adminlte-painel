<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class NotificacoesProgramadas extends ClasseBase
{
    public $id;
    public $esquema;
    public $tabela;
    public $nome_coluna_pk = "id";
    public $valor_pk;
    public $descricao;
    public $ativo;
    public $notificacao_criada;
    public $id_notificacao;
    public $criado_em;

    protected $_tabela = array(
        'nome' => 'TBLNotificacoesProgramadas',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "esquema",
            "tabela",
            "nome_coluna_pk",
            "valor_pk",
            "descricao",
            "ativo",
            "notificacao_criada",
            "id_notificacao",
            "criado_em",
        ),
        'permissao' => ''
    );

    public function __construct() {}

    public function getNotificacoesProgramadas($id_notificacao = null)
    {
        $id_notificacao = $this->id_notificacao ?? $id_notificacao;
        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_notificacao", $id_notificacao);
        $result = $this->buscar();

        if (empty($result)) {
            return null;
        }

        $programadas = [];

        foreach ($result as $notificacao_programada) {
            $condicoes = (new NotificacoesProgramadasCondicoes())
                ->getNotificacoesProgramadasCondicoes($notificacao_programada);

            if ($condicoes === false) {
                return false;
            }

            if (!empty($condicoes)) {
                $notificacao_programada->condicoes = $condicoes;
            }

            $programadas[] = $notificacao_programada;
        }

        return $programadas;
    }

    // public function getNotificacoesProgramadas($esquema = null, $tabela = null, $nome_coluna_pk = null, $valor_pk = null)
    // {
    //     $esquema = $this->esquema ?? $esquema;
    //     $tabela = $this->tabela ?? $tabela;
    //     $nome_coluna_pk = $this->nome_coluna_pk ?? $nome_coluna_pk;
    //     $valor_pk = $this->valor_pk ?? $valor_pk;
    //     $this->queryCorrente = $this->getQuerybase();
    //     $this->filtrar("");
    // }
}
