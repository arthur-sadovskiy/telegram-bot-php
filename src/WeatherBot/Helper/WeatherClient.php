<?php

namespace WeatherBot\Helper;

use GuzzleHttp\Client;
use WeatherBot\Emoji;

class WeatherClient
{
    public const APPID_KEY = 'appid';
    public const CITY_KEY = 'id';
    public const CITY_KYIV = 703448;
    public const CITY_KHARKIV = 706483;

    private const API_URL = 'http://api.openweathermap.org/data/2.5/forecast';
    private const TEMPERATURE_UNITS_FORMAT_KEY = 'units';
    private const TEMPERATURE_CELSIUS = 'metric';

    /**
     * @var array
     */
    private $params = [];

    /**
     * WeatherClient constructor.
     * @param array $params
     */
    public function __construct(array $params)
    {
        if (!isset($params[self::TEMPERATURE_UNITS_FORMAT_KEY])) {
            $params[self::TEMPERATURE_UNITS_FORMAT_KEY] = self::TEMPERATURE_CELSIUS;
        }

        $this->params = $params;
    }

    /**
     * @return string
     */
    public function fetch(): string
    {
        $urlParams = $this->prepareUrlParams();

        $client = new Client();
        $response = $client->get(self::API_URL . '?' . $urlParams);
        $weatherData = json_decode($response->getBody(), true);

        return $this->prepareData($weatherData);
    }

    /**
     * @param array $weatherData
     * @return string
     */
    private function prepareData(array $weatherData): string
    {
        $processedData = "Weather details in {$weatherData['city']['name']}, "
            . $weatherData['city']['country']
            . PHP_EOL . PHP_EOL;

        $weatherData = \array_slice($weatherData['list'], 0, 9);
        $emoji = new Emoji();
        foreach ($weatherData as $key => $data) {
            $dayCurrent = date('l, F j', strtotime($data['dt_txt']));
            $isNextDay = true;
            if ($key > 0) {
                $dayPrevious = date('l, F j', strtotime($weatherData[$key - 1]['dt_txt']));
                $isNextDay = $dayCurrent !== $dayPrevious;
            }
            $processedData .= ($isNextDay && ($key > 0)) ? PHP_EOL : '';
            $processedData .= $isNextDay ? $dayCurrent . PHP_EOL : '';
            $processedData .= date('H:i', strtotime($data['dt_txt'])) . ' - ';
            $processedData .= round($data['main']['temp']) . '°C, ';
            $weatherEmoji = $emoji->render($data['weather'][0]['description']);
            $processedData .= !empty($weatherEmoji)
                ? $weatherEmoji . ', '
                : $data['weather'][0]['description'] . ', ';
            $processedData .= 'wind ' . round(($data['wind']['speed'] * 18) / 5) . ' km/h';
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
}
