<?php

require "../Request.php";

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class XMLGenerator
{

    // abstract max entities count
    CONST COUNT_MAX = 2000000000;
    // text length generated 
    CONST RANDOM_TEXT_LENGTH = 5000;

    /**
     * @var array configuration parameters
     */
    private $params = [];
    private $defaultParams = [
        'h' => false,
        'b' => false,
        'count' => 1000,
        'of' => false,
    ];

    /**
     * @var resource output file handler
     */
    private $file;

    /**
     * @var string entry template
     */
    private $template = <<< STR
    <user>
        <id>{id}</id>
        <name>{username}</name>
        <email>{email}</mail>
        <age>{age}</age>
        <text>{random}<text>
    </user>
STR;

    /**
     * @var array symbols list to be inserted to template with attempt to break it
     */
    private $garbage = [
        '<', '>', '\\', '/',
    ];

    /**
     * Loads line parameters and checks it
     */
    private function checkConfig()
    {
        $request = new Request();
        $this->params = array_merge($this->defaultParams, $request->getParsed());

        if ($this->params['h']) {
            echo 'Test XML file generator. You can use this options:' . PHP_EOL;
            echo '  -h          - This help' . PHP_EOL;
            echo '  -b          - Broken structure' . PHP_EOL;
            echo '  --count=??? - Entries count, ' . self::COUNT_MAX . ' is max' . PHP_EOL;
            echo '  --of=???    - Output file name' . PHP_EOL;
            die;
        }

        if ($this->params['of'] && file_exists($this->params['of']) && !is_writable($this->params['of'])) {
            echo 'Output file is not writable!' . PHP_EOL;
            die(1);
        }

        $this->params['count'] = intval($this->params['count']);

        if (empty($this->params['count']) || !is_integer($this->params['count'])) {
            echo 'Entries count is wrong!' . PHP_EOL;
            die(2);
        }

        $this->params['count'] = min($this->params['count'], self::COUNT_MAX);
    }

    /**
     * Initializes output and opens main tag
     */
    private function start()
    {
        if ($this->params['of']) {
            $this->file = fopen($this->params['of'], 'w');
        } else {
            $this->file = fopen('php://stdout', 'w');
        }

        if ($this->file === FALSE) {
            echo 'Output error!' . PHP_EOL;
            die(3);
        }

        fwrite($this->file, '<users>' . PHP_EOL);
    }

    /**
     * Closes output and main tag
     */
    private function end()
    {
        fwrite($this->file, '</users>' . PHP_EOL);

        fclose($this->file);
    }

    /**
     * Adding random garbage symbols to XML template
     * @param string $string
     * @return string
     */
    private function addGarbage($string)
    {
        $insert = $this->garbage[mt_rand(0, count($this->garbage) - 1)];
        $pos = rand(0, strlen($string) - 1);
        return substr_replace($string, $insert, $pos, 0);
    }

    /**
     * Entry text generation
     * @return string
     */
    private function generateEntry()
    {
        $randomText = preg_replace('/[^a-zA-Z0-9]/', '', random_bytes(self::RANDOM_TEXT_LENGTH));

        $replace = [
            '{id}' => mt_rand(0, self::COUNT_MAX - 1),
            '{username}' => substr($randomText, 0, 10),
            '{email}' => substr($randomText, 0, 10) . '@' . substr($randomText, 10, 10) . '.com',
            //who can beat a bet?
            '{age}' => mt_rand(1, 120),
            '{random}' => $randomText,
        ];

        //Check is it should be broken
        if ($this->params['b']) {
            //tring to break it with special symbols
            $template = $this->addGarbage($this->template);
        } else {
            $template = $this->template;
        }

        return strtr($template, $replace);
    }

    /**
     * Main method
     */
    public function run()
    {
        $this->checkConfig();
        $this->start();

        for ($i = 0; $i < $this->params['count']; $i++) {
            $entry = $this->generateEntry();
            fwrite($this->file, $entry . PHP_EOL);
        }

        $this->end();
    }

}

(new XMLGenerator())->run();
