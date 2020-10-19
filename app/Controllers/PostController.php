<?php


namespace App\Controllers;


use App\Core\Controller;
use App\Core\DatabaseInterface;
use App\Core\Kernel;
use App\Repositories\PostRepository;

class PostController extends Controller
{
    protected PostRepository $postRepository;

    public function __construct($route)
    {
        parent::__construct($route);

        $this->postRepository = new PostRepository(Kernel::getService(DatabaseInterface::class));
    }

    /*
     * Lists all posts on index page
     */
    public function list()
    {
        $data['posts'] = $this->postRepository->findAll();

        $this->view->render('Главная', $data);

    }

    /*
     * Shows certain post detailed page
     */
    public function show(int $postId)
    {
        $data['post'] = $this->postRepository->find($postId);

        if (empty($data['post'])) {
            $this->view::errorCode(404, 'Post not found');
        }

        $this->view->render($data['post']['title'], $data);
    }
}