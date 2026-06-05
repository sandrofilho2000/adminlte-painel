<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

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
        'nome' => 'TBLNotificacoes',
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


    public function enviaEmail($emails)
    {
        $emailService = new EmailService();
        $emailService->queueEmails(
            'Notificações',
            $this->titulo,
            $this->html_email ?? $this->texto,
            $emails,
            $this->botao_label,
            $this->botao_url,
        );
    }

    public function criaNotificacao()
    {
        $this->criado_em = (new DateTime())->format('Y-m-d H:i:s');
        if (empty($this->titulo)) {
            return;
        }
        $incluir = $this->incluir();

        $NotificacoesUsuarios = new NotificacoesUsuarios();
        $NotificacoesUsuarios->destinatarios = $this->destinatarios;
        $NotificacoesUsuarios->id_notificacao = $incluir['id'];
        $NotificacoesUsuarios->disparada_como_toast = 0;
        $destinatarios_id = $NotificacoesUsuarios->criaNotificacoesUsuario();

        $emails = [];

        foreach ($destinatarios_id as $destinatario_id) {
            $email = (new NotificacoesUsuarios())->getDestinatarioEmail($destinatario_id);
            if ($email && !in_array($email, $emails)) {
                $emails[] = $email;
            }
        }

        if ($this->envia_email) {
            $this->enviaEmail($emails);
        }

        return $incluir;
    }

    private function normalizarBooleano($valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_numeric($valor)) {
            return ((int) $valor) === 1;
        }

        if ($valor === null) {
            return false;
        }

        $normalizado = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalizado ?? false;
    }

    public function contarNaoLidas()
    {
        $this->queryCorrente = "SELECT *
        FROM TBLNotificacoes n
        INNER JOIN TBLNotificacoesUsuarios nu ON n.id = nu.id_notificacao
        WHERE 1=1 ";
        $this->filtrar("nu.id_usuario", ID_USER);
        $this->filtrar("nu.lida", 0);
        $this->ordenar("n.criado_em", 'desc');
        $result = $this->buscar();
        $result = $this->filtrarNotificacoesProgramadasANaoExibir($result);
        // $result = $this->filtrarNotificacoesProgramadasANaoExibir($result);
        $total = count($result) ? count($result) : 0;
        return $total;
    }

    public function getNotificacoes()
    {
        $this->queryCorrente = "SELECT
            n.id,
            n.titulo,
            n.texto,
            n.html_email,
            n.cor,
            n.botao_label,
            n.botao_url,
            n.criado_em,
            n.envia_email,
            n.exibir_em_destaque,
            n.toast_total_exibicoes,
            n.toast_intervalo_minutos,
            nu.disparada_como_toast,
            nu.toast_exibicoes,
            nu.toast_ultima_exibicao_em,
            nu.toast_proxima_exibicao_em,
            nu.id_notificacao,
            nu.id as id_notificacao_usuario,
            nu.id_usuario,
            nu.lida,
            nu.lida_em
        FROM TBLNotificacoes n
        INNER JOIN TBLNotificacoesUsuarios nu ON n.id = nu.id_notificacao
        WHERE 1=1 ";
        $this->filtrar("nu.id_usuario", ID_USER);
        $this->ordenar("n.criado_em", 'desc');
        $this->limitar(350);
        $result = $this->buscar();
        $result = $this->filtrarNotificacoesProgramadasANaoExibir($result);
        // $result = $this->prepararNotificacoesProgramadasParaHistorico($result);
        $total = $this->contarNaoLidas();
        return array("notificacoes" => $result, "total" => $total);
    }

    public function getNotificacoesTable()
    {
        $this->queryCorrente = "SELECT
            n.id,
            n.titulo,
            n.texto,
            n.html_email,
            n.cor,
            n.botao_label,
            n.botao_url,
            n.criado_em,
            n.envia_email,
            n.exibir_em_destaque,
            n.toast_total_exibicoes,
            n.toast_intervalo_minutos,
            nu.disparada_como_toast,
            nu.toast_exibicoes,
            nu.toast_ultima_exibicao_em,
            nu.toast_proxima_exibicao_em,
            nu.id_notificacao,
            nu.id as id_notificacao_usuario,
            nu.id_usuario,
            nu.lida,
            nu.lida_em
        FROM TBLNotificacoes n
        INNER JOIN TBLNotificacoesUsuarios nu ON n.id = nu.id_notificacao
        WHERE 1=1  ";
        $this->filtrar("nu.id_usuario", ID_USER);
        if ($this->normalizarBooleano($this->ver_notificacoes_nao_lidas)) {
            $this->filtrar("nu.lida", 0);
        }
        $this->ordenar("n.criado_em", 'desc');
        $result = $this->buscar();
        $result->data = $this->filtrarNotificacoesProgramadasANaoExibir($result->data);
        // $result = $this->prepararNotificacoesProgramadasParaHistorico($result);
        return $result;
    }

    public function getNotificacoesRecentes()
    {
        $this->queryCorrente = "SELECT
            nu.id,
            n.titulo,
            n.texto,
            n.html_email,
            n.cor,
            n.botao_label,
            n.botao_url,
            n.criado_em,
            n.envia_email,
            n.exibir_em_destaque,
            n.toast_total_exibicoes,
            n.toast_intervalo_minutos,
            nu.disparada_como_toast,
            nu.toast_exibicoes,
            nu.toast_ultima_exibicao_em,
            nu.toast_proxima_exibicao_em,
            nu.id as id_notificacao_usuario,            
            nu.id_notificacao,
            nu.id_usuario,
            nu.lida,
            nu.lida_em
        FROM TBLNotificacoes n
        INNER JOIN TBLNotificacoesUsuarios nu ON n.id = nu.id_notificacao
        WHERE 1=1 ";
        $this->filtrar("nu.id_usuario", ID_USER);
        $this->filtrar("nu.lida", 0);
        $this->ordenar("n.criado_em", 'desc');
        $result = $this->buscar();

        if (empty($result)) {
            return $result;
        }

        $result = $this->filtrarNotificacoesProgramadasANaoExibir($result);

        $notificacoes_recentes = [];

        foreach ($result as $notificacao) {
            if (!$this->notificacaoPodeSerExibidaComoToast($notificacao)) {
                continue;
            }

            $this->marcarNotificacaoToastExibida($notificacao);
            $notificacoes_recentes[] = $notificacao;
        }

        return $notificacoes_recentes;
    }

    public function marcarNotificacaoComoLida(...$params)
    {
        $id_notificacao_usuario = (int) ($this->id_notificacao_usuario ?? ($params[0] ?? 0));

        if ($id_notificacao_usuario <= 0) {
            return ['tipo' => 'success', 'row_count' => 0];
        }

        $notificacao_usuario = (new NotificacoesUsuarios())->instanciarPorId($id_notificacao_usuario);
        if (!$notificacao_usuario || (int) ($notificacao_usuario->id_usuario ?? 0) !== (int) ID_USER) {
            return ['tipo' => 'success', 'row_count' => 0];
        }

        $notificacao_usuario->disparada_como_toast = 1;
        $notificacao_usuario->lida = 1;
        $notificacao_usuario->lida_em = (new DateTime())->format('Y-m-d H:i:s');

        return $notificacao_usuario->salvar();
    }

    public function lerNotificacoes()
    {
        $notificacoes = $this->getNotificacoes();
        foreach ($notificacoes['notificacoes'] as $notificacao) {
            if (!empty($notificacao->exibir_em_destaque)) {
                continue;
            }

            $notificacao_usuario = (new NotificacoesUsuarios())->instanciarPorId($notificacao->id_notificacao_usuario);
            if (!$notificacao_usuario) {
                continue;
            }

            $notificacao_usuario->lida = true;
            $notificacao_usuario->lida_em = (new DateTime())->format('Y-m-d H:i:s');
            $notificacao_usuario->salvar();
        }
    }

    public function lerNotificacao()
    {
        $notificacao_usuario = (new NotificacoesUsuarios())->instanciarPorId($this->id_notificacao_usuario);
        if (!$notificacao_usuario) {
            return;
        }

        $notificacao_usuario->lida = true;
        $notificacao_usuario->lida_em = (new DateTime())->format('Y-m-d H:i:s');
        $notificacao_usuario->salvar();
    }

    private function notificacaoFoiCriadaNosUltimosVinteMinutos($notificacao)
    {
        $criado_em = strtotime((string) ($notificacao->criado_em ?? ''));

        if ($criado_em === false) {
            return false;
        }

        return $criado_em >= (time() - (20 * 60));
    }

    private function obterToastExibicoes($notificacao)
    {
        return max(0, (int) ($notificacao->toast_exibicoes ?? 0));
    }

    private function obterToastTotalExibicoes($notificacao)
    {
        return max(1, (int) ($notificacao->toast_total_exibicoes ?? 1));
    }

    private function obterToastIntervaloMinutos($notificacao)
    {
        $intervalo = $notificacao->toast_intervalo_minutos ?? 5;

        if ($intervalo === null || $intervalo === '') {
            return 5;
        }

        return max(0, (int) $intervalo);
    }

    private function obterTimestampProximaExibicaoToast($notificacao)
    {
        $proxima_exibicao = (string) ($notificacao->toast_proxima_exibicao_em ?? '');

        if (!empty($proxima_exibicao)) {
            $timestamp = strtotime($proxima_exibicao);

            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        $base = (string) ($notificacao->toast_ultima_exibicao_em ?? '');

        if (empty($base)) {
            $base = (string) ($notificacao->criado_em ?? '');
        }

        $timestamp_base = strtotime($base);

        if ($timestamp_base === false) {
            return null;
        }

        return $timestamp_base + ($this->obterToastIntervaloMinutos($notificacao) * 60);
    }

    private function notificacaoPodeSerExibidaComoToast($notificacao)
    {
        if (empty($notificacao->exibir_em_destaque)) {
            if ((int) ($notificacao->disparada_como_toast ?? 0) !== 0) {
                return false;
            }

            if (!empty($notificacao->condicoes)) {
                return true;
            }

            return $this->notificacaoFoiCriadaNosUltimosVinteMinutos($notificacao);
        }

        $toast_exibicoes = $this->obterToastExibicoes($notificacao);
        $toast_total_exibicoes = $this->obterToastTotalExibicoes($notificacao);

        if ($toast_exibicoes >= $toast_total_exibicoes) {
            return false;
        }

        if ($toast_exibicoes === 0) {
            return true;
        }

        $proxima_exibicao = $this->obterTimestampProximaExibicaoToast($notificacao);

        if ($proxima_exibicao === null) {
            return true;
        }

        return $proxima_exibicao <= time();
    }

    private function marcarNotificacaoToastExibida($notificacao)
    {
        $notificacao_usuario = (new NotificacoesUsuarios())->instanciarPorId($notificacao->id_notificacao_usuario);

        if (!$notificacao_usuario) {
            return false;
        }

        $agora = new DateTime();
        $agora_formatado = $agora->format('Y-m-d H:i:s');
        $toast_exibicoes = $this->obterToastExibicoes($notificacao) + 1;
        $toast_total_exibicoes = $this->obterToastTotalExibicoes($notificacao);
        $proxima_exibicao = null;

        if ($toast_exibicoes < $toast_total_exibicoes) {
            $proxima_exibicao = (clone $agora)
                ->modify('+' . $this->obterToastIntervaloMinutos($notificacao) . ' minutes')
                ->format('Y-m-d H:i:s');
        }

        $notificacao_usuario->disparada_como_toast = 1;
        $notificacao_usuario->toast_exibicoes = $toast_exibicoes;
        $notificacao_usuario->toast_ultima_exibicao_em = $agora_formatado;
        $notificacao_usuario->toast_proxima_exibicao_em = $proxima_exibicao;
        $notificacao_usuario->salvar();

        $notificacao->disparada_como_toast = 1;
        $notificacao->toast_exibicoes = $toast_exibicoes;
        $notificacao->toast_ultima_exibicao_em = $agora_formatado;
        $notificacao->toast_proxima_exibicao_em = $proxima_exibicao;

        return true;
    }

    private function obterDadosProgramacaoNotificacao($notificacao)
    {
        $notificacao_programada = (new NotificacoesProgramadas())
            ->getNotificacoesProgramadas($notificacao->id_notificacao);

        if ($notificacao_programada === false) {
            return [
                'possui_programada' => true,
                'ativa' => false,
                'condicoes' => [],
            ];
        }

        $condicoes = [];

        if (is_array($notificacao_programada)) {
            foreach ($notificacao_programada as $programada) {
                if (!empty($programada->condicoes)) {
                    $condicoes = array_merge($condicoes, $programada->condicoes);
                }
            }
        }

        return [
            'possui_programada' => !empty($notificacao_programada),
            'ativa' => true,
            'condicoes' => $condicoes,
        ];
    }

    public function filtrarNotificacoesProgramadasANaoExibir($notificacoes)
    {
        if (empty($notificacoes)) {
            return $notificacoes;
        }

        $notificacoes_filtradas = [];

        foreach ($notificacoes as $notificacao) {
            $dados_programacao = $this->obterDadosProgramacaoNotificacao($notificacao);

            if (!empty($dados_programacao['possui_programada']) && empty($dados_programacao['ativa'])) {
                continue;
            }

            if (!empty($dados_programacao['condicoes'])) {
                $notificacao->condicoes = $dados_programacao['condicoes'];
            }

            $notificacoes_filtradas[] = $notificacao;
        }

        return array_values($notificacoes_filtradas);
    }

    private function prepararNotificacoesProgramadasParaHistorico($notificacoes)
    {
        if (empty($notificacoes)) {
            return $notificacoes;
        }

        $notificacoes_preparadas = [];

        foreach ($notificacoes as $notificacao) {
            $dados_programacao = $this->obterDadosProgramacaoNotificacao($notificacao);

            if (!empty($dados_programacao['possui_programada']) && empty($dados_programacao['ativa'])) {
                $notificacao->lida = 1;
            }

            if (!empty($dados_programacao['condicoes'])) {
                $notificacao->condicoes = $dados_programacao['condicoes'];
            }

            $notificacoes_preparadas[] = $notificacao;
        }

        return array_values($notificacoes_preparadas);
    }
}
