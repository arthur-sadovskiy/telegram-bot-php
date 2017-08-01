<?php

namespace WeatherBot\Helper;

use GuzzleHttp\Client;

class WeatherClient
{
    const API_URL = 'http://api.openweathermap.org/data/2.5/forecast';

    const CITY_KEY = 'id';
    const CITY_KYIV = 703448;
    const CITY_KHARKIV = 706483;

    const TEMPERATURE_UNITS_FORMAT_KEY = 'units';
    const TEMPERATURE_CELSIUS = 'metric';

    const APPID_KEY = 'appid';

    /**
     * @var array
     */
    private $params = [];

    public function __construct(array $params)
    {
        if (!isset($params[self::TEMPERATURE_UNITS_FORMAT_KEY])) {
            $params[self::TEMPERATURE_UNITS_FORMAT_KEY] = self::TEMPERATURE_CELSIUS;
        }

        $this->params = $params;

        return $this;
    }

    public function fetch()
    {
        $url = $this->prepareUrl();

        $client = new Client();
        $response = $client->get(self::API_URL . $url);
        $weatherData = json_decode($response->getBody(), true);

        return $this->prepareData($weatherData);
    }

    private function prepareData(array $weatherData)
    {
        $processedData['cityName'] = $weatherData['city']['name'];
        $processedData['cityCountry'] = $weatherData['city']['country'];

        $processedData['details'] = array_slice($weatherData['list'], 0, 9);

        return json_encode($processedData);
    }

    private function prepareUrl()
    {
        $url = '';
        $delimiter = '?';

        foreach ($this->params as $key => $param) {
            $url .= $delimiter;
            if ($delimiter === '?') {
              $delimiter = '&';
            }

            $url .= "{$key}={$param}";
        }

        return $url;
    }
}
