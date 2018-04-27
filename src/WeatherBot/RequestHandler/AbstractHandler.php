<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\Update;

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
     * @return array
     */
    abstract public function handle(): array;
}
