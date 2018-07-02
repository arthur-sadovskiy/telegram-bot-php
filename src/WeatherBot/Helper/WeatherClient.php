<?php

namespace WeatherBot\Helper;

use GuzzleHttp\{Client, Exception\ServerException};
use WeatherBot\Emoji;
use WeatherBot\RedisHelper;

class WeatherClient
{
    public const APPID_KEY = 'appid';
    public const CITY_KEY = 'id';

    private const API_URL = 'http://api.openweathermap.org/data/2.5/forecast';
    private const API_URL_DAILY = 'http://api.openweathermap.org/data/2.5/forecast/daily';
    private const TEMPERATURE_UNITS_FORMAT_KEY = 'units';
    private const TEMPERATURE_CELSIUS = 'metric';

    /**
     * @var array
     */
    private $params;

    /**
     * @var bool
     */
    private $isDetailedForecast = true;

    /**
     * @var RedisHelper
     */
    private $redisHelper;

    /**
     * @var int
     */
    private $ttl;

    /**
     * WeatherClient constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (!isset($params[self::TEMPERATURE_UNITS_FORMAT_KEY])) {
            $params[self::TEMPERATURE_UNITS_FORMAT_KEY] = self::TEMPERATURE_CELSIUS;
        }

        $this->params = $params;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setParam(string $key, string $value): WeatherClient
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param bool $isDetailedForecast
     *
     * @return $this
     */
    public function setIsDetailedForecast(bool $isDetailedForecast): WeatherClient
    {
        $this->isDetailedForecast = $isDetailedForecast;

        return $this;
    }

    /**
     * @param RedisHelper $helper
     *
     * @return $this
     */
    public function setRedisHelper(RedisHelper $helper): WeatherClient
    {
        $this->redisHelper = $helper;

        return $this;
    }

    /**
     * @return string
     */
    public function fetch(): string
    {
        $redisClient = $this->redisHelper->getRedisClient();
        $redisKey = $this->redisHelper->getRedisKey(
            $this->params[self::CITY_KEY],
            $this->isDetailedForecast
        );
        if (null !== $redisClient && $redisClient->exists($redisKey)) {
            return $redisClient->get($redisKey);
        }

        try {
            $url = $this->isDetailedForecast ? self::API_URL : self::API_URL_DAILY;
            $urlParams = $this->prepareUrlParams();
            $response = (new Client())->get($url . '?' . $urlParams);
            $weatherData = json_decode($response->getBody(), true);
            $preparedData = $this->prepareData($weatherData);

            if (null !== $redisClient) {
                $valueForRedis = $this->redisHelper->prependCacheSign($preparedData);
                $redisClient->setex($redisKey, $this->ttl, $valueForRedis);
                $redisClient->close();
            }

            return $preparedData;
        } catch (ServerException $e) {
            // handle 500 level errors
            return "Some issue has happened with my weather provider :'(";
        }
    }

    /**
     * @param array $weatherData
     *
     * @return string
     */
    private function prepareData(array $weatherData): string
    {
        $processedData = "Weather details in {$weatherData['city']['name']}, "
            . $weatherData['city']['country']
            . PHP_EOL . PHP_EOL;

        $weatherData = $this->getWeatherDataList($weatherData);
        $this->ttl = $this->getTtl($weatherData);
        $emoji = new Emoji();
        foreach ($weatherData as $key => $data) {
            $dayCurrent = date('l, F j', $this->getCurrentDayTimestamp($data));
            $isNextDay = true;
            if ($key > 0) {
                $dayPrevious = date('l, F j', $this->getPreviousDayTimestamp($weatherData, $key));
                $isNextDay = $dayCurrent !== $dayPrevious;
            }
            $processedData .= ($isNextDay && ($key > 0)) ? PHP_EOL : '';
            $processedData .= $isNextDay ? $dayCurrent . PHP_EOL : '';
            if ($this->isDetailedForecast) {
                $processedData .= date('H:i', strtotime($data['dt_txt'])) . '  ';
            }
            $processedData .= $this->getTemperature($data);
            $weatherEmoji = $emoji->render($data['weather'][0]['description']);
            $processedData .= !empty($weatherEmoji)
                ? $weatherEmoji . '  '
                : $data['weather'][0]['description'] . '  ';
            $processedData .= 'wind ' . round(($this->getWindSpeed($data) * 18) / 5) . ' km/h';
            $processedData .= PHP_EOL;
        }

        return $processedData;
    }

    /**
     * @return string
     */
    private function prepareUrlParams(): string
    {
        return http_build_query($this->params);
    }

    /**
     * @param array $weatherData
     *
     * @return array
     */
    private function getWeatherDataList(array $weatherData): array
    {
        return $this->isDetailedForecast
            ? \array_slice($weatherData['list'], 0, 9)
            : $weatherData['list'];
    }

    /**
     * @param array $weatherData
     *
     * @return int
     */
    private function getCurrentDayTimestamp(array $weatherData): int
    {
        return $this->isDetailedForecast
            ? strtotime($weatherData['dt_txt'])
            : $weatherData['dt'];
    }

    /**
     * @param array $weatherData
     * @param int $key
     *
     * @return int
     */
    private function getPreviousDayTimestamp(array $weatherData, int $key): int
    {
        return $this->isDetailedForecast
            ? strtotime($weatherData[$key - 1]['dt_txt'])
            : $weatherData[$key - 1]['dt'];
    }

    /**
     * @param array $weatherData
     *
     * @return string
     */
    private function getTemperature(array $weatherData): string
    {
        if ($this->isDetailedForecast) {
            $temperatureData = round($weatherData['main']['temp']) . '°C  ';
        } else {
            $temperatureData = round($weatherData['temp']['min']) . '°C';
            $temperatureData .= ' .. ';
            $temperatureData .= round($weatherData['temp']['max']) . '°C  ';
        }

        return $temperatureData;
    }

    /**
     * @param array $weatherData
     *
     * @return float
     */
    private function getWindSpeed(array $weatherData): float
    {
        return $this->isDetailedForecast
            ? $weatherData['wind']['speed']
            : $weatherData['speed'];
    }

    /**
     * @param array $weatherData
     *
     * @return int
     */
    private function getTtl(array $weatherData): int
    {
        $upcomingTime = $this->isDetailedForecast
            ? strtotime($weatherData[1]['dt_txt'])
            : $weatherData[1]['dt'];

        return $upcomingTime - time();
    }
}
