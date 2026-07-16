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
                echo '<script src="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;
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
                echo '<script src="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;
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
                echo '<link rel="stylesheet" href="' . htmlspecialchars(self::assetUrl($filename), ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
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
}
