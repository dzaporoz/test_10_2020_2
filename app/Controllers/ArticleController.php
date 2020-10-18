<?php


namespace App\Controllers;


use App\Core\Controller;

class ArticleController extends Controller
{
    public function list()
    {
        echo 'list';

    }

    public function show(int $articleId)
    {
        $this->view->render('Article');
        echo $articleId . ' show';
    }
}