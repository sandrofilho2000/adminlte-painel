<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class Notificacoes extends ClasseBase
{
    public $id;
    public $title;
    public $content;
    public $email_html;
    public $color;
    public $button_label;
    public $button_url;
    public $created_at;
    public $disparada_como_toast;
    public $send_email;
    public $destinatarios;
    public $id_notificacao;
    public $id_usuario;
    public $lida;
    public $lida_em;
    public $featured;
    public $ver_notificacoes_nao_lidas = false;
    public $toast_total_views = 1;
    public $toast_interval_minutes = 5;
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
            "title",
            "content",
            "email_html",
            "color",
            "button_label",
            "button_url",
            "created_at",
            "send_email",
            "featured",
            "toast_total_views",
            "toast_interval_minutes",
        ),
        'permissao' => false
    );

    public function __construct() {}

    private const LEGACY_COLUMN_ALIASES = [
        'titulo' => 'title',
        'texto' => 'content',
        'html_email' => 'email_html',
        'cor' => 'color',
        'botao_label' => 'button_label',
        'botao_url' => 'button_url',
        'criado_em' => 'created_at',
        'envia_email' => 'send_email',
        'exibir_em_destaque' => 'featured',
        'toast_total_exibicoes' => 'toast_total_views',
        'toast_intervalo_minutos' => 'toast_interval_minutes',
    ];

    public function __get($name)
    {
        if (isset(self::LEGACY_COLUMN_ALIASES[$name])) {
            $property = self::LEGACY_COLUMN_ALIASES[$name];
            return $this->$property;
        }

        return null;
    }

    public function __set($name, $value): void
    {
        if (isset(self::LEGACY_COLUMN_ALIASES[$name])) {
            $property = self::LEGACY_COLUMN_ALIASES[$name];
            $this->$property = $value;
            return;
        }

        $this->$name = $value;
    }

    public function __isset($name): bool
    {
        if (isset(self::LEGACY_COLUMN_ALIASES[$name])) {
            $property = self::LEGACY_COLUMN_ALIASES[$name];
            return isset($this->$property);
        }

        return false;
    }

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
