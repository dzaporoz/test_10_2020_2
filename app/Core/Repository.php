<?php


namespace App\Core;


abstract class Repository
{
    protected DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public abstract function find(int $id);

    public abstract function findAll();
}