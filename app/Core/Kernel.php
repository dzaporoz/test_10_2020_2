<?php


namespace App\Core;


use DI\Container;
use DI\ContainerBuilder;
use DirectoryIterator;

class Kernel
{
    const ROOT_PATH = __DIR__ . '/../..';

    protected static $instance = null;

    protected Container $container;
    
    protected array $config = [];

    private function __construct()
    {
        // load service container
        $builder = new ContainerBuilder();
        $builder->addDefinitions(require dirname(__FILE__) . '/../../services.php');
        $this->container = $builder->build();

        // load configuration from files
        $dir = new DirectoryIterator(self::ROOT_PATH . '/config');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->getType() === 'file') {
                $filename = pathinfo($fileinfo->getRealPath())['filename'];
                $this->config[$filename] = require $fileinfo->getRealPath();
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public static function getService($name)
    {
        return self::getInstance()->container->get($name);
    }

    public static function config(string $key = null)
    {
        if (empty($key)) {
            return self::getInstance()->config;
        }

        return self::getInstance()->config[$key] ?? null;
    }

    public function handleCli(int $argc, array $argv)
    {
        $cm = new ConsoleManager($argc, $argv);

        if ($argc === 1) {
            $cm->showUsage();
        } else {
            $cm->handleCommand($argv[1]);
        }
    }

    public function handleHttp()
    {
        $router = new Router();
        $router->run();
    }
}