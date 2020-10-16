<?php

return [
    \App\Core\DatabaseInterface::class => function () {
        return new \App\Core\Database("sqlite:" . \App\Core\Kernel::ROOT_PATH . '/database/db.sqlite');
    },

    '\App\ConsoleCommands\*Command' => DI\create('\App\ConsoleCommands\*Command'),
];