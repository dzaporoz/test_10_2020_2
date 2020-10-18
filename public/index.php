<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (php_sapi_name() === 'cli') {
    die('Can\'t be runned in CLI mode' . PHP_EOL);
}

$kernel = \App\Core\Kernel::getInstance();

$kernel->handleHttp();