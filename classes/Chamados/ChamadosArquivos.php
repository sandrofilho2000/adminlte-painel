<?php

namespace Classes;

require_once BASE_PATH . '/admin/includes/functions.php';

use PDO;
use DateTime;

class ChamadosArquivos extends ClasseBase
{
    private const MIME_PARA_EXTENSAO_ARQUIVO = array(
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'application/vnd.rar' => 'rar',
        'application/x-7z-compressed' => '7z',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/svg+xml' => 'svg',
    );

    private const EXTENSOES_PERMITIDAS = array(
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'bmp',
        'svg',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
        'zip',
        'rar',
        '7z',
        'csv',
        'ods',
    );

    public $id;
    public $id_chamado;
    public $nome;
    public $tipo;
    public $arquivo;
    public $tamanho;
    public $temp_id_chamado;
    public $criado_em;
    public $criado_por_id;

    protected $_tabela = array(
        'nome' => 'TBLChamados_Arquivos',
        'schema' => null,
        'chave_primaria' => array('id'),
        'colunas' => array(
            "id",
            "id_chamado",
            "nome",
            "tipo",
            "arquivo",
            "temp_id_chamado",
            "criado_em",
            "criado_por_id",
        ),
        'permissao' => '00072'
    );

    public function __construct() {}

    public function criaNotificacao(){
        
    }

    private function normalizarMimeArquivo($mime)
    {
        $mime = strtolower(trim((string) $mime));
        if ($mime === '') {
            return '';
        }

        $partes = explode(';', $mime);
        return trim($partes[0] ?? '');
    }

    private function obterExtensaoArquivoAnexo($nomeArquivo, $mime = '')
    {
        $nomeArquivo = trim((string) $nomeArquivo);
        if ($nomeArquivo !== '') {
            $partes = explode('.', $nomeArquivo);
            if (count($partes) > 1) {
                $extensao = strtolower(trim((string) array_pop($partes)));
                if ($extensao !== '') {
                    return $extensao;
                }
            }
        }

        $mimeNormalizado = $this->normalizarMimeArquivo($mime);
        if ($mimeNormalizado === '') {
            return '';
        }

        return self::MIME_PARA_EXTENSAO_ARQUIVO[$mimeNormalizado] ?? '';
    }

    private function arquivoAnexoPermitido($nomeArquivo, $mime = '')
    {
        $extensao = $this->obterExtensaoArquivoAnexo($nomeArquivo, $mime);
        return $extensao !== '' && in_array($extensao, self::EXTENSOES_PERMITIDAS, true);
    }

