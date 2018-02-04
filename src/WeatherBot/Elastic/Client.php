<?php

namespace WeatherBot\Elastic;

class Client
{
    private $params = [];

    private $defaultParams = [
        'host' => '172.17.0.2',
        'port' => 9200
    ];

    public function __construct(array $params = [])
    {
        $this->params = $params ?: $this->defaultParams;
    }

    public function create()
    {
        return new \Elastica\Client($this->params);
    }
}