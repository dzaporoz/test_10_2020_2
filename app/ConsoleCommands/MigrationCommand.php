<?php

namespace App\ConsoleCommands;

use PDO;

class MigrationCommand {
    const NAME = 'migrate';

    const DESCRIPTION = 'Create DB with required structure';

    public function handle()
    {
        $project_root = dirname(__FILE__) . '/../..';
        $db_root = $project_root . '/database';
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
        }
        if (! file_exists($db_root)) {
            mkdir($db_root, 0755);
        }
        touch($db_path);

        $db = new PDO("sqlite:$db_path");
        $db->exec('CREATE TABLE IF NOT EXISTS grabbed_articles (
    id INTEGER PRIMARY KEY NOT NULL,
    date DATE NOT NULL,
    url VARCHAR not NULL,
    title VARCHAR NOT NULL,
    excerpt VARCHAR NOT NULL,
    content TEXT not NULL,
    image VARCHAR
)');

        echo "Database migration finished successfully." . PHP_EOL;
    }
}
/*
$project_root = dirname(__FILE__) . '/..';
$db_root = $project_root . '/database';
$db_path = $db_root . '/db.sqlite';

if (file_exists($db_path)) {
    echo "Database is already exists. You will lose all data. Continue? [y/N]\n";
    $handle = fopen ("php://stdin","r");
    $line = strtolower(trim(fgets($handle)));
    if($line != 'yes' && $line != 'y'){
        echo "Migration canceled by user\n";
        exit;
    }
    fclose($handle);
}
if (! file_exists($db_root)) {
    mkdir($db_root, 0755);
}
touch($db_path);

$db = new PDO("sqlite:$db_path");
$db->exec('CREATE TABLE IF NOT EXISTS grabbed_articles (
    id INTEGER PRIMARY KEY NOT NULL,
    date DATE NOT NULL,
    title VARCHAR NOT NULL,
    excerpt VARCHAR NOT NULL,
    content TEXT not NULL,
    image VARCHAR
)');

die;
//Uncomment the line above to create the  Table, do it only once.
$string_to_insert=$db->escapeString(date("r" ,time()));//Important to escape any strings before inserting them into a query since they can contain an illegal character
//or can be used for "sting insertion" hacks.
$db->exec("INSERT INTO table1 (value) VALUES ('$string_to_insert')");
$sql_select='SELECT * FROM table1 ORDER BY ID DESC';
$result=$db->query($sql_select);
echo "<table border='1'>";
echo "<tr>";
$numColumns=$result->numColumns();
for ($i = 0; $i < $numColumns; $i++)
{
    $colname=$result->columnName($i);
    echo "<th>$colname</th>";
}
?>
*/
