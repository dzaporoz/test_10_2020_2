<?php

return [
    \App\Core\DatabaseInterface::class => function () {
        $db = new \App\Core\Database("sqlite:" . \App\Core\Kernel::ROOT_PATH . '/database/db.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_COLUMN);
        return $db;
    },

    '\App\ConsoleCommands\*Command' => DI\create('\App\ConsoleCommands\*Command'),
];