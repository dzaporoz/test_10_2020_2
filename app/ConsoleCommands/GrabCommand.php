<?php


namespace App\ConsoleCommands;


use GuzzleHttp\Client;

class GrabCommand
{
    const NAME = 'grab';

    const DESCRIPTION = 'Grabs 15 articles from rbc.ru and saves them to DB';

    const URL_TO_GRAB = 'https://www.rbc.ru';

    const GRAB_LIMIT = 15;

    protected $db;

    public function handle()
    {
        $client = new Client();
        $res = $client->request('GET', self::URL_TO_GRAB);

        $newArticles = $this->getNewArticles($res->getBody());

        $articlesToParse = $this->filterArticles($newArticles);
    }

    protected function getNewArticles(string $page_content) : array
    {
        preg_match_all('~<a[^>]*data-yandex-name="from_news_feed"[^>]*>~msi', $page_content, $matches);

        $articles_data = [];
        foreach ($matches[0] as $match) {
            $href = substr($match, strpos($match, 'href="') + 6);
            $href_end = min(array_filter([strpos($href, '"'), strpos($href, '?')]));
            $href = substr($href, 0, $href_end);
            $articles_data[] = ['url' => $href];
        }

        return $articles_data;
    }

    protected function filterArticles(array $articlesToFilter)
    {
        $urls = array_column($articlesToFilter, 'url');

        $in  = str_repeat('?,', count($urls) - 1) . '?';
        $sql = "SELECT url FROM grabbed_articles WHERE url IN ($in)";
        $result = $this->db->prepare($sql);
        $result->execute($urls);
        $existingUrls = $result->fetchAll();

        print_r($existingUrls);
    }
}