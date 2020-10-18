<?php


namespace App\Core;


class Controller
{
    public $route;
    public View $view;

    public function __construct($route) {
        $this->route = $route;
        $this->view = New View($route);
    }
}