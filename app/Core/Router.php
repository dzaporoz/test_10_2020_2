<?php


namespace App\Core;


class Router
{
    /** @var array associated array of routes patterns and their data */
    protected array $routes = array();

    /** @var array parameters of current route */
    protected array $params = array();

    /** @var array dynamical URL parameters */
    protected array $urlParams = array();

    function __construct() {
        $routesData = Kernel::config('routes');
        foreach ($routesData as $key => $val) {
            $this->add($key, $val);
        }
    }

    /**
     * Generate regex pattern for certain route and saves it in $routes array
     *
     * @param $route
     * @param $params
     */
    public function add($route, $params) {
        $route = '#^'.$route.'$#';
        $this->routes[$route] = $params;
    }

    /**
     * Checks current request URI for coincidence with given routes
     * and fills variables with parameters
     *
     * @return bool
     */
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

    /**
     * Passes parameters to appropriate controller method or shows an error
     */
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