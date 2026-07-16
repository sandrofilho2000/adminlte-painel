<?php

class Controller
{
    private static $_instance;
    private static $_permissao;
    private static $_page_title = 'Painel';
    private static $_apenas_confef = false;

    private $_meta_tags = [];

    private $_files_javascript_header = [
        "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js",
        "https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js",
        "https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js",
        "https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jquery.vmap.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/maps/jquery.vmap.brazil.js",
    ];

    private $_files_javascript = [
        "https://cdn.jsdelivr.net/npm/swiper@12.1.2/swiper-bundle.min.js",
        "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js",
        "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js",
        "https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js",
        "https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js",
        "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js",
        "/src/functions.js",
    ];

    private $_files_styles = [
        "/src/styles.css",
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css",
        "https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css",
        "https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css",
        "https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css",
        "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css",
        "https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jqvmap.min.css"
    ];

    private const INTEGRIDADE_ATIVOS = [
        'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js' => 'sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs',
        'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js' => 'sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct',
        'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js' => 'sha384-GzAyPc+9MeNdsDGfpe/gNkeDXXSbdZdY0yKEFBGFxqmq/97NJ92k5oyF1YPOOhm5',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js' => 'sha384-9MhbyIRcBVQiiC7FSd7T38oJNj2Zh+EfxS7/vjhBi4OOT78NlHSnzM31EZRWR1LZ',
        'https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jquery.vmap.min.js' => 'sha384-rLA8VzLoBHS94UKKsOAwExBuHk1WCiWP9LzzY0KBjgq5/dkGDIsgQ5A2l1Q5xbHX',
        'https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/maps/jquery.vmap.brazil.js' => 'sha384-QJxGpPlkRFX8oJhmEOYk5EZO9Ipy2isocFjhxBG8sm5EiLfOK8ZCZFk1cd6WIS/Z',
        'https://cdn.jsdelivr.net/npm/swiper@12.1.2/swiper-bundle.min.js' => 'sha384-XBt9wZlqV7TZ2Yv+sr6KBngDWjf3ZKfi4bFXmIDpeuyhbhRBDsaLJnqhU4O7avkB',
        'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js' => 'sha384-cjmdOgDzOE22dUheI5E6Gzd3upfmReW8N1y/4jwKQE50KYcvFKZJA9JxWgQOzqwQ',
        'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js' => 'sha384-vCX+UFRnh1Gp0hr9dL82snXI1HvdBaApGHMjbewoGQ69VkYcHt9jvTy+Q4CAWwPX',
        'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js' => 'sha384-VUnyCeQcqiiTlSM4AISHjJWKgLSM5VSyOeipcD9S/ybCKR3OhChZrPPjjrLfVV0y',
        'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js' => 'sha384-OOkn5Krc37A+2dQETF0WjM74kdj9ZsNH5myIgTgDeCm6PDGgKRELxvACGYjTuYLc',
        'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js' => 'sha384-HCjW0//bc6Gu9bS3OISjenLhzVqjRipLVVj9LZtzKu+FYXXOZVCN7WDv2TYxCfmo',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' => 'sha384-nRgPTkuX86pH8yjPJUAFuASXQSSl2/bBUiNV47vSYpKFxHJhbcrGnmlYpYJMeD7a',
        'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css' => 'sha384-qrt37eUXKQgF1p6OlpdB29OTyKryxbxdJHkvfVN4suujWnn6PibIvbnygcK4uJfA',
        'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css' => 'sha384-qG0OxGrM7FX2QWKJFlEklnjVtIyDs8DV8Vsdp+56Mc8mexZqqM2+CWM8eGJ+/s/Q',
        'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css' => 'sha384-f89RBCpXIb8B0pmvO2fADSw3ZHEvAPLa2qgOF4azIIoo140I8FVDJw07g47TXNHe',
        'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css' => 'sha384-KZO2FRYNmIHerhfYMjCIUaJeGBRXP7CN24SiNSG+wdDzgwvxWbl16wMVtWiJTcMt',
        'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css' => 'sha384-dZ/kdA+7jiSms5Z+NkfRPANv1sHvWqS7e51A6ywK9wsJztsDG2q4gA4Ltui+/1yW',
        'https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jqvmap.min.css' => 'sha384-xau09+vRM4u6xFkktxCxgMaX317B0DbdKzSsPpfpbT2U1J2QmOAObomSguUmNc8O',
    ];

