<?php

require_once 'HtmlParser.php';
require_once 'CssParser.php';

/**
 * Parser scans and parse remote files to get total size inluding all subresources
 * 
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class Parser
{

    static private $parsers = ['css', 'html'];
    
    static private $cache = [];
    
    static public $exclude = [];

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * Check this class can process this URL or content type
     * @param string $url
     * @param string $contentType
     * @return boolean
     */
    static function check($url, $contentType = null)
    {
        return false;
    }

    /**
     * Create a parser appropriate for this URL or content type
     * @return self|null
     */
    static function fabric($url, $contentType = null)
    {
        foreach (self::$parsers as $parser) {
            $className = $parser . 'Parser';
            if ($className::check($url, $contentType)) {
                return new $className;
            }
        }
    }

    /**
     * Downloads file by URL
     * @param string $url
     */
    protected function getFile($url)
    {
        $this->scheme = parse_url($url, PHP_URL_SCHEME);
        $this->host = parse_url($url, PHP_URL_HOST);
        
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, $this->scheme . '://' . $this->host . '/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:61.0) Gecko/20100101 Firefox/61.0');

        $this->data = curl_exec($ch);
        $this->size = strlen($this->data);
        $this->setCacheSize($url, $this->size);
        echo 'D ' . $url . ' - ' . $this->size . PHP_EOL;
        curl_close($ch);
    }
    
    /**
     * Gets headers for specific URL
     * @param string $url
     * @return array
     */
    private function getHeaders($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:61.0) Gecko/20100101 Firefox/61.0');

        $data = curl_exec($ch);
        $pos = strrpos($data, "\r\n\r\n", -5);
        if ($pos !== false) {
            $data = substr($data, $pos + 2);
        }
        curl_close($ch);
        
        return $data;
    }
    
    /**
     * Check cached size for specific URL
     * @param string $url
     * @return integer
     */
    protected function getCacheSize($url)
    {
        return isset(self::$cache[$url]) ? self::$cache[$url] : null;
    }
    
    /**
     * Remembers cached size
     * @param type $url
     * @param type $size
     * @return integer
     */
    protected function setCacheSize($url, $size)
    {
        self::$cache[$url] = $size;
        return $size;
    }

    /**
     * Calculate full size for specified file type
     * @param string $url
     * @return integer size in bytes
     */
    public function getSize($url)
    {
        if (count(self::$exclude)) {
            $path = parse_url($url, PHP_URL_PATH);
            foreach (self::$exclude as $exc) {
                $exc = trim($exc);
                $len = strlen($exc);
                if ($len && substr($path, -$len) === $exc) {
                    echo 'E ' . $url . PHP_EOL;
                    return 0;
                }
            }
        }
        
        if (!is_null($size = $this->getCacheSize($url))) {
            echo 'C ' . $url . ' - ' . $size . PHP_EOL;
            return $size;
        }
        $this->size = 0;
        $headers = $this->getHeaders($url);
        
        if (empty($headers)) {
            return $this->setCacheSize($url, 0);
        }
        
        if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/im", $headers, $matches)) {
            $status = $matches[1];
        }
        
        if ($status != 200 && !($status > 300 && $status <= 308)) {
            return $this->setCacheSize($url, 0);
        }
        
        if (preg_match('/^Content-Type: (.+)/im', $headers, $matches)) {
            $contentType = isset($matches[1]) ? $matches[1] : null;
        }
        
        $parser = self::fabric($url, $contentType);

        if ($parser) {
            return $parser->getSize($url);
        }
        
        if (preg_match('/^Content-Length: (\d+)/im', $headers, $matches)) {
            $this->size = isset($matches[1]) ? $matches[1] : 0;
            $this->setCacheSize($url, $this->size);
            echo 'H ' . $url . ' - ' . $this->size . PHP_EOL;
        } else {
            $this->getFile($url);
        }
        
        return $this->size;
    }
    
    /**
     * Scans text content for additional URLs
     * @param string $regular
     * @return array
     */
    public function scanForUrls($regular)
    {
        preg_match_all($regular, $this->data, $matches);
        
        $urls = array_pop($matches);
        
        foreach ($urls as $key => &$url) {
            $url = $this->fixUrl($url);
        }
        
        return array_filter($urls);
    }

    /**
     * Total size for a list of files
     * @param array $list
     * @return integer
     */
    public function getSizeMulti($list)
    {
        $size = 0;
        $parser = new Parser();
        foreach ($list as $element) {
            $size += $parser->getSize($element);
        }
        
        return $size;
    }

    /**
     * Change URL on same host as first request made
     * @param string $url
     * @return string
     */
    public function fixUrl($url)
    {
        if ($url[0] == '/') {
            if ($url[1] == '/') {
                return $this->scheme . ':' . $url;
            } else {
                return $this->scheme . '://' . $this->host . $url;
            }
        }
        return $url;
    }

}
