<?php

require "../Request.php";

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class Parser
{

    /**
     * @var array configuration parameters
     */
    private $params = [];
    private $defaultParams = [
        'h' => false,
        'age-min' => '20',
        'age-max' => '30',
        'if' => 'users.xml',
        'of' => 'out.xml',
    ];

    /**
     * @var resource input file handler
     */
    private $fileIn;

    /**
     * @var resource output file handler
     */
    private $fileOut;

    /**
     * Loads line parameters and checks it
     */
    private function checkConfig()
    {
        $request = new Request();
        $this->params = array_merge($this->defaultParams, $request->getParsed());

        if ($this->params['h']) {
            echo 'Users XML file parse. You can use this options:' . PHP_EOL;
            echo '  -h            - This help' . PHP_EOL;
            echo '  --age-min=??? - Filter age from, 20 default' . PHP_EOL;
            echo '  --age-max=??? - Filter age to, 30 default' . PHP_EOL;
            echo '  --if=???      - Input file name, "users.xml" is default' . PHP_EOL;
            echo '  --of=???      - Output file name, "out.xml" is default' . PHP_EOL;
            die;
        }

        if (empty($this->params['if'])) {
            echo 'Input file is not set!' . PHP_EOL;
            die(1);
        }

        if (!file_exists($this->params['if']) || !is_readable($this->params['if'])) {
            echo 'Input file is not readable!' . PHP_EOL;
            die(2);
        }

        if ($this->params['of'] && file_exists($this->params['of']) && !is_writable($this->params['of'])) {
            echo 'Output file is not writable!' . PHP_EOL;
            die(3);
        }
    }

    /**
     * Initializes input and opens main tag
     */
    private function start()
    {
        $this->fileIn = fopen($this->params['if'], 'r');

        if ($this->fileIn === FALSE) {
            echo 'Output error!' . PHP_EOL;
            die(4);
        }

        if ($this->params['of']) {
            $this->fileOut = fopen($this->params['of'], 'w');
        } else {
            $this->fileOut = fopen('php://stdout', 'w');
        }

        if ($this->fileOut === FALSE) {
            echo 'Output error!' . PHP_EOL;
            die(5);
        }

        fwrite($this->fileOut, '<users>' . PHP_EOL);
    }

    /**
     * Closes input and main tag
     */
    private function end()
    {
        fclose($this->fileIn);

        fwrite($this->fileOut, '</users>' . PHP_EOL);

        fclose($this->fileOut);
    }

    /**
     * Main method
     */
    public function run()
    {
        $this->checkConfig();
        $this->start();

        $tail = '';
        // reading blocks by 1M each
        $blockSize = 1024 * 1024;

        while (!feof($this->fileIn)) {
            $fileContent = $tail . fread($this->fileIn, $blockSize);

            $parts = preg_split('/<+user>+/is', $fileContent);
            array_pop($parts);

            $totalLength = 0;
            foreach ($parts as $part) {
                $totalLength += strlen($part);
                preg_match('/<+age>+(.*?)<+\/+age>+/is', $part, $match);

                if (!empty($match[1]) && intval($match[1]) >= $this->params['age-min'] && intval($match[1]) <= $this->params['age-max']) {
                    fwrite($this->fileOut, '<user>' . $part);
                }
            }

            $tail = substr($fileContent, $totalLength);
        }

        $this->end();
    }

}

(new Parser())->run();
