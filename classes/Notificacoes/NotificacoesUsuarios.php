<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class NotificacoesUsuarios extends ClasseBase
{
    public $id;
    public $id_notificacao;
    public $id_usuario;
    public $lida = 0;
    public $lida_em;
    public $disparada_como_toast = 0;
    public $toast_exibicoes = 0;
    public $toast_ultima_exibicao_em;
    public $toast_proxima_exibicao_em;

    public $destinatarios = [];

    protected $_tabela = array(
        'nome' => 'TBLNotificacoesUsuarios',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_notificacao",
            "id_usuario",
            "lida",
            "lida_em",
            "disparada_como_toast",
            "toast_exibicoes",
            "toast_ultima_exibicao_em",
            "toast_proxima_exibicao_em",
        ),
        'permissao' => false
    );

    public function __construct() {}

    public function getDestinatarioEmail($destinatario)
    {
        $destinatarios = !empty($destinatarios) ? $destinatarios : $this->destinatarios;
        $email = '';
        if (is_numeric($destinatario)) {
            $id_usuario = (int) $destinatario;
        } elseif (is_array($destinatario)) {

            if (isset($destinatario['id'])) {
                $email = $destinatario['email'];
            } elseif (isset($destinatario['id'])) {
                $id_usuario = (int) $destinatario['id'];
            } else {
                return;
            }
        } elseif (is_object($destinatario)) {
            if (isset($destinatario->id)) {
                $email = $destinatario->email;
            } elseif (isset($destinatario->id)) {
                $id_usuario = (int) $destinatario->id;
            } elseif (method_exists($destinatario, 'getId')) {
                $id_usuario = (int) $destinatario->getId();
            } else {
                return;
            }
        } else {
            return;
        }

        if (empty($email)) {
            if (!empty($id_usuario)) {
                $usuario = Usuarios::executarComIgnorados(
                    function () use ($id_usuario) {
                        return Usuarios::instanciarPorId($id_usuario);
                    },
                    true,
                    true
                );
                $email = $usuario->email ?? '';
            }
        }

        return $email;
    }

    public function criaNotificacoesUsuario($destinatarios = [])
    {
        $destinatarios = !empty($destinatarios) ? $destinatarios : $this->destinatarios;

        $ids = [];

        foreach ($destinatarios as $destinatario) {
            if (is_numeric($destinatario)) {
                $id = (int) $destinatario;
            } elseif (is_array($destinatario)) {
                if (isset($destinatario['id_usuario'])) {
                    $id = (int) $destinatario['id_usuario'];
                } elseif (isset($destinatario['id_user'])) {
                    $id = (int) $destinatario['id_user'];
                } elseif (isset($destinatario['id'])) {
                    $id = (int) $destinatario['id'];
                } elseif (isset($destinatario[0])) {
                    $id = (int) $destinatario[0];
                } else {
                    continue;
                }
            } elseif (is_object($destinatario)) {
                if (isset($destinatario->id_usuario)) {
                    $id = (int) $destinatario->id_usuario;
                } elseif (isset($destinatario->id_user)) {
                    $id = (int) $destinatario->id_user;
                } elseif (isset($destinatario->id)) {
                    $id = (int) $destinatario->id;
                } elseif (method_exists($destinatario, 'getId')) {
                    $id = (int) $destinatario->getId();
                } else {
                    continue;
                }
            } else {
                continue;
            }

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));

        foreach ($ids as $id_usuario) {
            if ($id_usuario != ID_USER) {
                $this->id = null;
                $this->id_usuario = $id_usuario;
                $this->lida = 0;
                $this->lida_em = null;
                $this->disparada_como_toast = 0;
                $this->toast_exibicoes = 0;
                $this->toast_ultima_exibicao_em = null;
                $this->toast_proxima_exibicao_em = null;
                $this->incluir();
            }
        }

        return $ids;
    }
}
