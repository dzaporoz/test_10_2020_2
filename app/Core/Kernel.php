<?php


namespace App\Core;


use DI\Container;
use DI\ContainerBuilder;

class Kernel
{
    const ROOT_PATH = __DIR__ . '/../..';

    protected static $instance = null;

    protected Container $container;

    private function __construct()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(require dirname(__FILE__) . '/../../services.php');
        $this->container = $builder->build();
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
        
    }
}