    private function __construct() {}

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function setPageTitle($title)
    {
        self::$_page_title = $title;
    }

    public static function setMetaTag($property, $content)
    {
        self::getInstance()->_meta_tags[$property] = $content;
    }

    public static function setFileStyle($file)
    {
        if (!in_array($file, self::getInstance()->_files_styles)) {
            self::getInstance()->_files_styles[] = $file;
        }
    }

    public static function setFileJavascript($file)
    {
        if (!in_array($file, self::getInstance()->_files_javascript)) {
            self::getInstance()->_files_javascript[] = $file;
        }
    }

    public static function setPermissao($permissao)
    {
        self::$_permissao = $permissao;
    }

    public static function getPermissao()
    {
        return self::$_permissao;
    }

    public static function setApenasConfef($apenas_confef)
    {
        self::$_apenas_confef = $apenas_confef;
    }

    public static function getApenasConfef()
    {
        return self::$_apenas_confef;
    }

    public static function setFileJavascriptHeader($file)
    {
        if (!in_array($file, self::getInstance()->_files_javascript_header)) {
            self::getInstance()->_files_javascript_header[] = $file;
        }
    }

    public static function getFilesJavascriptHeader()
    {
        $files = self::getInstance()->_files_javascript_header;

        if (count($files) == 0) {
            return;
        }

        foreach ($files as $filename) {
            if (self::assetExists($filename)) {
                echo '<script src="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '"' . self::atributosIntegridade($filename) . '></script>' . PHP_EOL;
            }
        }
    }

    public static function getFilesJavascript()
    {
        $files = self::getInstance()->_files_javascript;

        if (count($files) == 0) {
            return;
        }

        foreach ($files as $filename) {
            if (self::assetExists($filename)) {
                echo '<script src="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '"' . self::atributosIntegridade($filename) . '></script>' . PHP_EOL;
            }
        }
    }

    public static function getMetaTags()
    {
        $metaTags = self::getInstance()->_meta_tags  ?? [];

        foreach ($metaTags as $property => $content) {
            echo '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
        }
    }

    public static function getFilesStyles()
    {
        $files = self::getInstance()->_files_styles;

        if (count($files) == 0) {
            return;
        }

        foreach ($files as $filename) {
            if (self::assetExists($filename)) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '"' . self::atributosIntegridade($filename) . '>' . PHP_EOL;
            }
        }
    }

    public static function getPageTitle()
    {
        $_page_title = self::$_page_title ?? 'Painel';
        return $_page_title;
    }

    private static function assetExists($filename)
    {
        if (preg_match('/^https?:\/\//', $filename)) {
            return true;
        }

        $assetPath = parse_url($filename, PHP_URL_PATH);
        $relativeFilename = ltrim(is_string($assetPath) ? $assetPath : $filename, '/');

        return file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $relativeFilename)
            || file_exists(BASE_PATH . DIRECTORY_SEPARATOR . 'paginas' . DIRECTORY_SEPARATOR . $relativeFilename);
    }

    private static function assetUrl($filename)
    {
        if (preg_match('/^https?:\/\//', $filename)) {
            return $filename;
        }

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/adminlte-painel';

        return $baseUrl . '/' . ltrim($filename, '/');
    }

    private static function atributosIntegridade($filename): string
    {
        $integridade = self::INTEGRIDADE_ATIVOS[$filename] ?? '';

        if ($integridade === '') {
            return '';
        }

        return ' integrity="' . htmlspecialchars($integridade, ENT_QUOTES, 'UTF-8') . '" crossorigin="anonymous" referrerpolicy="no-referrer"';
    }
}
