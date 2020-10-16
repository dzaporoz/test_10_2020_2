<?php


namespace App\Core;


interface DatabaseInterface
{
    public function prepare($statement, array $driver_options = array());
}