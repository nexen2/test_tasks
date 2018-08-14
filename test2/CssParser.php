<?php

require_once "Parser.php";

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class CssParser extends Parser
{
    
    protected $basePath;

    /**
     * @inheritdoc
     */
    public static function check($url, $contentType = null)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (strncmp($contentType, 'text/css', 8) === 0 || substr($path, -3) === 'css') {
            return true;
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getSize($url)
    {
        parent::getFile($url);
        
        $this->basePath = dirname($url);
        
        $urls = $this->scanForUrls('/url\s*\(\s*["\']?(.+?)["\']?\s*\)/i');
        $this->size += $this->getSizeMulti($urls);
        
        return $this->size;
    }
    
    /**
     * Change URL on same host as first request made
     * @param string $url
     * @return string
     */
    public function fixUrl($url)
    {
        //clear inline data URLs
        if (strncmp($url, 'data:', 5) === 0) {
            return null;
        }
        if ($url[0] == '/') {
            if ($url[1] == '/') {
                return $this->scheme . ':' . $url;
            } else {
                return $this->scheme . '://' . $this->host . $url;
            }
        } else {
            return $this->basePath . '/' . $url;
        }
        return $url;
    }

}
