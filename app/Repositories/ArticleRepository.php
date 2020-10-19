<?php

namespace App\Repositories;


use App\Core\Repository;


class ArticleRepository extends Repository
{
    public function findAll()
    {
        $sql = "SELECT * FROM grabbed_articles";
        $result = $this->db->prepare($sql);
        $result->execute();

        return $result->fetchAll();
    }

    public function find(int $id)
    {
        $sql = "SELECT * FROM grabbed_articles WHERE id=?";
        $result = $this->db->prepare($sql);
        $result->execute([$id]);

        return $result->fetch();
    }

    public function findByUrl()
    {
        
    }

    public function saveBulk(array $articlesData)
    {
        $flattenedData = [];
        foreach ($articlesData as $articleDatum) {
            $flattenedData = array_merge($flattenedData, [
                $articleDatum['url'],
                $articleDatum['image_url'],
                $articleDatum['title'],
                $articleDatum['content'],
                $articleDatum['excerpt'],
            ]);
        }

        $rows = str_repeat('(?,?,?,?,?),', count($articlesData) - 1) . '(?,?,?,?,?)';
        $sql = "INSERT INTO grabbed_articles (url, image_url, title, content, excerpt) VALUES $rows";

        $this->db->prepare($sql)->execute($flattenedData);
    }
}