<?php


namespace App\Core;


class Kernel
{
    protected static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new static();
        }

        return self::$instance;
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