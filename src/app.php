<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\Api;
use WeatherBot\Helper\WeatherClient;

$config = parse_ini_file('app_config.ini');

$token = getenv('TOKEN');
if (!$token) {
    $token = $config['token'];
}

$weatherApiToken = getenv('WEATHER_TOKEN');
if (!$weatherApiToken) {
    $weatherApiToken = $config['weather_api_token'];
}

$telegram = new Api($token);
$result = $telegram->getWebhookUpdates();
//$result = $telegram->getUpdates(['offset' => 719560554]);
//$result = $telegram->getUpdates(); // TODO
//$result = $result[0]; // TODO
$providedText = $result['message']['text'];
$chatId = $result['message']['chat']['id'];
$keyboard = [['Weather: Kyiv'], ['Weather: Kharkiv']];

if (!$providedText || $providedText === '/start') {
    $replyText = 'Make your choice';
    $replyMarkup = $telegram->replyKeyboardMarkup(
        ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
    );

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $replyMarkup]
    );

} elseif ($providedText === 'Weather: Kyiv') {
    $params = [
        WeatherClient::CITY_KEY => WeatherClient::CITY_KYIV,
        WeatherClient::APPID_KEY => $weatherApiToken
    ];

    $replyText = (new WeatherClient($params))->fetch();

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );
} elseif ($providedText === 'Weather: Kharkiv') {
    $params = [
        WeatherClient::CITY_KEY => WeatherClient::CITY_KHARKIV,
        WeatherClient::APPID_KEY => $weatherApiToken
    ];

    $replyText = (new WeatherClient($params))->fetch();

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );
}
