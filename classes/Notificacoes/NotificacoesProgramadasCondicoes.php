<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class NotificacoesProgramadasCondicoes extends ClasseBase
{
    public $id;
    public $nome_coluna;
    public $operacao;
    public $valor_coluna;
    public $id_notificacao_programada;
    public $and_ou_or = "AND";


    protected $_tabela = array(
        'nome' => 'TBLNotificacoesProgramadasCondicoes',
        'schema' => 'confef1',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "nome_coluna",
            "operacao",
            "valor_coluna",
            "id_notificacao_programada",
            "and_ou_or",
        ),
        'permissao' => ''
    );

    public function __construct() {}

    public function verificarCondicaoViaQuery($notificacao_programada, $condicoes)
    {
        $esquema_table = $notificacao_programada->esquema . "." . $notificacao_programada->tabela;

        $this->queryCorrente =  "SELECT COUNT(*) as contar FROM $esquema_table WHERE 1=1 ";
        $this->filtrar($notificacao_programada->nome_coluna_pk, $notificacao_programada->valor_pk);

        foreach ($condicoes as $condicao) {
            $tipo_coluna = $this->getPrimitivoColuna($esquema_table, $condicao->nome_coluna);

            if ($tipo_coluna === 'datetime' || $tipo_coluna === 'date') {
                $valor_coluna = (new DateTime($condicao->valor_coluna))->format('Y-m-d H:i:s');
            } else {
                $valor_coluna = $condicao->valor_coluna;
            }

            if($condicao->operacao === "EXPLICITO"){
                $this->filtrar("", $valor_coluna, "EXPLICITO", $condicao->and_ou_or);
            }
            else if (Dao::tabelaTemColuna($esquema_table, $condicao->nome_coluna)) {
                $this->filtrar($condicao->nome_coluna, $valor_coluna, $condicao->operacao, $condicao->and_ou_or);
            } else {
                $this->filtrar("id", "-1");
            }

            $this->filtrar($condicao->nome_coluna, $valor_coluna, $condicao->operacao, $condicao->and_ou_or);
        }

        $this->retornarComoArray = true;

        $dados = $this->buscar();

        if (!$dados || !isset($dados[0]['contar'])) {
            return false;
        }

        return $dados[0]['contar'] > 0;
    }

    public function getNotificacoesProgramadasCondicoes($notificacao_programada)
    {
        $id_notificacao_programada = $notificacao_programada->id;
        $this->queryCorrente = $this->getQuerybase();
        $this->filtrar("id_notificacao_programada", $id_notificacao_programada);
        $condicoes = $this->buscar();

        if (empty($condicoes)) {
            return false;
        }

        $condicoes_validas = $this->verificarCondicaoViaQuery($notificacao_programada, $condicoes);
        if ($condicoes_validas === false) {
            return false;
        }

        return $condicoes;
    }
}