    public function criaArquivo()
    {
        $arquivo = $this;

        $Chamados = new Chamados();
        $atributos_editaveis = $Chamados->getAtributosEditaveis($arquivo->id_chamado);
        $pode_anexar = !empty($arquivo->temp_id_chamado) || !empty($atributos_editaveis['anexar_arquivos']);

        if ($pode_anexar) {
            $upload = $_FILES['file'] ?? null;
            $tmp_path = is_array($upload) ? ($upload['tmp_name'] ?? null) : null;
            $erro_upload = is_array($upload) ? (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

            if ($erro_upload !== UPLOAD_ERR_OK || empty($tmp_path) || !is_file($tmp_path)) {
                return array(
                    'status' => 'error',
                    'message' => 'Falha ao receber o arquivo. Tente novamente.'
                );
            }

            $name = (string)($upload['name'] ?? '');
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $tipo = $this->normalizarMimeArquivo($finfo->file($tmp_path) ?: '');

            $arquivo_nome = trim((string) $name);
            $arquivo_nome = preg_replace('/[^a-zA-Z0-9_\\-\\.]/', '_', $arquivo_nome);
            $arquivo_nome = preg_replace('/_+/', '_', $arquivo_nome);
            $arquivo_nome = substr($arquivo_nome, 0, 255);

            if (!$this->arquivoAnexoPermitido($arquivo_nome, $tipo)) {
                return array(
                    'status' => 'error',
                    'message' => 'Formato de arquivo não permitido. Use imagens, PDF, DOC, DOCX, XLS, XLSX, CSV, ODS, TXT, ZIP, RAR ou 7Z.'
                );
            }

            $blob = file_get_contents($tmp_path);
            $tipo_armazenado = $this->obterExtensaoArquivoAnexo($arquivo_nome, $tipo);
            if ($tipo_armazenado === '') {
                $tipo_armazenado = $tipo;
            }

            $arquivo->criado_por_id = ID_USER;
            $arquivo->criado_em = (new DateTime())->format('Y-m-d H:i:s');
            $arquivo->nome = $arquivo_nome;
            $arquivo->arquivo = $blob;
            $arquivo->tipo = $tipo_armazenado;
            $incluir = $arquivo->incluir();
            unset($incluir['arquivo']);
            return $incluir;
        }

        return array(
            'status' => 'error',
            'message' => 'Você não tem permissão para anexar arquivos neste chamado.'
        );
    }

    public function excluirArquivo()
    {
        $arquivo = self::instanciarPorId($this->id);

        if (empty($arquivo)) {
            throw new \Exception('Arquivo não encontrado.');
        }

        if (!empty($arquivo->id_chamado)) {
            $chamado = (new Chamados())->instanciarPorId($arquivo->id_chamado);
            if (!empty($chamado) && ((int)$chamado->arquivado === 1 || $chamado->coluna === 'arquivados')) {
                throw new \Exception('Não é possível excluir arquivos de cards arquivados.');
            }
        }

        if ((string)$arquivo->criado_por_id !== (string)ID_USER) {
            throw new \Exception('Você não tem permissão para excluir este arquivo.');
        }

        $exclusao = Dao::excluir($arquivo);
        if ($exclusao <= 0) {
            throw new \Exception('Não foi possível excluir o arquivo.');
        }

        return [
            'status' => 'success',
            'id' => (string)$arquivo->id
        ];
    }

    public function getArquivos($id_chamado = null, $temp_id_chamado = null, $somente_do_usuario_atual = false)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $temp_id_chamado = $temp_id_chamado ?? $this->temp_id_chamado;

        $this->queryCorrente = "SELECT id, id_chamado, nome, tipo, OCTET_LENGTH(arquivo) as tamanho, temp_id_chamado, criado_em, criado_por_id FROM TBLChamados_Arquivos WHERE 1=1 ";

        if (!empty($id_chamado)) {
            $this->filtrar("id_chamado", $id_chamado);
        }

        if (!empty($temp_id_chamado)) {
            $this->filtrar("temp_id_chamado", $temp_id_chamado);
        }

        if ($somente_do_usuario_atual) {
            $this->filtrar("criado_por_id", ID_USER);
        }

        $this->ordenar("criado_em", "DESC");
        return $this->buscar() ?? [];
    }

    public function vincularArquivosTemporariosAoChamado($id_chamado = null, $temp_id_chamado = null)
    {
        $id_chamado = $id_chamado ?? $this->id_chamado;
        $temp_id_chamado = $temp_id_chamado ?? $this->temp_id_chamado;

        if (empty($id_chamado)) {
            return [];
        }

        if (empty($temp_id_chamado)) {
            return $this->getArquivos($id_chamado);
        }

        $arquivos_temporarios = $this->getArquivos(null, $temp_id_chamado, true);

        foreach ($arquivos_temporarios as $arquivo_temporario) {
            $arquivo = self::instanciarPorId($arquivo_temporario->id);
            if (empty($arquivo) || (string)$arquivo->criado_por_id !== (string)ID_USER) {
                continue;
            }

            $arquivo->id_chamado = $id_chamado;
            $arquivo->temp_id_chamado = null;
            $arquivo->salvar();
        }

        return $this->getArquivos($id_chamado);
    }
}
