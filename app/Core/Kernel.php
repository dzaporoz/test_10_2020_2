<?php


namespace App\Core;


use DI\Container;
use DI\ContainerBuilder;
use DirectoryIterator;

class Kernel
{
    const ROOT_PATH = __DIR__ . '/../..';

    // singletone instance of application kernel
    protected static $instance = null;

    // service container
    protected Container $container;

    // array to keep application configuration
    protected array $config = [];

    private function __construct()
    {
        // load service container
        $builder = new ContainerBuilder();
        $builder->addDefinitions(require self::ROOT_PATH . '/services.php');
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

    /**
     * Get service instance by its string key
     *
     * @param $name
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function getService($name)
    {
        return self::getInstance()->container->get($name);
    }

    /**
     * Get part of config by its associated key
     *
     * @param string|null $key
     * @return array|mixed|null
     */
    public static function config(string $key = null)
    {
        if (empty($key)) {
            return self::getInstance()->config;
        }

        return self::getInstance()->config[$key] ?? null;
    }

    /**
     * Application execution from CLI mode
     *
     * @param int $argc
     * @param array $argv
     */
    public function handleCli(int $argc, array $argv)
    {
        $cm = new ConsoleManager($argc, $argv);

        if ($argc === 1) {
            $cm->showUsage();
        } else {
            try {
                $cm->handleCommand($argv[1]);
            } catch (\Exception $e) {
                echo 'An error occurred during program execution: ' . $e->getMessage() . PHP_EOL;
            }
        }
    }

    /*
     * Application execution to handle HTTP request
     */
    public function handleHttp()
    {
        try {
            $router = new Router();
            $router->run();
        } catch (\Exception $e) {
            View::errorCode(500, $e->getMessage());
        }
    }
}