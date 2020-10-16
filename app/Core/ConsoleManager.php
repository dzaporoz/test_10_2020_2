<?php

namespace App\Core;

use HaydenPierce\ClassFinder\ClassFinder;

class ConsoleManager
{
    protected array $command_classes = [];

    public function __construct(int $argc, array $argv)
    {
        $this->command_classes = ClassFinder::getClassesInNamespace('App\ConsoleCommands');
    }

    public function showUsage()
    {
        echo "Usage:\n\tphp cli.php [command]" . PHP_EOL. PHP_EOL;
        echo 'Here is list of available commands:' . PHP_EOL;

        $commands = [];
        foreach ($this->command_classes as $command_class) {
            $commands[] = sprintf("%s\t\t\t%s", $command_class::NAME, $command_class::DESCRIPTION);
        }
        sort($commands);
        echo implode(PHP_EOL, $commands) . PHP_EOL;
    }

    public function handleCommand(string $command_name)
    {
        $command = null;

        foreach ($this->command_classes as $command_class) {
            if ($command_class::NAME == $command_name) {
                $command = Kernel::getService($command_class);
            }
        }

        if (! $command) {
            die("ERROR: Command with name $command_name doesn't exist" . PHP_EOL);
        }

        $command->handle();
    }
}