<?php


namespace App\Controllers;


use App\Core\Controller;
use App\Core\DatabaseInterface;
use App\Core\Kernel;
use App\Repositories\ArticleRepository;

class ArticleController extends Controller
{
    protected ArticleRepository $articleRepository;

    public function __construct($route)
    {
        parent::__construct($route);

        $this->articleRepository = new ArticleRepository(Kernel::getService(DatabaseInterface::class));
    }

    public function list()
    {
        $data['posts'] = $this->articleRepository->findAll();

        $this->view->render('Главная', $data);

    }

    public function show(int $articleId)
    {
        $data['post'] = $this->articleRepository->find($articleId);

        if (empty($data['post'])) {
            $this->view::errorCode(404, 'Post not found');
        }

        $this->view->render($data['post']['title'], $data);
    }
}