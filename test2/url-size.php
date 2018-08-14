<?php

require "../Request.php";
require "Parser.php";

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class GetUrlSize
{

    /**
     * @var array configuration parameters
     */
    private $params = [];
    private $defaultParams = [
        'h' => false,
        'exc' => [],
    ];

    /**
     * Loads line parameters and checks it
     */
    private function checkConfig()
    {
        $request = new Request();
        $this->params = array_merge($this->defaultParams, $request->getParsed());

        if (empty($this->params[0]) || $this->params['h']) {
            echo 'Url size checker. Lists all subresources.' . PHP_EOL;
            echo 'Url marks:' . PHP_EOL;
            echo '  H - Size got from headers' . PHP_EOL;
            echo '  В - Size got from direct download' . PHP_EOL;
            echo '  С - Size got from cache' . PHP_EOL;
            echo '  E - File excluded' . PHP_EOL;
            echo 'You can use this options:' . PHP_EOL;
            echo '  <first argument> - Url' . PHP_EOL;
            echo '  -h               - This help' . PHP_EOL;
            echo '  --exc=???        - Coma separated file extensions for exclusion' . PHP_EOL;
            die;
        }
    }

    /**
     * Main method
     */
    public function run()
    {
        $this->checkConfig();

        $url = $this->params[0];
        if (strncmp($url, 'http', 4) !== 0) {
            $url = 'http://' . $url;
        }
        
        if ($this->params['exc']) {
            Parser::$exclude = explode(',', $this->params['exc']);
        }
        
        $parser = new Parser();
        
        echo 'Size is: ' . $parser->getSize($url) . PHP_EOL;
    }

}

(new GetUrlSize())->run();
