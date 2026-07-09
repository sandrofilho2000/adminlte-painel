<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;
use Exception;

class Portais extends ClasseBase
{
    public $id;
    public $estado_conselho;
    public $dt_inclusao;
    public $ativo;
    public $logo_portal;
    public $logo;

    protected $_tabela = array(
        'nome' => 'TBLPortais',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "estado_conselho",
            "dt_inclusao",
            "logo",
            "ativo",
        ),
        'permissao' => '00014'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function criaPortalCref()
    {
        $this->estado_conselho = strtoupper(trim((string) ($this->estado_conselho ?? '')));
        $this->ativo = (int) ($this->ativo ?? 0) === 1 ? 1 : 0;
        $this->dt_inclusao = trim((string) ($this->dt_inclusao ?? ''));

        if ($this->estado_conselho === '') {
            throw new Exception("Informe o estado conselho.");
        }

        if ($this->dt_inclusao === '') {
            $this->dt_inclusao = null;
        } else {
            $this->dt_inclusao = str_replace('T', ' ', $this->dt_inclusao);
        }

        if (!empty($this->id)) {
            $portal = self::instanciarPorId((int) $this->id);

            if (empty($portal)) {
                throw new Exception("Portal nao encontrado.");
            }

            $portal->estado_conselho = $this->estado_conselho;
            $portal->dt_inclusao = $this->dt_inclusao;
            $portal->ativo = $this->ativo;

            return $portal->salvar();
        }

        $armazenamentoImagens = new ArmazenamentoArquivoDisco(
            raiz: null,
            extensoesPermitidas: ['jpg', 'jpeg', 'png', 'webp'],
            tamanhoMaximoBytes: 15 * 1024 * 1024,
            rotinaPermissao: '00014',
            mimesPermitidos: [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp']
            ]
        );

        try {
            $capa = $armazenamentoImagens->salvarUpload(
                $_FILES['logo_portal'],
                'portais/logos/',
                [
                    'operacao' => 'Incluir',
                    'prefixo' => 'logo'
                ]
            );

            $this->logo = '/webconfef_storage/' . ltrim(str_replace('\\', '/', $capa['caminho_relativo']), '/');
            $this->dt_inclusao = date("Y-m-d");
            $resultado = $this->incluir();
            $resultado['tipo'] = 'success';
            $resultado['message'] = 'Portal salvo com sucesso.';
            return $resultado;
        } catch (\Throwable $excecao) {
            throw $excecao;
        }
    }

    public function removerFundoLogoPortal()
    {
        $upload = $_FILES['logo_portal'] ?? null;
        $erroUpload = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        $caminhoTemporario = is_array($upload) ? (string) ($upload['tmp_name'] ?? '') : '';

        if ($erroUpload !== UPLOAD_ERR_OK || $caminhoTemporario === '' || !is_file($caminhoTemporario)) {
            throw new Exception("Falha ao receber a imagem.");
        }

        $tamanhoMaximo = 10 * 1024 * 1024;
        $tamanhoArquivo = (int) ($upload['size'] ?? 0);

        if ($tamanhoArquivo <= 0 || $tamanhoArquivo > $tamanhoMaximo) {
            throw new Exception("A imagem deve ter no maximo 10MB.");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $tipoImagem = (string) $finfo->file($caminhoTemporario);
        $tiposPermitidos = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($tipoImagem, $tiposPermitidos, true)) {
            throw new Exception("Formato de imagem nao permitido.");
        }

        $pastaTemporaria = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $entrada = tempnam($pastaTemporaria, 'logo_entrada_');
        $saidaBase = tempnam($pastaTemporaria, 'logo_saida_');

        if ($entrada === false || $saidaBase === false) {
            throw new Exception("Nao foi possivel preparar os arquivos temporarios.");
        }

        $saida = $saidaBase . '.png';

        try {
            if (!copy($caminhoTemporario, $entrada)) {
                throw new Exception("Nao foi possivel preparar a imagem enviada.");
            }

            $this->executarRemovedorFundoPython($entrada, $saida);

            if (!is_file($saida) || filesize($saida) <= 0) {
                throw new Exception("Nao foi possivel gerar a imagem sem fundo.");
            }

            return [
                'tipo' => 'success',
                'message' => 'Fundo removido com sucesso.',
                'imagem_base64' => base64_encode((string) file_get_contents($saida)),
                'tipo_imagem' => 'image/png',
                'nome_arquivo' => 'logo_sem_fundo.png',
            ];
        } finally {
            @unlink($entrada);
            @unlink($saidaBase);
            @unlink($saida);
        }
    }

    private function executarRemovedorFundoPython($entrada, $saida)
    {
        $script = BASE_PATH . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'remover_fundo.py';

        if (!is_file($script)) {
            throw new Exception("Script de remocao de fundo nao encontrado.");
        }

        $python = $this->obterExecutavelPython();
        $comando = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($entrada) . ' ' . escapeshellarg($saida);
        $descritores = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $processo = proc_open($comando, $descritores, $pipes, BASE_PATH);

        if (!is_resource($processo)) {
            throw new Exception("Nao foi possivel iniciar o processo Python.");
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $tempoLimite = max(10, (int) env('REMOVER_FUNDO_TIMEOUT', 60));
        $inicio = time();
        $saidaPadrao = '';
        $saidaErro = '';
        $tempoEsgotado = false;

        do {
            $status = proc_get_status($processo);
            $saidaPadrao .= stream_get_contents($pipes[1]);
            $saidaErro .= stream_get_contents($pipes[2]);

            if (!$status['running']) {
                break;
            }

            if ((time() - $inicio) >= $tempoLimite) {
                proc_terminate($processo);
                $tempoEsgotado = true;
                break;
            }

            usleep(100000);
        } while (true);

        $saidaPadrao .= stream_get_contents($pipes[1]);
        $saidaErro .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $codigoRetorno = proc_close($processo);

        if ($tempoEsgotado) {
            throw new Exception("A remocao de fundo demorou mais que {$tempoLimite} segundos. Tente uma imagem menor ou tente novamente.");
        }

        if ($codigoRetorno !== 0) {
            $mensagem = trim($saidaErro ?: $saidaPadrao);
            throw new Exception($mensagem !== '' ? $mensagem : "Nao foi possivel remover o fundo da imagem.");
        }
    }

    private function obterExecutavelPython()
    {
        $pythonEnv = trim((string) env('PYTHON_EXECUTAVEL', ''));

        if ($pythonEnv !== '') {
            return $pythonEnv;
        }

        $pythonWindows = BASE_PATH . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        $pythonLinux = BASE_PATH . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';

        if (is_file($pythonWindows)) {
            return $pythonWindows;
        }

        if (is_file($pythonLinux)) {
            return $pythonLinux;
        }

        return DIRECTORY_SEPARATOR === '\\' ? 'python' : 'python3';
    }

    public function getPortais()
    {
        $this->queryCorrente = "SELECT * FROM portal.TBLPortais WHERE 1=1 ";
        return $this->buscar();
    }
}
