<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use DateTime;

class ChamadosComentarios extends ClasseBase
{
    public $id;
    public $id_chamado;
    public $comentario;
    public $criado_por_id;
    public $criado_em;
    public $mencoes;

    public $apresentacao;

    protected $_tabela = array(
        'nome' => 'TBLChamados_Comentarios',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_chamado",
            "comentario",
            "criado_por_id",
            "criado_em",
        ),
        'permissao' => '00072'
    );

    public function __construct() {}

    public function getMencionaveis($id_chamado = null, $responsavel_id = null, $observadores_json = null)
    {

        $mencionaveis = [];
        $id_chamado = $id_chamado ?? $this->id_chamado;
        if (empty($id_chamado)) {
            return [];
        }

        $chamado = Chamados::instanciarPorId($id_chamado);
        $atributos_editaveis = (new Chamados())->getAtributosEditaveis($id_chamado);
        $id_usuario_atual = defined('ID_USER') ? (string) ID_USER : '';

        if (!$atributos_editaveis['criar_comentarios']) {
            return [];
        }

        $Usuarios = new Usuarios();
        $adicionarMencionavel = function ($idUsuario) use (&$mencionaveis, $Usuarios, $id_usuario_atual) {
            $idUsuario = trim((string) $idUsuario);
            if ($idUsuario === '') {
                return;
            }

            if ($id_usuario_atual !== '' && $idUsuario === $id_usuario_atual) {
                return;
            }

            $usuario = Usuarios::executarComIgnorados(function () use ($Usuarios, $idUsuario) {
                $usuarioBase = $Usuarios->instanciarPorId($idUsuario);
                if (empty($usuarioBase) || empty($usuarioBase->apresentacao)) {
                    return null;
                }

                try {
                    $usuariosEncontrados = $Usuarios->getUsuarioPorString($usuarioBase->apresentacao);
                    if (!empty($usuariosEncontrados[0]) && !empty($usuariosEncontrados[0]->id)) {
                        return $usuariosEncontrados[0];
                    }
                } catch (\Throwable $e) {
                    // Mantém o registro carregado por ID quando a busca por texto falhar.
                }

                return $usuarioBase;
            }, true, true);

            if (!empty($usuario) && !empty($usuario->id)) {
                $mencionaveis[(string) $usuario->id] = $usuario;
            }
        };

        $adicionarMencionavel($chamado->criado_por_id ?? null);

        if (trim((string) $responsavel_id) === '') {
            $responsavel_id = $chamado->responsavel_id ?? null;
        }

        $adicionarMencionavel($responsavel_id);

        $observadoresSelecionados = [];
        if ($observadores_json !== null) {
            if (is_string($observadores_json)) {
                $observadoresDecodificados = json_decode($observadores_json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($observadoresDecodificados)) {
                    $observadoresSelecionados = $observadoresDecodificados;
                }
            } elseif (is_array($observadores_json)) {
                $observadoresSelecionados = $observadores_json;
            }
        } else {
            $observadores = (new ChamadosObservadores())->getObsevadores($id_chamado);
            foreach ($observadores as $observador) {
                if (!empty($observador->id_usuario)) {
                    $observadoresSelecionados[] = $observador->id_usuario;
                } elseif (!empty($observador->id)) {
                    $observadoresSelecionados[] = $observador->id;
                }
            }
        }

        $observadoresSelecionados = array_values(array_unique(array_filter(array_map(function ($valor) {
            $valor = trim((string) $valor);
            return $valor !== '' ? (int) $valor : 0;
        }, $observadoresSelecionados))));

        foreach ($observadoresSelecionados as $idObservador) {
            $adicionarMencionavel($idObservador);
        }

        $sou_de_informatica = trim((string) ($_SESSION['setor'] ?? '')) === 'Coordenadoria de Informática e Tecnologia';
        if ($sou_de_informatica) {
            $tecnicosTi = $Usuarios->getUsuariosPorSetor(2);
            foreach ($tecnicosTi as $tecnicoTi) {
                $adicionarMencionavel($tecnicoTi->id ?? null);
            }
        }

        return array_values($mencionaveis);
    }

    public function criaComentario()
    {
        $Chamados = new Chamados();
        $atributos_editaveis = $Chamados->getAtributosEditaveis($this->id_chamado);

        if(!$atributos_editaveis['criar_comentarios']) {
            throw new \Exception('Você não possui permissão para criar comentários neste chamado.');
        }

        $result = self::executarComIgnorados(
            function () use ($atributos_editaveis, $Chamados) {
                if (empty($this->comentario)) {
                    throw new \Exception('Não é possível criar um comentário vazio.');
                }
        
                $this->criado_por_id = ID_USER;
                $this->criado_em = (new DateTime())->format('Y-m-d H:i:s');
                $incluir = $this->incluir();
        
                if (!empty($incluir['id'])) {
                    $chamado = $Chamados->getChamado($this->id_chamado);
                    $chamado['comentario'] = $this->comentario;
                    $chamado['mencoes'] = $this->mencoes;
                    $Chamados->criaNotificacoes('criar-comentario', $chamado);
                }
        
                if (!empty($this->mencoes)) {
                    $chamado['mencoes'] = $this->mencoes;
                    $Chamados->criaNotificacoes('mencoes', $chamado);
                }
        
                return $incluir;
            },
            true,
            true
        );

        return $result;
    }

    public function excluirComentario()
    {
        $comentario = self::instanciarPorId($this->id);

        if (empty($comentario)) {
            throw new \Exception('Comentário não encontrado.');
        }

        $chamado = (new Chamados())->instanciarPorId($comentario->id_chamado);
        if (!empty($chamado) && ((int)$chamado->arquivado === 1 || $chamado->coluna === 'arquivados')) {
            throw new \Exception('Não é possível excluir comentários de cards arquivados.');
        }

        if ((string)$comentario->criado_por_id !== (string)ID_USER) {
            throw new \Exception('Você não tem permissão para excluir este comentário.');
        }

        $exclusao = $this->excluir($comentario->id);
        if ($exclusao <= 0) {
            throw new \Exception('Não foi possível excluir o comentário.');
        }

        return [
            'status' => 'success',
            'id' => (string)$comentario->id
        ];
    }

    public function getComentarios($id_chamado = null)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $this->queryCorrente = "SELECT c.*, u.apresentacao FROM TBLChamados_Comentarios c LEFT JOIN TBLUsuarios u ON c.criado_por_id = u.id WHERE 1=1 ";
        $this->filtrar("c.id_chamado", $id_chamado);
        $this->ordenar("c.criado_em");
        $atualizacoes = $this->buscar();
        return $atualizacoes;
    }
}
