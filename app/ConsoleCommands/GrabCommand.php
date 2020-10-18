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

    const EXCERPT_LENGHT = 200;

    const SUBDOMAINS_TO_SKIP = ['//traffic.rbc.ru', '//moneysend.rbc.ru'];

    const NODE_SELECTORS_TO_REMOVE = [
        'div.article__text__overview',
        'div.article__main-image',
        'div.article__inline-item',
        'div.article__clear',
        'div.article__ticker',
        'div.article__inline-video',
        '.g-hidden',
        'div.js-news-video-stat',
        'div.banner',
        'div.pro-anons',
        'script'
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
        $urlsToGrab = $this->getUrlsToGrab();
        $urlsToGrab = array_fill_keys($urlsToGrab, null);

        $numberOfUrls = count($urlsToGrab);
        if ($numberOfUrls == 0) {
            die('There is nothing to grab' . PHP_EOL);
        }
        echo "There are $numberOfUrls new articles to be grabbed:" . PHP_EOL;
        $this->outputStatusTable($urlsToGrab);

        require_once Kernel::ROOT_PATH . '/resources/libraries/simple_html_dom.php';

        $articlesData = [];
        foreach ($urlsToGrab as $url => &$status) {
            if (! empty($status)) {
                continue;
            }

            $status = 'parsing...';
            $this->outputStatusTable($urlsToGrab);
            try {
                $articlesData[] = $this->grabArticle($url);
                $status = 'OK';
            } catch (\Exception $e) {
                $status = 'ERROR: ' . $e->getMessage();
            }
            $this->outputStatusTable($urlsToGrab);
        }

        $this->saveArticles($articlesData);
    }

    protected function getUrlsToGrab()
    {
        $res = $this->httpClient->request('GET', self::URL_TO_GRAB);

        $indexPageUrls = $this->parseIndexPageForUrls($res->getBody());
        return $this->filterUrls($indexPageUrls);
    }

    protected function saveArticles($articlesData)
    {
        if (empty($articlesData)) {
            die('There is nothing to save' . PHP_EOL);
        }

        echo 'Saving grabbed articles' . PHP_EOL;
        $objTmp = (object) array('aFlat' => array());
        array_walk_recursive($articlesData, function(&$value, $key, $object) {
            $object->aFlat[] = $value;
        }, $objTmp);

        $rows = str_repeat('(?,?,?,?,?),', count($articlesData) - 1) . '(?,?,?,?,?)';
        $sql = "INSERT INTO grabbed_articles (url, image_url, title, content, excerpt) VALUES $rows";

        try {
            $result = $this->db->prepare($sql);
            $result->execute($objTmp->aFlat);
        } catch (\Exception $e) {
            echo 'Error occurred while trying to save articles: ' . $e->getMessage() . PHP_EOL;
        }
    }

    protected function parseIndexPageForUrls(string $page_content) : array
    {
        preg_match_all('~<a[^>]*data-yandex-name="from_news_feed"[^>]*>~msi', $page_content, $matches);

        $urls = [];
        foreach ($matches[0] as $match) {
            $href = substr($match, strpos($match, 'href="') + 6);
            $href_end = min(array_filter([strpos($href, '"'), strpos($href, '?')]));
            $href = substr($href, 0, $href_end);
            $urls[] = $href;
        }

        echo count($urls) . ' URLs was found on index page' . PHP_EOL;

        return $urls;
    }

    protected function filterUrls(array $urlsToFilter)
    {
        $urlsNumber = count($urlsToFilter);

        $in  = str_repeat('?,', count($urlsToFilter) - 1) . '?';
        $sql = "SELECT url FROM grabbed_articles WHERE url IN ($in)";
        $result = $this->db->prepare($sql);
        $result->execute($urlsToFilter);
        $existingUrls = $result->fetchAll();

        $filteredUrls = array_diff($urlsToFilter, $existingUrls);

        foreach ($filteredUrls as $key => $urlToFilter) {
            foreach (self::SUBDOMAINS_TO_SKIP as $subdomainToSkip) {
                if (strstr($urlToFilter, $subdomainToSkip)) {
                    unset($filteredUrls[$key]);
                    continue 2;
                }
            }
        }

        $skippedUrlsNumber = $urlsNumber - count($filteredUrls);
        if ($skippedUrlsNumber > 0) {
            echo "$skippedUrlsNumber URLs were skipped" . PHP_EOL;
        }

        return $filteredUrls;
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
        $nodesToDelete = $contentItem->find(implode(', ', self::NODE_SELECTORS_TO_REMOVE));
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
        // Add spaces between tags before stripping them to avoid <h1>Foo</h1>Bar >>> FooBar
        $fullContent = str_replace('<', ' <', $fullContent);
        $fullContent = strip_tags($fullContent);
        $fullContent = str_replace('  ', ' ', $fullContent);
        $fullContent = html_entity_decode($fullContent);

        $excerpt = mb_substr($fullContent, 0, self::EXCERPT_LENGHT);
        $charAfterCrop = mb_substr($fullContent, self::EXCERPT_LENGHT, 1);

        // Gentle cut text for excerpt, without cropping words
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
            $object->lines[] = (! $value) ? $key : (sprintf("%-${longestUrl}s\t-\t%s%s", $key, $value, chr(27) . "[K"));
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