<?php


namespace App\ConsoleCommands;


use App\Core\DatabaseInterface;
use App\Core\Kernel;
use GuzzleHttp\Client;

class GrabCommand
{
    const NAME = 'grab';

    const DESCRIPTION = 'Grabs 15 articles from rbc.ru and saves them to DB';

    const URL_TO_GRAB = 'https://www.rbc.ru';

    const GRAB_LIMIT = 15;

    const EXCERPT_LENGHT = 200;

    const CONTENT_SELECTORS_BLACK_LIST = [
        'div.article__text__overview', 'div.article__main-image', 'div.article__inline-item',
        'div.article__clear', 'div.banner', 'div.pro-anons', 'script'
    ];

    protected $db;

    protected $httpClient;

    public function __construct(DatabaseInterface $db, Client $httpClient)
    {
        $this->db = $db;

        $this->httpClient = $httpClient;
    }

    public function handle()
    {
        $res = $this->httpClient->request('GET', self::URL_TO_GRAB);

        $articlesUrls = $this->getNewArticles($res->getBody());
        $urlsToGrab = array_slice($this->filterArticles($articlesUrls), 0, self::GRAB_LIMIT);
        $urlsToGrab = array_fill_keys($urlsToGrab, null);

        $articlesNum = count($urlsToGrab);
        if ($articlesNum == 0) {
            die('There is nothing to grab' . PHP_EOL);
        }
        echo "There are $articlesNum new articles to be parsed:" . PHP_EOL;
//        echo implode(PHP_EOL, $urlsToGrab) . PHP_EOL;
        $this->outputStatusTable($urlsToGrab);

        require_once Kernel::ROOT_PATH . '/resources/simple_html_dom.php';

        $articlesData = [];
        foreach ($urlsToGrab as $url => &$status) {
            $status = 'parsing...';
            $this->outputStatusTable($urlsToGrab);
            try {
                $articlesData[] = $this->grabArticle($url);
                $status = 'finished';
            } catch (\Exception $e) {
                $status = 'ERROR: ' . $e->getMessage();
            }
            $this->outputStatusTable($urlsToGrab);
        }

        $objTmp = (object) array('aFlat' => array());
        array_walk_recursive($articlesData, function(&$value, $key, $object) {
            $object->aFlat[] = $value;
        }, $objTmp);

        $rows = str_repeat('(?,?,?,?,?),', count($articlesData) - 1) . '(?,?,?,?,?)';
        $sql = "INSERT INTO grabbed_articles (url, image_url, title, content, excerpt) VALUES $rows";
        $result = $this->db->prepare($sql);
        if (!$result) {
            print_r($this->db->errorInfo());
        }

        $result->execute($objTmp->aFlat);
    }

    protected function getNewArticles(string $page_content) : array
    {
        preg_match_all('~<a[^>]*data-yandex-name="from_news_feed"[^>]*>~msi', $page_content, $matches);

        $urls = [];
        foreach ($matches[0] as $match) {
            $href = substr($match, strpos($match, 'href="') + 6);
            $href_end = min(array_filter([strpos($href, '"'), strpos($href, '?')]));
            $href = substr($href, 0, $href_end);
            $urls[] = $href;
        }

        return $urls;
    }

    protected function filterArticles(array $articlesToFilter)
    {
        $urls = $articlesToFilter;

        $in  = str_repeat('?,', count($urls) - 1) . '?';
        $sql = "SELECT url FROM grabbed_articles WHERE url IN ($in)";
        $result = $this->db->prepare($sql);
        $result->execute($urls);
        $existingUrls = $result->fetchAll();

        print_r($existingUrls);
        return $articlesToFilter;
    }

    protected function grabArticle(string $url) : array
    {
        $res = $this->httpClient->request('GET', $url);

        $articleBody = $res->getBody();

        $articleData = [
            'url'       => $url,
            'image_url' => $this->parseArticleImageUrl($articleBody),
            'title'     => $this->parseArticleTitle($articleBody),
            'content'   => $this->parseArticleContent($articleBody),
        ];

        $articleData['excerpt'] = $this->generateExcerpt($articleData['content']);

        return $articleData;
    }

    protected function parseArticleImageUrl(string $articleBody) : ?string
    {
        $imageExists = preg_match('~<img[^>]*article__main-image__image[^>]*>~', $articleBody, $matches);
        if (! $imageExists || empty($matches)) {
            return null;
        }

        $src = substr($matches[0], strpos($matches[0], 'src="') + 5);
        $src_end = strpos($src, '"');
        $src = substr($src, 0, $src_end);
        return $src;
    }

    protected function parseArticleTitle(string $articleBody) : string
    {
        $articleTitleExists = preg_match('~<meta property="og:title" content="([^"]*)~', $articleBody, $matches);

        if (! $articleTitleExists || empty ($matches[1])) {
            throw new \Exception("Unable to find article title");
        }

        return $matches[1];
    }

    protected function parseArticleContent(string $articleBody) : string
    {
        // Remove HTML comments and spaces between tags
        $articleBody = preg_replace(
            ['~<!--[^>]*>~', '~>[\s]*<~'],
            ['', '><'],
            $articleBody
        );

        $articleContent = '';
        $html = new \simple_html_dom();
        $html->load($articleBody);

        foreach ($html->find('div.article__text') as $contentItem) {
            $this->filterArticleContentSection($contentItem);
            $articleContent .= $contentItem->innertext;
        }

        $html->clear();
        unset($html);

        $articleContent = trim($articleContent);
        if (mb_strlen($articleContent) < 10) {
            throw new \Exception('Unable to find article content');
        }

        return $articleContent;
    }

    protected function filterArticleContentSection(\simple_html_dom_node $contentItem)
    {
        // Removing garbage content
        $nodesToDelete = $contentItem->find(implode(', ', self::CONTENT_SELECTORS_BLACK_LIST));
        foreach ($nodesToDelete as $node) {
            $node->outertext = '';
        }

        // Removing links and leaving their text
        foreach ($contentItem->find('a') as $link) {
            $link->outertext = $link->plaintext;
        }
    }

    protected function generateExcerpt(string $fullContent) : string
    {
        $fullContent = strip_tags($fullContent);
        $fullContent = html_entity_decode($fullContent);

        $excerpt = mb_substr($fullContent, 0, self::EXCERPT_LENGHT);
        $charAfterCrop = mb_substr($fullContent, self::EXCERPT_LENGHT, 1);

        if (
            mb_strlen($excerpt) === self::EXCERPT_LENGHT
            && ! in_array($charAfterCrop, ['.', ' ', '-'])
        ) {
            $lastSpacePos = mb_strrpos($excerpt, ' ');
            $excerpt = mb_substr($excerpt, 0, $lastSpacePos);
        }
        return $excerpt;
    }

    protected function outputStatusTable(array $articles)
    {
        static $firstOutput = true;
        static $longestUrl;

        if (empty($longestUrl)) {
            $longestUrl = max(array_map('strlen', array_keys($articles)));
        }

        $outputObject = (object) array('lines' => array());
        array_walk($articles, function(&$value, $key, $object) use ($longestUrl) {
            $object->lines[] = (! $value) ? $key : sprintf("%-${longestUrl}s\t-\t%s", $key, $value);
        }, $outputObject);
        $output = implode(PHP_EOL, $outputObject->lines);

        if ($firstOutput) {
            $firstOutput = false;
        } else {
            $linesOffset = count($articles);
            echo chr(27) . "[0G";
            echo chr(27) . "[${linesOffset}A";
        }
        echo $output . PHP_EOL;
    }
}