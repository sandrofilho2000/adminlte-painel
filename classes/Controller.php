<?php

class Controller
{
    private static $_instance;
    private static $_page_title = 'Painel';

    private $_meta_tags = [];

    private $_files_javascript_header = [
        "https://code.jquery.com/jquery-3.6.0.min.js",
        "https://cdn.jsdelivr.net/npm/scrollreveal@4.0.9/dist/scrollreveal.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js",
        "https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js",
        "https://cdn.datatables.net/responsive/2.1.1/js/dataTables.responsive.min.js",
    ];
    private $_files_javascript = [
        "https://cdn.jsdelivr.net/npm/swiper@11.1.0/swiper-bundle.min.js",
        "/src/js/functions.js"
    ];
    private $_files_styles = [
        "https://cdn.datatables.net/responsive/2.1.1/css/responsive.dataTables.min.css",
        "https://fonts.googleapis.com/css2?family=Wix+Madefor+Display:wght@400..800&display=swap",
        "https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css",
        "https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css",
        "/src/css/carrousel.css",
        "https://cdn.jsdelivr.net/npm/swiper@11.1.0/swiper-bundle.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css",
        "/src/css/style.css",
        "https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css",
    ];

    private function __construct()
    {
    }

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
        if(!in_array($file, self::getInstance()->_files_styles)) {
            self::getInstance()->_files_styles[] = $file;
        }
    }

    public static function setFileJavascript($file)
    {
        if(!in_array($file, self::getInstance()->_files_javascript)) {
            self::getInstance()->_files_javascript[] = $file;
        }
    }

    public static function setFileJavascriptHeader($file)
    {
        if(!in_array($file, self::getInstance()->_files_javascript_header)) {
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

        $relativeFilename = ltrim($filename, '/');

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
