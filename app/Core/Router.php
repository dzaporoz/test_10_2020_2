<?php


namespace App\Core;


class Router
{
    protected array $routes = array();
    protected array $params = array();
    protected array $urlParams = array();

    function __construct() {
        $routesData = Kernel::config('routes');
        foreach ($routesData as $key => $val) {
            $this->add($key, $val);
        }
    }

    public function add($route, $params) {
        $route = '#^'.$route.'$#';
        $this->routes[$route] = $params;
    }

    public function match() {
        $url = trim($_SERVER['REQUEST_URI'], '/');
        if (strpos($url, '?') !== false)
            $url = substr($url, 0, strpos($url, '?'));
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $this->urlParams)) {
                $this->params = $params;
                unset($this->urlParams[0]);
                return true;
            }
        }
        return false;
    }

    public function run() {
        if ($this->match()) {
            $path = 'App\Controllers\\'.ucfirst($this->params['controller']).'Controller';
            if (class_exists($path)) {
                $action = $this->params['action'];
                if (method_exists($path, $action)) {
                    $controller = new $path($this->params);
                    $controller->$action(...$this->urlParams);
                } else {
                    View::errorCode(404);
                }
            } else {
                View::errorCode(404);
            }
        } else {
            View::errorCode(404);
        }
    }
}