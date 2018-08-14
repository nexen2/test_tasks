<?php

require_once "Parser.php";

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class HtmlParser extends Parser
{

    /**
     * @inheritdoc
     */
    public static function check($url, $contentType = null)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (strncmp($contentType, 'text/htm', 8) === 0 || substr($path, -3) === 'htm' || substr($path, -4) === 'html') {
            return true;
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getSize($url)
    {
        parent::getFile($url);
        
        $cssUrls = $this->scanForUrls('/<link.+?href\s*=\s*["\'](.+?\.css)(?:\?.+)?["\']/i');
        $jsUrls = $this->scanForUrls('/<script.+?src\s*=\s*["\'](.+?\.js)(?:\?.+)?["\']/i');
        $imgUrls1 = $this->scanForUrls('/<img.+?src\s*=\s*["\'](http.+?)["\']/i');
        $imgUrls2 = $this->scanForUrls('/<img.+?srcset\s*=\s*["\'](http.+?),?\s+.*?["\']/i');
        
        $this->size += $this->getSizeMulti(array_merge($cssUrls, $jsUrls, $imgUrls1, $imgUrls2));
        
        return $this->size;
    }

}
