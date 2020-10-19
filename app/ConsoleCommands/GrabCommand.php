<?php


namespace App\ConsoleCommands;


use App\Core\DatabaseInterface;
use App\Core\Kernel;
use App\Repositories\PostRepository;
use GuzzleHttp\Client;

class GrabCommand
{
    const NAME = 'grab';

    const DESCRIPTION = 'Grabs 15 news posts from rbc.ru and saves them to DB';

    // URL of index page
    const URL_TO_GRAB = 'https://www.rbc.ru';

    // excerpt length limit for generation
    const EXCERPT_LENGHT = 200;

    // URLs with following subdomains will be skipped during grabbing
    const SUBDOMAINS_TO_SKIP = ['//traffic.rbc.ru', '//moneysend.rbc.ru', '//plus.rbc.ru'];

    // following elements will be removed from context blocks during parsing post pages
    const NODE_SELECTORS_TO_REMOVE = [
        'div.article__text__overview',
        'div.article__main-image',
        'div.article__inline-item',
        'div.article__clear',
        'div.article__ticker',
        'div.article__inline-video',
        'div.q-item__company',
        '.g-hidden',
        'div.js-news-video-stat',
        'div.banner',
        'div.pro-anons',
        'script'
    ];


    protected PostRepository $pr;

    protected $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;

