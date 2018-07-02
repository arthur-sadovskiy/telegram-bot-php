<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\Update;
use WeatherBot\Response;

abstract class AbstractHandler
{
    /**
     * @var Update
     */
    protected $telegramUpdate;

    /**
     * @var string
     */
    protected $weatherApiToken;

    /**
     * @var array
     */
    protected $redisClientConfig;

    /**
     * AbstractHandler constructor.
     * @param Update $telegramUpdate
     */
    public function __construct(Update $telegramUpdate)
    {
        $this->telegramUpdate = $telegramUpdate;
    }

    /**
     * @param string $weatherApiToken
     */
    public function setWeatherApiToken(string $weatherApiToken)
    {
        $this->weatherApiToken = $weatherApiToken;
    }

    /**
     * @param array $config
     */
    public function setRedisClientConfig(array $config)
    {
        $this->redisClientConfig = $config;
    }

    /**
     * @return array
     */
    public function getRedisClientConfig(): array
    {
        return $this->redisClientConfig;
    }

    /**
     * @return Response
     */
    abstract public function handle(): Response;
}
