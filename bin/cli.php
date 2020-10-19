<?php
require_once dirname(__FILE__) . '/../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    die('Can be launched only in CLI mode' . PHP_EOL);
}

$kernel = \App\Core\Kernel::getInstance();

$kernel->handleCli($argc, $argv);