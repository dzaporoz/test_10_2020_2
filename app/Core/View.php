<?php


namespace App\Core;


class View
{
    const VIEW_ROOT = Kernel::ROOT_PATH . '/resources/views/';

    /** @var string path for certain view file */
    public string $path;

    /** @var array current route parameters */
    public array $route;

    public function __construct($route) {
        $this->route = $route;
        $this->path = $route['controller'].'/'.$route['action'];
    }

    /**
     * Output required page view
     *
     * @param $title
     * @param array $vars
     */
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


    /**
     * Shows error page
     *
     * @param int $code
     * @param string $message
     */
    public static function errorCode(int $code, string $message = '') {
        $title = $message ? "$code: $message" : $code;

        http_response_code($code);
        ob_start();
        require self::VIEW_ROOT . '/error.php';
        $content = ob_get_clean();
        require self::VIEW_ROOT . '/base.php';
        exit;
    }
}