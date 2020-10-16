<?php

namespace App\ConsoleCommands;

use App\Core\DatabaseInterface;
use App\Core\Kernel;

class MigrationCommand {
    const NAME = 'migrate';

    const DESCRIPTION = 'Create DB with required structure';

    public function handle()
    {
        $db_root = Kernel::ROOT_PATH . '/database';
        $db_path = $db_root . '/db.sqlite';

        if (file_exists($db_path)) {
            echo "Database is already exists. You will lose all data. Continue? [y/N]\n";
            $handle = fopen ("php://stdin","r");
            $line = strtolower(trim(fgets($handle)));
            if($line != 'yes' && $line != 'y'){
                echo "Migration canceled by user.\n";
                exit;
            }
            fclose($handle);
            unlink($db_path);
        }
        if (! file_exists($db_root)) {
            mkdir($db_root, 0755);
        }
        touch($db_path);

        $db = Kernel::getService(DatabaseInterface::class);
        $db->exec('CREATE TABLE IF NOT EXISTS grabbed_articles (
            id INTEGER PRIMARY KEY NOT NULL,
            url VARCHAR not NULL,
            title VARCHAR NOT NULL,
            excerpt VARCHAR NOT NULL,
            content TEXT not NULL,
            image_url VARCHAR
        )');

        echo "Database migration finished successfully." . PHP_EOL;
    }
}