        $this->pr = new PostRepository(Kernel::getService(DatabaseInterface::class));
    }

    public function handle()
    {
        $urlsToGrab = $this->getUrlsToGrab();
        $urlsToGrab = array_fill_keys($urlsToGrab, null);

        $numberOfUrls = count($urlsToGrab);
        if ($numberOfUrls == 0) {
            die('There is nothing to grab' . PHP_EOL);
        }
        echo "There are $numberOfUrls new posts to be grabbed:" . PHP_EOL;
        $this->outputStatusTable($urlsToGrab);

        require_once Kernel::ROOT_PATH . '/resources/libraries/simple_html_dom.php';

        $postsData = [];
        foreach ($urlsToGrab as $url => &$status) {
            if (! empty($status)) {
                continue;
            }

            $status = 'parsing...';
            $this->outputStatusTable($urlsToGrab);
            try {
                $postsData[] = $this->grabPost($url);
                $status = 'OK';
            } catch (\Exception $e) {
                $status = 'ERROR: ' . $e->getMessage();
            }
            $this->outputStatusTable($urlsToGrab);
        }

        $this->savePosts($postsData);
        echo 'Grabbing finished' . PHP_EOL;
    }

    /*
     * Gets posts URLs from index page
     */
    protected function getUrlsToGrab()
    {
        $res = $this->httpClient->request('GET', self::URL_TO_GRAB);

        $indexPageUrls = $this->parseIndexPageForUrls($res->getBody());
        return $this->filterUrls($indexPageUrls);
    }


    /**
     * Saves grabbed posts to DB
     *
     * @param array $postsData
     */
    protected function savePosts($postsData)
    {
        if (empty($postsData)) {
            die('There is nothing to save' . PHP_EOL);
        }

        echo 'Saving grabbed posts' . PHP_EOL;

        try {
            $this->pr->saveBulk($postsData);
        } catch (\Exception $e) {
            echo 'Error occurred while trying to save posts: ' . $e->getMessage() . PHP_EOL;
        }
    }


    /**
     * Parse index page content for links to posts
     *
     * @param string $page_content HTML content
     * @return array of parsed URLs
     */
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


    /**
     * Filters URLs parsed from index page for duplicates and black list of subdomains
     *
     * @param array $urlsToFilter
     * @return array
     */
    protected function filterUrls(array $urlsToFilter)
    {
        $urlsNumber = count($urlsToFilter);
        $existingPosts = $this->pr->findByUrl($urlsToFilter);
        $existingUrls = array_column($existingPosts, 'url');

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


    /**
     * Grabs post by its URL with parsing for required content
     *
     * @param string $url
     * @return array of post data
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function grabPost(string $url) : array
    {
        $res = $this->httpClient->request('GET', $url);

        $postBody = $res->getBody();

        $postData = [
            'url'       => $url,
            'image_url' => $this->parsePostImageUrl($postBody),
            'title'     => $this->parsePostTitle($postBody),
            'content'   => $this->parsePostContent($postBody),
        ];

        $postData['excerpt'] = $this->generateExcerpt($postData['content']);

        return $postData;
    }


    /**
     * Search for required img tag in post HTML page content and returns its src
     *
     * @param string $postBody
     * @return string|null image source URL or null if parsing was unsuccessful
     */
    protected function parsePostImageUrl(string $postBody) : ?string
    {
        $imageExists = preg_match('~<img[^>]*article__main-image__image[^>]*>~', $postBody, $matches);
        if (! $imageExists || empty($matches)) {
            return null;
        }

        $src = substr($matches[0], strpos($matches[0], 'src="') + 5);
        $src_end = strpos($src, '"');
        $src = substr($src, 0, $src_end);
        return $src;
    }

    /**
     * Parses post title from its HTML page content
     *
     * @param string $postBody
     * @return string
     * @throws \Exception In case the title wasn't found
     */
    protected function parsePostTitle(string $postBody) : string
    {
        $postTitleExists = preg_match('~<meta property="og:title" content="([^"]*)~', $postBody, $matches);

        if (! $postTitleExists || empty ($matches[1])) {
            throw new \Exception("Unable to find post title");
        }

        return $matches[1];
    }

    /**
     * Parses post content from its HTML page content
     * @param string $postBody
     * @return string
     * @throws \Exception In case the content wasn't found
     */
    protected function parsePostContent(string $postBody) : string
    {
        // Remove HTML comments and spaces between tags
        $postBody = preg_replace(
            ['~<!--[^>]*>~', '~>[\s]*<~'],
            ['', '><'],
            $postBody
        );

        $postContent = '';
        $html = new \simple_html_dom();
        $html->load($postBody);

        foreach ($html->find('div.article__text') as $contentItem) {
            $this->filterPostContentSection($contentItem);
            $postContent .= $contentItem->innertext;
        }

        $html->clear();
        unset($html);

        $postContent = trim($postContent);
        if (mb_strlen($postContent) < 10) {
            throw new \Exception('Unable to find post content');
        }

        return $postContent;
    }

    /**
     * Removes garbage sub-elements from post content nodes
     *
     * @param \simple_html_dom_node $contentItem
     */
    protected function filterPostContentSection(\simple_html_dom_node $contentItem)
    {
        // Removing garbage content
        $nodesToDelete = $contentItem->find(implode(', ', self::NODE_SELECTORS_TO_REMOVE));
        foreach ($nodesToDelete as $node) {
            $node->outertext = '';
        }

        // Removing links but leaving their text
        foreach ($contentItem->find('a') as $link) {
            $link->outertext = $link->plaintext;
        }
    }

    /**
     * Generates short excerpt from full post content
     *
     * @param string $fullContent
     * @return string
     * @throws \Exception   In case post content was suspiciously short.
     *                      Probably there was interactive or infografical post
     */
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

        $excerpt = trim($excerpt);
        if (empty($excerpt)) {
            throw new \Exception('Unable to generate excerpt. Insufficient of content');
        }

        return $excerpt;
    }

    /**
     * Outputs formatted table with posts URLs and their statuses.
     * Further output will update the table by rewriting it
     *
     * @param array $posts
     */
    protected function outputStatusTable(array $posts)
    {
        static $firstOutput = true;
        static $longestUrl;

        if (empty($longestUrl)) {
            $longestUrl = max(array_map('strlen', array_keys($posts)));
        }

        $outputObject = (object) array('lines' => array());
        array_walk($posts, function(&$value, $key, $object) use ($longestUrl) {
            $object->lines[] = (! $value) ? $key : (sprintf("%-${longestUrl}s\t-\t%s%s", $key, $value, chr(27) . "[K"));
        }, $outputObject);
        $output = implode(PHP_EOL, $outputObject->lines);

        if ($firstOutput) {
            $firstOutput = false;
        } else {
            $linesOffset = count($posts);
            echo chr(27) . "[0G";
            echo chr(27) . "[${linesOffset}A";
        }
        echo $output . PHP_EOL;
    }
}