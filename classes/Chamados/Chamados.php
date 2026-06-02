<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;
use Exception;

class Chamados extends ClasseBase
{
    public $id;
    public $titulo;
    public $descricao;
    public $arquivado;
    public $proximo_retorno;
    public $responsavel_id;
    public $tipo_chamado;
    public $modulo;
    public $classificacao;
    public $coluna = "backlog";
    public $posicao = 0;
    public $ultima_coluna = "backlog";
    public $criado_por_id;
    public $criado_em;
    public $ultima_atualizacao;
    public $temp_id_chamado;
    public $atualizacoes;
    public $comentarios;
    public $arquivos;
    public $checklist;
    public $observadores = array();
    public $novos_observadores = array();

    public $comentario;
    public $mencoes;
    public $termo;

    protected $_tabela = array(
        'nome' => 'TBLChamados',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "titulo",
            "descricao",
            "proximo_retorno",
            "responsavel_id",
            "tipo_chamado",
            "modulo",
            "classificacao",
            "coluna",
            "posicao",
            "ultima_coluna",
            "criado_por_id",
            "criado_em",
            "ultima_atualizacao",
        ),
        'permissao' => '00072'
    );

    public function __construct() {}

    private function normalizarTextoOpcional($valor)
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private function normalizarTipoChamado($tipoChamado)
    {
        $tipoChamado = trim((string) $tipoChamado);
        if ($tipoChamado === '') {
            return null;
        }

        $tiposPermitidos = array('desenvolvimento', 'suporte');
        return in_array($tipoChamado, $tiposPermitidos, true) ? $tipoChamado : null;
    }

    private function normalizarClassificacao($classificacao)
    {
        $classificacao = trim((string) $classificacao);
        if ($classificacao === '') {
            return null;
        }

        $classificacoesPermitidas = array('correcao', 'melhoria', 'novo_modulo');
        $nova_classificacao =  in_array($classificacao, $classificacoesPermitidas, true) ? $classificacao : null;
        return $nova_classificacao;
    }

    public $legendaColunas = array(
        'backlog' => 'Em fila',
        'andamento' => 'Em andamento',
        'pausadas' => 'Pausados',
        'em_validacao' => 'Em validação',
        'concluidas' => 'Concluídos',
        'retorno' => 'Retorno',
        'arquivados' => 'Arquivados',
    );

    public $mapColunaCores = array(
        'backlog' => 'info',
        'andamento' => 'warning',
        'pausadas' => 'gray-dark',
        'em_validacao' => 'orange',
        'concluidas' => 'success',
        'retorno' => 'danger',
        'arquivados' => 'secondary',
    );

    private function obterRotuloModuloSuporte($modulo)
    {
        $rotulos = array(
            'suporte-para-maquinas' => 'Suporte para Maquinas',
            'suporte-para-programas-e-aplicativos' => 'Suporte para Programas e Aplicativos',
            'suporte-para-perifericos' => 'Suporte para Perifericos',
            'suporte-para-duvidas-no-geral' => 'Suporte para duvidas no geral',
            'liberacao-de-acesso-para-internos' => 'Liberacao de acesso para internos',
            'liberacao-de-acesso-para-externos' => 'Liberacao de acesso para externos',
        );

        $modulo = trim((string) $modulo);
        return $rotulos[$modulo] ?? $modulo;
    }

    private function criarHtmlModuloChamadoEmail($chamado)
    {
        $tipoChamado = trim((string) ($chamado['tipo_chamado'] ?? ''));
        $sistemaModulo = trim((string) ($chamado['modulo'] ?? ''));

        if ($sistemaModulo === '') {
            return '';
        }

        if ($tipoChamado === 'desenvolvimento') {
            $partes = explode('/', $sistemaModulo, 2);
            $sistema = htmlspecialchars($partes[0] ?? '');
            $modulo = htmlspecialchars($partes[1] ?? '');
            $html = '';

            if ($sistema !== '') {
                $html .= "<p><strong>Sistema:</strong> {$sistema}</p>";
            }

            if ($modulo !== '') {
                $html .= "<p><strong>Modulo:</strong> {$modulo}</p>";
            }

            return $html;
        }

        $moduloSuporte = htmlspecialchars($this->obterRotuloModuloSuporte($sistemaModulo));
        return $moduloSuporte !== '' ? "<p><strong>Modulo:</strong> {$moduloSuporte}</p>" : '';
    }

    public function criaHtmlEmailChamado($texto, $chamado)
    {
        $chamado = (array) $chamado;

        $titulo = htmlspecialchars($chamado['titulo'] ?? '');
        $descricao = nl2br(htmlspecialchars($chamado['descricao'] ?? ''));
        $proximoRetorno = !empty($chamado['proximo_retorno']) ? date('d/m/Y', strtotime($chamado['proximo_retorno'])) : 'Não definido';

        $moduloHtml = $this->criarHtmlModuloChamadoEmail($chamado);
        $arquivosHtml = '';
        if (!empty($chamado['arquivos'])) {
            $arquivosHtml .= '<ul>';
            foreach ($chamado['arquivos'] as $arquivo) {
                $arquivo = (array) $arquivo;
                $nome = htmlspecialchars($arquivo['nome'] ?? '');
                $arquivosHtml .= "<li>{$nome}</li>";
            }
            $arquivosHtml .= '</ul>';
        } else {
            $arquivosHtml = '<p>Nenhum arquivo anexado</p>';
        }

        $observadoresHtml = '';
        if (!empty($chamado['observadores'])) {
            $observadoresHtml .= '<ul>';
            foreach ($chamado['observadores'] as $obs) {
                $obs = (array) $obs;
                $nome = htmlspecialchars($obs['apresentacao'] ?? '');
                $observadoresHtml .= "<li>{$nome}</li>";
            }
            $observadoresHtml .= '</ul>';
        } else {
            $observadoresHtml = '<p>Nenhum observador</p>';
        }

        $texto = htmlspecialchars($texto);

        return "
            <div style='font-family: Arial, sans-serif; font-size: 14px; color: #333'>
                <p>{$texto}</p>

                <h3>Detalhes do Chamado</h3>

                <p><strong>Título:</strong> {$titulo}</p>
                <p><strong>Descrição:</strong><br>{$descricao}</p>
                <p><strong>Próximo retorno:</strong> {$proximoRetorno}</p>

                {$moduloHtml}

                <h4>Arquivos</h4>
                {$arquivosHtml}

                <h4>Observadores</h4>
                {$observadoresHtml}
            </div>";
    }

    private function souDeInformatica()
    {
        return ($_SESSION['setor'] ?? '') == 'Coordenadoria de Informática e Tecnologia';
    }


    public function criaNotificacoesProgramadas($tipo = '', $chamado = null, $notificacao = null)
    {
        switch ($tipo) {
            case "muda-proximo-retorno":
                $Usuarios = new Usuarios();
                $usuario_ti = $Usuarios->getUsuariosPorSetor(2);

                $destinatarios = $chamado['responsavel_id'] ? [$chamado['responsavel_id']] : $usuario_ti;
                $NovaNotificacao = new Notificacoes();
                $NovaNotificacao->titulo = "[CHAMADOS] Data de retorno para hoje.";
                $NovaNotificacao->texto = "O chamado T-" . ($chamado['id'] ?? '') . " possui uma data de retorno para hoje.";
                $NovaNotificacao->cor = "warning";
                $NovaNotificacao->botao_label = "Ver chamado";
                $NovaNotificacao->destinatarios = $destinatarios;
                $NovaNotificacao->botao_url = "/adminlte-painel/admin/content/chamados/index.php?id_chamado=" . ($chamado['id'] ?? '');
                $NovaNotificacao->envia_email = false;
                $NovaNotificacao->toast_total_exibicoes = 3;
                $NovaNotificacao->toast_intervalo_minutos = 30;
                $NovaNotificacao->envia_email = false;
                $NovaNotificacao->exibir_em_destaque = true;

            default:
                break;
        }

        if (!empty($NovaNotificacao->destinatarios)) {
            $NovaNotificacao = $NovaNotificacao->criaNotificacao();

            $NotificacaoProgramada = new NotificacoesProgramadas();
            $NotificacaoProgramada->esquema = '';
            $NotificacaoProgramada->tabela = 'TBLChamados';
            $NotificacaoProgramada->nome_coluna_pk = 'id';
            $NotificacaoProgramada->valor_pk = $chamado['id'] ?? null;
            $NotificacaoProgramada->descricao = $NovaNotificacao['titulo'];
            $NotificacaoProgramada->ativo = true;
            $NotificacaoProgramada->notificacao_criada = true;
            $NotificacaoProgramada->id_notificacao = $NovaNotificacao['id'];
            $NotificacaoProgramada->gerar_log_query = true;
            $NotificacaoProgramada->criado_em = (new DateTime())->format('Y-m-d H:i:s');

            $NotificacaoProgramada = $NotificacaoProgramada->incluir();

            $condicao = new NotificacoesProgramadasCondicoes();
            $condicao->nome_coluna = '';
            $condicao->operacao = "EXPLICITO";
            $condicao->valor_coluna = "proximo_retorno = CURDATE()";
            $condicao->id_notificacao_programada = $NotificacaoProgramada['id'];
            $condicao = $condicao->incluir();

            $condicao = new NotificacoesProgramadasCondicoes();
            $condicao->nome_coluna = 'coluna';
            $condicao->operacao = "IGUAL";
            $condicao->valor_coluna = "backlog";
            $condicao->id_notificacao_programada = $NotificacaoProgramada['id'];
            $condicao = $condicao->incluir();
        }
    }

    public function criaNotificacoes($tipo = '', $chamado = null)
    {
        $chamado = $chamado ?? $this;
        $chamado = (array) $chamado;

        if (empty($chamado['arquivos'])) {
            $chamado['arquivos'] = (new ChamadosArquivos())->getArquivos($chamado['id']);
        }

        if (empty($chamado['observadores'])) {
            $chamado['observadores'] = (new ChamadosObservadores())->getObsevadores($chamado['id']);
        }

        $Notificacao = new Notificacoes();

        switch ($tipo) {
            case "cria-chamado":
                $sou_de_informatica = $_SESSION['setor'] == 'Coordenadoria de Informática e Tecnologia';
                if ($sou_de_informatica) {
                    break;
                }
                $Usuarios = new Usuarios();
                $destinatarios = $Usuarios->getUsuariosPorSetor(2);
                $idsIgnorar = [
                    (int) ($chamado['responsavel_id'] ?? 0),
                    (int) ($chamado['criado_por_id'] ?? 0),
                    (int) ID_USER
                ];

                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] Novo chamado criado.";
                $Notificacao->texto = "Um novo chamado foi criado (T-" . ($chamado['id'] ?? '') . "). Clique para ver.";
                $Notificacao->html_email = $this->criaHtmlEmailChamado(
                    "Um novo chamado foi criado (T-" . ($chamado['id'] ?? '') . "). Clique para ver.",
                    $chamado
                );
                $Notificacao->cor = "info";
                $Notificacao->botao_label = "Ver chamado";
                $Notificacao->botao_url = "/adminlte-painel/admin/content/chamados/index.php?id_chamado=" . ($chamado['id'] ?? '');
                $Notificacao->envia_email = true;
                break;

            case "novo-observador":
                $Usuarios = new Usuarios();
                $destinatarios = $chamado['novos_observadores'];

                $idsIgnorar = [
                    (int) ($chamado['responsavel_id'] ?? 0),
                    (int) ($chamado['criado_por_id'] ?? 0),
                    (int) ID_USER
                ];

                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);
                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] Você foi adicionado com observador a um novo chamado.";
                $Notificacao->texto = "Agora você pode visualizar um novo chamado (T-" . ($chamado['id'] ?? '') . "). Clique para ver.";
                $Notificacao->html_email = $this->criaHtmlEmailChamado(
                    "Agora você pode visualizar um novo chamado (T-" . ($chamado['id'] ?? '') . "). Clique para ver.",
                    $chamado
                );
                $Notificacao->cor = "info";
                $Notificacao->botao_label = "Ver chamado";
                $Notificacao->botao_url = "/adminlte-painel/admin/content/chamados/index.php?id_chamado=" . ($chamado['id'] ?? '');
                $Notificacao->envia_email = true;
                break;

            case "notifica-responsavel":
                if (empty($chamado['responsavel_id'])) {
                    break;
                }

                if ($chamado['responsavel_id'] != ID_USER) {
                    $Notificacao->destinatarios = [$chamado['responsavel_id']];
                    $Notificacao->titulo = "[CHAMADOS] Um novo chamado foi associado à você.";
                    $Notificacao->texto = "Agora você é o responsável pelo chamado T-" . ($chamado['id'] ?? '') . ". Clique para ver.";
                    $Notificacao->html_email = $this->criaHtmlEmailChamado(
                        "Um novo chamado foi associado à você (T-" . ($chamado['id'] ?? '') . ").",
                        $chamado
                    );
                    $Notificacao->cor = "info";
                }
                break;

            case "mover-coluna":
                $nome = $_SESSION['nome'];
                $nova_coluna = $chamado['legendaColunas'][$chamado['coluna']] ?? '';
                $cor = $chamado['mapColunaCores'][$chamado['coluna']] ?? 'info';

                $observadores = (new ChamadosObservadores())->getObsevadores($chamado['id']);
                $destinatarios = [];

                foreach ($observadores as $observador) {
                    $destinatarios[] = (array) $observador;
                }

                $idsIgnorar = [
                    (int) ID_USER
                ];

                $destinatarios[] = $chamado['responsavel_id'] ?? null;
                $destinatarios[] = $chamado['criado_por_id'] ?? null;
                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] O chamado T-" . ($chamado['id'] ?? '') . " foi atualizado.";
                $Notificacao->texto = "$nome moveu o chamado T-" . ($chamado['id'] ?? '') . " para a coluna '$nova_coluna'.";
                $Notificacao->html_email = $this->criaHtmlEmailChamado(
                    $Notificacao->texto,
                    $chamado
                );
                $Notificacao->cor = $cor;

                break;

            case "muda-proximo-retorno":
                $nome = $_SESSION['nome'];

                $observadores = (new ChamadosObservadores())->getObsevadores($chamado['id']);
                $destinatarios = [];

                foreach ($observadores as $observador) {
                    $destinatarios[] = (array) $observador;
                }

                $idsIgnorar = [
                    (int) ID_USER
                ];

                $destinatarios[] = $chamado['responsavel_id'] ?? null;
                $destinatarios[] = $chamado['criado_por_id'] ?? null;
                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] O chamado T-" . ($chamado['id'] ?? '') . " possui uma nova data de retorno.";
                $Notificacao->texto = "$nome definiu a data de " . ($chamado['proximo_retorno'] ?? '') . " para trazer atualizações sobre a tarefa " . '"' . $chamado['titulo'] . '"';
                $Notificacao->cor = "info";
                break;

            case "criar-comentario":
                $nome = $_SESSION['nome'];

                $observadores = (new ChamadosObservadores())->getObsevadores($chamado['id']);
                $destinatarios = [];

                foreach ($observadores as $observador) {
                    $destinatarios[] = (array) $observador;
                }

                $idsIgnorar = [
                    (int) ID_USER
                ];
                $mencoes = $chamado['mencoes'] ?? [];
                foreach ($mencoes as $mencao) {
                    $idsIgnorar[] = $mencao['id'];
                }

                $destinatarios[] = $chamado['responsavel_id'] ?? null;
                $destinatarios[] = $chamado['criado_por_id'] ?? null;
                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] " .  $_SESSION['nome']  . " fez um comentário no chamado T-" . $chamado['id'];
                $Notificacao->texto =  $_SESSION['nome'] . " comentou no chamado " . '"' . $chamado['titulo'] .  '"' . ": " . '"' . $chamado['comentario'] .  '"';
                $Notificacao->cor = "info";

                break;
            case "mencoes":
                $nome = $_SESSION['nome'];

                $mencoes = $chamado['mencoes'] ?? [];
                $destinatarios = [];

                foreach ($mencoes as $mencao) {
                    $destinatarios[] = (array) $mencao;
                }

                $idsIgnorar = [
                    (int) ID_USER
                ];

                $destinatarios[] = $chamado['responsavel_id'] ?? null;
                $destinatarios[] = $chamado['criado_por_id'] ?? null;
                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo = "[CHAMADOS] " . $_SESSION['nome']  . " mencionou você em um comentário no chamado T-" . $chamado['id'];
                $Notificacao->texto =  "Você foi mencionado em um comentário no chamado " . '"' . $chamado['titulo'] .  '"' . ": " . '"' . $chamado['comentario'] .  '"';
                $Notificacao->cor = "info";

                break;

            case "criar-atualizacao":
                $nome = $_SESSION['nome'];

                $observadores = (new ChamadosObservadores())->getObsevadores($chamado['id']);
                $destinatarios = [];

                foreach ($observadores as $observador) {
                    $destinatarios[] = (array) $observador;
                }

                $idsIgnorar = [
                    (int) ID_USER
                ];

                $destinatarios[] = $chamado['responsavel_id'] ?? null;
                $destinatarios[] = $chamado['criado_por_id'] ?? null;
                $destinatarios = $this->filtrarDestinatarios($destinatarios, $idsIgnorar);

                $Notificacao->destinatarios = $destinatarios;
                $Notificacao->titulo ="[CHAMADOS] " .  $_SESSION['nome']  . " criou uma atualização no chamado T-" . $chamado['id'];
                $Notificacao->texto =  $_SESSION['nome'] . " criou uma atualização no chamado " . '"' . $chamado['titulo'] .  '"' . ": " . '"' . $chamado['atualizacao'] .  '"';
                $Notificacao->cor = "info";

                break;
        }

        $Notificacao->botao_label = "Ver chamado";
        $Notificacao->botao_url = "/adminlte-painel/admin/content/chamados/index.php?id_chamado=" . ($chamado['id'] ?? '');
        $Notificacao->envia_email = true;

        if (!empty($Notificacao->destinatarios)) {
            $Notificacao = $Notificacao->criaNotificacao();

            $this->criaNotificacoesProgramadas($tipo, $chamado, $Notificacao);
        }
    }

    private function aplicarFiltroVisualizacaoChamados($alias = 'c')
    {
        if ($this->souDeInformatica()) {
            return;
        }

        $id_usuario = (int) ID_USER;
        $this->filtrar(
            'visualizacao_chamados',
            "(
                $alias.criado_por_id = $id_usuario
                OR $alias.responsavel_id = $id_usuario
                OR EXISTS (
                    SELECT 1
                    FROM TBLChamados_Observadores o
                    WHERE o.id_chamado = $alias.id
                    AND o.id_usuario = $id_usuario
                )
            )",
            'EXPLICITO'
        );
    }

    public function criaChamado()
    {
        $chamado = $this;
        $chamado->criado_por_id = ID_USER;
        $chamado->responsavel_id = $this->souDeInformatica() ? $this->responsavel_id : null   ;
        $chamado->criado_em = (new DateTime())->format('Y-m-d H:i:s');
        $chamado->arquivado = $chamado->arquivado == "on" ? 1 : 0;

        $atributos_editaveis = $this->getAtributosEditaveis();

        if (!$atributos_editaveis['tipo_chamado']) {
            $chamado->tipo_chamado = null;
        }
        if (!$atributos_editaveis['modulo']) {
            $chamado->modulo = null;
        }
        if (!$atributos_editaveis['classificacao']) {
            $chamado->classificacao = null;
        }
        if (!$atributos_editaveis['proximo_retorno']) {
            $chamado->proximo_retorno = null;
        }
        if (!$atributos_editaveis['responsavel_id']) {
            $chamado->responsavel_id = null;
        }
        if (!$atributos_editaveis['arquivado']) {
            $chamado->arquivado = null;
        }
        if (!$atributos_editaveis['criar_em_qualquer_coluna']) {
            $chamado->coluna = "backlog";
        }

        $chamado->tipo_chamado = $this->normalizarTipoChamado($chamado->tipo_chamado ?? null);
        $chamado->modulo = $this->normalizarTextoOpcional($chamado->modulo ?? null);
        $chamado->classificacao = $this->normalizarClassificacao($chamado->classificacao ?? null);

        if (empty($chamado->titulo)) {
            throw new Exception("Informe o título para salvar o chamado!");
        }

        if (empty($chamado->tipo_chamado)) {
            throw new Exception("Informe o tipo de chamado antes de salvar!");
        }

        if (empty($chamado->modulo)) {
            if ($chamado->tipo_chamado == "suporte") {
                throw new Exception("Informe o tipo de suporte antes de salvar!");
            } else {
                throw new Exception("Informe o módulo antes de salvar!");
            }
        }

        if ($chamado->tipo_chamado == "desenvolvimento") {
            if (empty($chamado->classificacao)) {
                throw new Exception("Informe a classificação do chamado antes de salvar!");
            }
        }

        $incluir = $chamado->incluir();
        $chamado = $incluir;
        $arquivos = (new ChamadosArquivos())->vincularArquivosTemporariosAoChamado($chamado['id'], $chamado['temp_id_chamado']);
        if (!$atributos_editaveis['observadores']) {
            $Usuarios = new Usuarios();
            $Usuarios->retornarComoArray = true;
            $Usuarios::habilitarIgnorarPermissao();
            $colegas_de_setor = $Usuarios->getColegasDeSetor(ID_USER);
            $Usuarios::desabilitarIgnorarPermissao();
            foreach ($colegas_de_setor as $colega) {
                $observadores_ids[] = (int)$colega['id'];
            }
        } else {
            $observadores = array();
            $observadores_ids = is_array($chamado['observadores']) ? $chamado['observadores'] : array();
            $observadores_ids = array_values(array_unique(array_filter(array_map('intval', $observadores_ids))));
        }

        foreach ($observadores_ids as $id_observador) {
            $observador = new ChamadosObservadores();
            $observador->id_chamado = $chamado['id'];
            $observador->id_usuario = $id_observador;
            $observador->criado_em = (new DateTime())->format('Y-m-d H:i:s');
            $observadores[] = $observador->incluir();
        }

        $chamado['arquivos'] = $arquivos;
        $chamado['observadores'] = (new ChamadosObservadores())->getObsevadores($chamado['id']);
        $chamado['checklist'] = array();

        if (!empty($this->checklist) && is_array($this->checklist)) {
            foreach ($this->checklist as $indice => $itemChecklist) {
                $textoChecklist = '';
                if (is_array($itemChecklist)) {
                    $textoChecklist = trim((string) ($itemChecklist['texto'] ?? ''));
                } elseif (is_object($itemChecklist)) {
                    $textoChecklist = trim((string) ($itemChecklist->texto ?? ''));
                }

                if ($textoChecklist === '') {
                    continue;
                }

                $concluidoChecklist = 0;
                $ordemChecklist = $indice + 1;
                if (is_array($itemChecklist)) {
                    $concluidoChecklist = $itemChecklist['concluido'] ?? 0;
                    $ordemChecklist = $itemChecklist['ordem'] ?? ($indice + 1);
                } elseif (is_object($itemChecklist)) {
                    $concluidoChecklist = $itemChecklist->concluido ?? 0;
                    $ordemChecklist = $itemChecklist->ordem ?? ($indice + 1);
                }
                $agoraChecklist = (new DateTime())->format('Y-m-d H:i:s');

                $checklistItem = new ChamadosChecklist();
                $checklistItem->id_chamado = $chamado['id'];
                $checklistItem->texto = $textoChecklist;
                $checklistItem->concluido = !empty($concluidoChecklist) ? 1 : 0;
                $checklistItem->ordem = (int) $ordemChecklist > 0 ? (int) $ordemChecklist : ($indice + 1);
                $checklistItem->criado_por_id = ID_USER;
                $checklistItem->criado_em = $agoraChecklist;
                $checklistItem->atualizado_em = $agoraChecklist;
                $checklistItem->criaChecklistItem();
            }

            $chamado['checklist'] = (new ChamadosChecklist())->getChecklistPorChamado($chamado['id']);
        }

        $this->criaNotificacoes('cria-chamado', $chamado);
        if ($chamado['responsavel_id']) {
            $this->criaNotificacoes('notifica-responsavel', $chamado);
        }
        return $chamado;
    }

    public function editaChamado()
    {
        $chamado = $this;
        $atributos_editaveis = $this->getAtributosEditaveis();

        $chamado_existente = $chamado->instanciarPorId($chamado->id);
        if (empty($chamado_existente)) {
            return null;
        }

        $antigo_responsavel_id = $chamado_existente->responsavel_id;
        $antigo_proximo_retorno = $chamado_existente->proximo_retorno;

        $chamado_existente->titulo = (
            $atributos_editaveis['titulo'] && !empty($chamado->titulo)
        ) ? $chamado->titulo : $chamado_existente->titulo;

        $chamado_existente->descricao = (
            $atributos_editaveis['descricao'] && !empty($chamado->descricao)
        ) ? $chamado->descricao : $chamado_existente->descricao;

        $chamado_existente->tipo_chamado = $atributos_editaveis['tipo_chamado']
            ? $chamado->tipo_chamado
            : $chamado_existente->tipo_chamado;

        $chamado_existente->modulo = $atributos_editaveis['modulo']
            ? $chamado->modulo
            : $chamado_existente->modulo;

        $chamado_existente->classificacao = $atributos_editaveis['classificacao']
            ? $chamado->classificacao
            : $chamado_existente->classificacao;

        $chamado_existente->proximo_retorno = (
            $atributos_editaveis['proximo_retorno'] && !empty($chamado->proximo_retorno)
        ) ? $chamado->proximo_retorno : $chamado_existente->proximo_retorno;

        $chamado_existente->responsavel_id = (
            $atributos_editaveis['responsavel_id'] && !empty($chamado->responsavel_id)
        ) ? $chamado->responsavel_id : $chamado_existente->responsavel_id;

        $chamado_existente->arquivado = $atributos_editaveis['arquivado']
            ? ($chamado->arquivado == "on" ? 1 : 0)
            : $chamado_existente->arquivado;

        if ($chamado_existente->arquivado) {
            $chamado_existente->coluna = 'arquivados';
        } else {
            $chamado_existente->coluna = $atributos_editaveis['criar_em_qualquer_coluna']
                ? $chamado->coluna
                : $chamado_existente->coluna;
        }

        $chamado_existente->tipo_chamado = $this->normalizarTipoChamado($chamado_existente->tipo_chamado ?? null);
        $chamado_existente->modulo = $this->normalizarTextoOpcional($chamado_existente->modulo ?? null);
        $chamado_existente->classificacao = $this->normalizarClassificacao($chamado_existente->classificacao ?? null);

        if ($chamado_existente->responsavel_id != $antigo_responsavel_id) {
            $atualizacao = new ChamadosAtualizacoes();
            $atualizacao->id_chamado = $chamado->id;
            $nome = $_SESSION['nome'];
            if (ID_USER == $chamado_existente->responsavel_id) {
                $atualizacao->descricao = "$nome assumiu esta tarefa";
            } else {
                $Usuarios = new Usuarios();
                $Usuarios::habilitarIgnorarPermissao();
                $atualizacao->descricao = "$nome mudou o responsável deste chamado para " . $Usuarios->instanciarPorId($chamado_existente->responsavel_id)->apresentacao;
                $Usuarios::desabilitarIgnorarPermissao();
            }
            $atualizacao->criado_por_id = ID_USER;
            $atualizacao->criado_em = (new DateTime())->format('Y-m-d H:i:s');
            $atualizacao->incluir();
            $this->criaNotificacoes('notifica-responsavel');
        }

        if ($chamado_existente->proximo_retorno != $antigo_proximo_retorno) {
            $atualizacao = new ChamadosAtualizacoes();
            $atualizacao->id_chamado = $chamado->id;
            $nome = $_SESSION['nome'];

            $Usuarios = new Usuarios();
            $Usuarios::habilitarIgnorarPermissao();
            $atualizacao->descricao = "$nome definiu a data de $this->proximo_retorno para trazer atualizações sobre a tarefa.";
            $Usuarios::desabilitarIgnorarPermissao();

            $atualizacao->criado_por_id = ID_USER;
            $atualizacao->criado_em = (new DateTime())->format('Y-m-d H:i:s');
            $atualizacao->incluir();
            $this->criaNotificacoes('muda-proximo-retorno');
        }


        if (empty($chamado_existente->titulo)) {
            throw new Exception("Informe o título para salvar o chamado!");
        }

        if (empty($chamado_existente->tipo_chamado)) {
            throw new Exception("Informe o tipo de chamado antes de salvar!");
        }

        if (empty($chamado_existente->modulo)) {
            if ($chamado_existente->tipo_chamado == "suporte") {
                throw new Exception("Informe o tipo de suporte antes de salvar!");
            } else {
                throw new Exception("Informe o módulo antes de salvar!");
            }
        }

        if ($chamado_existente->tipo_chamado == "desenvolvimento") {
            if (empty($chamado_existente->classificacao)) {
                throw new Exception("Informe a classificação do chamado antes de salvar!");
            }
        }


        $salvar = $chamado_existente->salvar();

        $observadores = new ChamadosObservadores();
        $observadores_existentes = $observadores->getObsevadores($chamado->id);
        $observadores_editar = $this->observadores ?? array();

        if ($atributos_editaveis['observadores']) {
            $observadores_existentes_ids = array_map(function ($obs) {
                return $obs->id_usuario;
            }, $observadores_existentes);

            $observadores_editar_ids = array_map(function ($obs) {
                $teste = $obs;
                return $obs['id_usuario'];
            }, $observadores_editar);

            $para_excluir = array_diff($observadores_existentes_ids, $observadores_editar_ids);
            $para_salvar = array_diff($observadores_editar_ids, $observadores_existentes_ids);

            foreach ($para_excluir as $id_observador) {
                $observador = new ChamadosObservadores();
                $observador = $observador->getObsevador($chamado->id, $id_observador);
                if (!empty($observador)) {
                    $excluir = $observador->excluir();
                }
            }

            foreach ($para_salvar as $id_observador) {
                $observador = new ChamadosObservadores();
                $observador->id_chamado = $chamado->id;
                $observador->id_usuario = $id_observador;
                $observador->criado_em = (new DateTime())->format('Y-m-d H:i:s');
                $observador->incluir();
            }

            $chamado_existente->novos_observadores = $para_salvar;
            $this->criaNotificacoes('novo-observador', $chamado_existente);
        }

        return $chamado_existente;
    }

    public function getChamados()
    {
        $this->queryCorrente = "SELECT c.*, ur.apresentacao as responsavel, uc.apresentacao as criado_por FROM TBLChamados c 
        LEFT JOIN TBLUsuarios ur ON c.responsavel_id = ur.id 
        LEFT JOIN TBLUsuarios uc ON c.criado_por_id = uc.id 
        WHERE 1=1    ";
        $this->aplicarFiltroVisualizacaoChamados('c');
        $this->retornarComoArray = true;
        $this->ordenar("c.posicao", "ASC");
        $this->ordenar("c.ultima_atualizacao", "DESC");

        $result = $this->buscar();
        foreach ($result as &$chamado) {
            $chamado['observadores'] = (new ChamadosObservadores())->getObsevadores($chamado['id']);
            $chamado['atualizacoes'] = (new ChamadosAtualizacoes())->getAtualizacoes($chamado['id']);
            $chamado['comentarios'] = (new ChamadosComentarios())->getComentarios($chamado['id']);
            $chamado['arquivos'] = (new ChamadosArquivos())->getArquivos($chamado['id']);
            $chamado['checklist'] = (new ChamadosChecklist())->getChecklistPorChamado($chamado['id']);
        }
        // $observadores = new ChamadosObservadores();
        return $result;
    }

    public function getChamado($id)
    {
        $id = $id ?? $this->id;
        $this->queryCorrente = "SELECT c.*, ur.apresentacao as responsavel, uc.apresentacao as criado_por FROM TBLChamados c 
        LEFT JOIN TBLUsuarios ur ON c.responsavel_id = ur.id 
        LEFT JOIN TBLUsuarios uc ON c.criado_por_id = uc.id 
        WHERE 1=1    ";
        $this->aplicarFiltroVisualizacaoChamados('c');
        $this->filtrar("c.id", $id);
        $this->retornarComoArray = true;

        $result = $this->buscar();
        foreach ($result as &$chamado) {
            $chamado['observadores'] = (new ChamadosObservadores())->getObsevadores($chamado['id']);
            $chamado['atualizacoes'] = (new ChamadosAtualizacoes())->getAtualizacoes($chamado['id']);
            $chamado['comentarios'] = (new ChamadosComentarios())->getComentarios($chamado['id']);
            $chamado['arquivos'] = (new ChamadosArquivos())->getArquivos($chamado['id']);
            $chamado['checklist'] = (new ChamadosChecklist())->getChecklistPorChamado($chamado['id']);
            $chamado['atributos_editaveis'] = $this->getAtributosEditaveis($chamado['id']);
        }
        // $observadores = new ChamadosObservadores();
        return $result[0] ?? null;
    }

    public function getAtributosEditaveis($id = null)
    {
        $id = $id ?? $this->id;
        $chamado = null;
        $sou_criador = false;
        $sou_responsavel = false;

        if (!empty($id)) {
            $chamado = $this->instanciarPorId($id);
            if (empty($chamado)) {
                return false;
            }
            $sou_criador = $chamado->criado_por_id == ID_USER;
            $sou_responsavel = $chamado->responsavel_id == ID_USER;
        }

        $sou_de_informatica = $_SESSION['setor'] == 'Coordenadoria de Informática e Tecnologia';
        $id_usuario = ID_USER;
        $sou_observador = !empty((new ChamadosObservadores())->getObsevador($id, $id_usuario));

        $atributos = array(
            'titulo' => $sou_de_informatica || empty($id),
            'tipo_chamado' => $sou_de_informatica || empty($id),
            'modulo' => $sou_de_informatica || empty($id),
            'classificacao' => $sou_de_informatica || empty($id),
            'descricao' => $sou_de_informatica || empty($id),
            'observadores' => $sou_criador || $sou_de_informatica || empty($id),
            'responsavel_id' => $sou_de_informatica,
            'proximo_retorno' => $sou_de_informatica,
            'arquivado' => $sou_de_informatica,
            'criar_em_qualquer_coluna' => $sou_de_informatica,
            'criar_atualizacoes_por_texto' => $sou_responsavel || $sou_de_informatica,
            'gerenciar_checklist' => $sou_de_informatica || $sou_responsavel || $sou_criador,
            'criar_comentarios' => $sou_criador || $sou_de_informatica || $sou_observador,
            'anexar_arquivos' => $sou_criador || $sou_de_informatica || $sou_observador,
        );

        if (!empty($chamado) && ((int) $chamado->arquivado === 1 || $chamado->coluna === 'arquivados')) {
            $atributos['titulo'] = false;
            $atributos['tipo_chamado'] = false;
            $atributos['modulo'] = false;
            $atributos['classificacao'] = false;
            $atributos['descricao'] = false;
            $atributos['observadores'] = false;
            $atributos['responsavel_id'] = false;
            $atributos['proximo_retorno'] = false;
            $atributos['criar_atualizacoes_por_texto'] = false;
            $atributos['gerenciar_checklist'] = false;
            $atributos['criar_comentarios'] = false;
            $atributos['anexar_arquivos'] = false;
        }

        return $atributos;
    }

    public function getObservadoresPadrao()
    {
        $Usuarios = new Usuarios();
        $Usuarios::habilitarIgnorarPermissao();
        $colegas_de_setor = $Usuarios->getColegasDeSetor(ID_USER);
        $Usuarios::desabilitarIgnorarPermissao();

        return $colegas_de_setor;
    }

    public function moverChamado()
    {
        $atributos_editaveis = $this->getAtributosEditaveis($this->id);

        if (!$atributos_editaveis['criar_em_qualquer_coluna']) {
            return null;
        }

        $chamado_existente = $this->instanciarPorId($this->id);
        if (empty($chamado_existente)) {
            return null;
        }

        $coluna_anterior = $chamado_existente->coluna;

        if ($this->coluna != $coluna_anterior) {
            $atualizacao = new ChamadosAtualizacoes();
            $atualizacao->id_chamado = $this->id;
            $nome = $_SESSION['nome'];
            $nova_coluna = $this->legendaColunas[$this->coluna] ?? '';
            $atualizacao->descricao = "$nome moveu este cartão para \"$nova_coluna\";";
            $atualizacao->criado_por_id = ID_USER;
            $atualizacao->criado_em = (new DateTime())->format('Y-m-d H:i:s');
            $atualizacao->incluir();
        }

        $chamado_existente->coluna = $this->coluna;
        $chamado_existente->ultima_coluna = $this->ultima_coluna;
        $chamado_existente->posicao = $this->posicao;
        $chamado_existente->ultima_atualizacao = (new DateTime())->format('Y-m-d H:i:s');

        if ($this->coluna != $coluna_anterior) {
            $this->criaNotificacoes('mover-coluna', $chamado_existente);
        }

        $salvar = $chamado_existente->salvar();
        return $salvar;
    }

    public function pesquisarUsuariosObservadores()
    {
        $sou_de_informatica = $_SESSION['setor'] == 'Coordenadoria de Informática e Tecnologia';
        $result = [];
        $Usuarios = new Usuarios();
        if ($sou_de_informatica) {
            $Usuarios->termo = $this->termo;
            $result = $Usuarios->getUsuarioPorString();
        } else {
            $result = $Usuarios->getColegasdeSetor(ID_USER);
        }

        return $result;
    }
}
