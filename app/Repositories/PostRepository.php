<?php

namespace App\Repositories;


use App\Core\Repository;


class PostRepository extends Repository
{
    /**
     * @return mixed searches for all rows in table
     */
    public function findAll()
    {
        $sql = "SELECT * FROM grabbed_posts ORDER BY id DESC";
        $result = $this->db->prepare($sql);
        $result->execute();

        return $result->fetchAll();
    }

    /**
     * Searches for one row by id
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $sql = "SELECT * FROM grabbed_posts WHERE id=?";
        $result = $this->db->prepare($sql);
        $result->execute([$id]);

        return $result->fetch();
    }

    /**
     * Searches for rows by given URLs
     *
     * @param string|string[] $urls array of URLs to search or one string url
     */
    public function findByUrl($urls)
    {
        if (! is_array($urls)) {
            $urls = [$urls];
        }

        $in  = str_repeat('?,', count($urls) - 1) . '?';
        $sql = "SELECT url FROM grabbed_posts WHERE url IN ($in)";
        $result = $this->db->prepare($sql);
        $result->execute($urls);
        $posts = $result->fetchAll();

        if (! $posts) {
            return [];
        }
        return $posts;
    }

    /**
     * Creates multiple rows with given data
     *
     * @param array $postsData
     */
    public function saveBulk(array $postsData)
    {
        $flattenedData = [];
        foreach ($postsData as $postDatum) {
            $flattenedData = array_merge($flattenedData, [
                $postDatum['url'],
                $postDatum['image_url'],
                $postDatum['title'],
                $postDatum['content'],
                $postDatum['excerpt'],
            ]);
        }

        $rows = str_repeat('(?,?,?,?,?),', count($postsData) - 1) . '(?,?,?,?,?)';
        $sql = "INSERT INTO grabbed_posts (url, image_url, title, content, excerpt) VALUES $rows";

        $this->db->prepare($sql)->execute($flattenedData);
    }
}