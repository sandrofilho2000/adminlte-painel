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
    public $cnpj;
    public $endereco;
    public $numero;
    public $complemento;
    public $bairro;
    public $cidade;
    public $estado;
    public $cep;
    public $email;
    public $telefone;
    public $transparencia;
    public $facebook;
    public $instagram;
    public $linkedin;
    public $youtube;
    public $spotify;
    public $twitter;

    protected $_tabela = array(
        'nome' => 'TBLPortais',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "estado_conselho",
            "dt_inclusao",
            "logo",
            "cnpj",
            "endereco",
            "numero",
            "complemento",
            "bairro",
            "cidade",
            "estado",
            "cep",
            "email",
            "telefone",
            "transparencia",
            "facebook",
            "instagram",
            "linkedin",
            "youtube",
            "spotify",
            "twitter",
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
        $this->normalizarDadosCadastrais();

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
                throw new Exception("Portal não encontrado.");
            }

            $portal->estado_conselho = $this->estado_conselho;
            $portal->dt_inclusao = $this->dt_inclusao;
            $portal->ativo = $this->ativo;
            $portal->cnpj = $this->cnpj;
            $portal->endereco = $this->endereco;
            $portal->numero = $this->numero;
            $portal->complemento = $this->complemento;
            $portal->bairro = $this->bairro;
            $portal->cidade = $this->cidade;
            $portal->estado = $this->estado;
            $portal->cep = $this->cep;
            $portal->email = $this->email;
            $portal->telefone = $this->telefone;
            $portal->transparencia = $this->transparencia;
            $portal->facebook = $this->facebook;
            $portal->instagram = $this->instagram;
            $portal->linkedin = $this->linkedin;
            $portal->youtube = $this->youtube;
            $portal->spotify = $this->spotify;
            $portal->twitter = $this->twitter;

            if ($this->possuiUploadLogoPortal()) {
                $portal->logo = $this->salvarLogoPortal('Alterar');
            }

            $resultado = $portal->salvar();
            $resultado['tipo'] = 'success';
            $resultado['message'] = 'Portal atualizado com sucesso.';

            return $resultado;
        }

        $this->logo = $this->salvarLogoPortal('Incluir');
        $this->dt_inclusao = date("Y-m-d");
        $resultado = $this->incluir();
        $resultado['tipo'] = 'success';
        $resultado['message'] = 'Portal salvo com sucesso.';

        return $resultado;
    }

    private function possuiUploadLogoPortal(): bool
    {
        $upload = $_FILES['logo_portal'] ?? null;

        return is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function salvarLogoPortal(string $operacao): string
    {
        $upload = $_FILES['logo_portal'] ?? null;

        if (!is_array($upload)) {
            throw new Exception('Selecione a logo do portal.');
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

        $logo = $armazenamentoImagens->salvarUpload(
            $upload,
            'portais/logos/',
            [
                'operacao' => $operacao,
                'prefixo' => 'logo'
            ]
        );

        return '/webconfef_storage/' . ltrim(str_replace('\\', '/', $logo['caminho_relativo']), '/');
    }

    private function normalizarDadosCadastrais(): void
    {
        $this->cnpj = $this->normalizarCnpj($this->cnpj);
        $this->endereco = $this->normalizarTextoObrigatorio($this->endereco, 'endereço', 255);
        $this->numero = $this->normalizarTextoOpcional($this->numero, 20);
        $this->complemento = $this->normalizarTextoOpcional($this->complemento, 100);
        $this->bairro = $this->normalizarTextoOpcional($this->bairro, 100);
        $this->cidade = $this->normalizarTextoObrigatorio($this->cidade, 'cidade', 100);
        $this->estado = strtoupper($this->normalizarTextoObrigatorio($this->estado, 'estado', 2));
        $this->cep = $this->normalizarCep($this->cep);
        $this->email = strtolower($this->normalizarTextoObrigatorio($this->email, 'e-mail', 255));
        $this->telefone = $this->normalizarTelefone($this->telefone);
        $this->transparencia = $this->normalizarUrlOpcional($this->transparencia, 'Portal da Transparência');
        $this->facebook = $this->normalizarUrlOpcional($this->facebook, 'Facebook');
        $this->instagram = $this->normalizarUrlOpcional($this->instagram, 'Instagram');
        $this->linkedin = $this->normalizarUrlOpcional($this->linkedin, 'LinkedIn');
        $this->youtube = $this->normalizarUrlOpcional($this->youtube, 'YouTube');
        $this->spotify = $this->normalizarUrlOpcional($this->spotify, 'Spotify');
        $this->twitter = $this->normalizarUrlOpcional($this->twitter, 'Twitter/X');

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Informe um e-mail válido.');
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->estado)) {
            throw new Exception('Informe a UF com duas letras.');
        }
    }

    private function normalizarTextoObrigatorio($valor, string $campo, int $tamanhoMaximo): string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            throw new Exception("Informe o {$campo}.");
        }

        if (mb_strlen($valor) > $tamanhoMaximo) {
            throw new Exception("O campo {$campo} deve possuir no máximo {$tamanhoMaximo} caracteres.");
        }

        return $valor;
    }

    private function normalizarTextoOpcional($valor, int $tamanhoMaximo): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return mb_substr($valor, 0, $tamanhoMaximo);
    }

    private function normalizarCnpj($valor): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $valor);

        if (strlen($digitos) !== 14) {
            throw new Exception('Informe um CNPJ com 14 dígitos.');
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digitos);
    }

    private function normalizarCep($valor): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $valor);

        if (strlen($digitos) !== 8) {
            throw new Exception('Informe um CEP com 8 dígitos.');
        }

        return substr($digitos, 0, 5) . '-' . substr($digitos, 5);
    }

    private function normalizarTelefone($valor): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $valor);

        if (!in_array(strlen($digitos), [10, 11], true)) {
            throw new Exception('Informe um telefone com DDD.');
        }

        $tamanhoPrefixo = strlen($digitos) === 11 ? 5 : 4;

        return '(' . substr($digitos, 0, 2) . ') '
            . substr($digitos, 2, $tamanhoPrefixo) . '-'
            . substr($digitos, 2 + $tamanhoPrefixo);
    }

    private function normalizarUrlOpcional($valor, string $campo): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (mb_strlen($valor) > 255 || !filter_var($valor, FILTER_VALIDATE_URL)) {
            throw new Exception("Informe uma URL válida para {$campo}, incluindo http:// ou https://.");
        }

        return $valor;
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
            throw new Exception("A imagem deve ter no máximo 10MB.");
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
            throw new Exception("Formato de imagem não permitido.");
        }

        $pastaTemporaria = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $entrada = tempnam($pastaTemporaria, 'logo_entrada_');
        $saidaBase = tempnam($pastaTemporaria, 'logo_saida_');

        if ($entrada === false || $saidaBase === false) {
            throw new Exception("Não foi possível preparar os arquivos temporários.");
        }

        $saida = $saidaBase . '.png';

        try {
            if (!copy($caminhoTemporario, $entrada)) {
                throw new Exception("Não foi possível preparar a imagem enviada.");
            }

            $this->executarRemovedorFundoPython($entrada, $saida);

            if (!is_file($saida) || filesize($saida) <= 0) {
                throw new Exception("Não foi possível gerar a imagem sem fundo.");
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
            throw new Exception("Script de remoção de fundo não encontrado.");
        }

        $python = $this->obterExecutavelPython();
        $comando = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($entrada) . ' ' . escapeshellarg($saida);
        $descritores = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $processo = proc_open($comando, $descritores, $pipes, BASE_PATH);

        if (!is_resource($processo)) {
            throw new Exception("Não foi possível iniciar o processo Python.");
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
            throw new Exception("A remoção de fundo demorou mais que {$tempoLimite} segundos. Tente uma imagem menor ou tente novamente.");
        }

        if ($codigoRetorno !== 0) {
            $mensagem = trim($saidaErro ?: $saidaPadrao);
            throw new Exception($mensagem !== '' ? $mensagem : "Não foi possível remover o fundo da imagem.");
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
