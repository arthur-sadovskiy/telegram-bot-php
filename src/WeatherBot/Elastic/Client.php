<?php

namespace WeatherBot\Elastic;

class Client
{
    private $params;

    private $defaultParams = [
        'host' => '127.0.0.1',
        'port' => 9200
    ];

    /**
     * Client constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params ?: $this->defaultParams;
    }

    /**
     * @return \Elastica\Client
     */
    public function create(): \Elastica\Client
    {
        return new \Elastica\Client($this->params);
    }
}
