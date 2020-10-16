<?php

class Grabber {
    const URL_TO_GRAB = 'https://www.rbc.ru/';
    
    const GRAB_LIMIT = 15;
    
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function grab()
    {
        $index_page = file_get_contents(self::URL_TO_GRAB);

        $articles = $this->parseArticlesBlocks($index_page);
    }

    protected function parseArticlesBlocks(string $page_content)
    {
        $articles = preg_match_all('~<a[^>]*data-yandex-name="from_news_feed"[^>]*>~msi', $page_content, $matches);

        var_dump($matches);
    }

}

