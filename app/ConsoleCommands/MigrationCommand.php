<?php

namespace App\ConsoleCommands;

use App\Core\DatabaseInterface;
use App\Core\Kernel;

class MigrationCommand {
    const NAME = 'migrate';

    const DESCRIPTION = 'Create DB with required structure';

    const DB_ROOT = Kernel::ROOT_PATH . '/database';

    const DB_PATH = self::DB_ROOT . '/db.sqlite';

    /*
     * Creates Sqlite Database with required structure
     */
    public function handle()
    {
        $this->prepareFileStructure();

        $this->migrateDatabase();

        echo "Database migration finished successfully." . PHP_EOL;
    }

    /*
     * Creates folder and file for DB. Rewrite existing file depends on user input
     */
    protected function prepareFileStructure()
    {
        if (file_exists(self::DB_PATH)) {
            echo "Database is already exists. You will lose all data. Continue? [y/N]\n";
            $handle = fopen ("php://stdin","r");
            $line = strtolower(trim(fgets($handle)));
            if($line != 'yes' && $line != 'y'){
                echo "Migration canceled by user.\n";
                exit;
            }
            fclose($handle);
            unlink(self::DB_PATH);
        }
        if (! file_exists(self::DB_ROOT)) {
            mkdir(self::DB_ROOT, 0755);
        }
        touch(self::DB_PATH);
    }

    /*
     * Runs SQL command to create required structure in DB
     */
    protected function migrateDatabase()
    {
        $db = Kernel::getService(DatabaseInterface::class);
        $db->exec('CREATE TABLE IF NOT EXISTS grabbed_posts (
            id INTEGER PRIMARY KEY NOT NULL,
            url VARCHAR not NULL,
            title VARCHAR NOT NULL,
            excerpt VARCHAR NOT NULL,
            content TEXT not NULL,
            image_url VARCHAR
        )');
    }
}
