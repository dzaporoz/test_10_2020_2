<?php


namespace App\Core;


class View
{
    const VIEW_ROOT = Kernel::ROOT_PATH . '/resources/views/';

    public $path;

    public $route;

    public function __construct($route) {
        $this->route = $route;
        $this->path = $route['controller'].'/'.$route['action'];
    }

    public function render($title, $vars = []) {
        if (is_array($vars)) {
            extract($vars);
        }
        $path = self::VIEW_ROOT . $this->path.'.php';
        if (file_exists($path)) {
            ob_start();
            require $path;
            $content = ob_get_clean();
            require self::VIEW_ROOT . '/base.php';
        } else {
            echo 'View not found';
        }
    }

    public function redirect($url) {
        header('location: '.$url);
        exit;
    }

    public static function errorCode($code) {
        http_response_code($code);
        $path = self::VIEW_ROOT . '/errors/'.$code.'.php';
        if (file_exists($path)) {
            require $path;
        }
        exit;
    }
}