<?php

/**
 * @author Vladimir Shapovalov <vovasn@gmail.com>
 */
class Request
{

    /**
     * @var array
     */
    private $params;
    /**
     * @var array
     */
    private $parsed;

    /**
     * Returns the command line arguments.
     * @return array
     */
    public function getParams()
    {
        if ($this->params === null) {
            if (isset($_SERVER['argv'])) {
                $this->params = $_SERVER['argv'];
                array_shift($this->params);
            } else {
                $this->params = [];
            }
        }
        return $this->params;
    }
    
    /**
     * Sets the command line arguments.
     * @param array $params the command line arguments
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }
    
        /**
     * Resolves the current request into a route and the associated parameters.
     * @return array the first element is the route, and the second is the associated parameters.
     * @throws Exception when parameter is wrong and can not be resolved
     */
    public function getParsed()
    {
        if ($this->parsed !== null) {
            return $this->parsed;
        }
        
        $rawParams = $this->getParams();
        $params = [];
        $prevOption = null;
        foreach ($rawParams as $param) {
            if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                $params[$name] = isset($matches[2]) ? $matches[2] : true;
                $prevOption = &$params[$name];
            } elseif (preg_match('/^-([\w-]+)(?:=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                $params[$name] = isset($matches[2]) ? $matches[2] : true;
            } elseif ($prevOption === true) {
                $prevOption = $param;
            } else {
                $params[] = $param;
            }
        }
        $this->parsed = $params;
        return $params;
    }

}
