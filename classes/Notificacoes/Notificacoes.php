<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class Notificacoes extends ClasseBase
{
    public $id;
    public $titulo;
    public $texto;
    public $html_email;
    public $cor;
    public $botao_label;
    public $botao_url;
    public $criado_em;
    public $disparada_como_toast;
    public $envia_email;
    public $destinatarios;
    public $id_notificacao;
    public $id_usuario;
    public $lida;
    public $lida_em;
    public $exibir_em_destaque;
    public $ver_notificacoes_nao_lidas = false;
    public $toast_total_exibicoes = 1;
    public $toast_intervalo_minutos = 5;
    public $toast_exibicoes;
    public $toast_ultima_exibicao_em;
    public $toast_proxima_exibicao_em;

    public $id_notificacao_usuario;
    public $total;

    protected $_tabela = array(
        'nome' => 'notificacoes',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "titulo",
            "texto",
            "html_email",
            "cor",
            "botao_label",
            "botao_url",
            "criado_em",
            "envia_email",
            "exibir_em_destaque",
            "toast_total_exibicoes",
            "toast_intervalo_minutos",
        ),
        'permissao' => false
    );

    public function __construct() {}

    /* CORES DISPONIVEIS */
    // success,
    // info,
    // warning,
    // danger,
    // maroon,
    // primary,
    // secondary

    public function getNotificacoes(){
        $this->queryCorrente = $this->getQueryBase();
        $result = $this->buscar();
        return $result;
    }
}
