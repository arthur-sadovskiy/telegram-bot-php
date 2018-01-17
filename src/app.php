<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\Api;
use WeatherBot\Helper\WeatherClient;
use Telegram\Bot\Keyboard\Keyboard;

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
$result = $telegram->getWebhookUpdate();
//$result = $telegram->getUpdates(['offset' => 719560554]);
//$result = $telegram->getUpdates(); // TODO
//$result = $result[0]; // TODO
$providedText = $result['message']['text'];
$chatId = $result['message']['chat']['id'];
$keyboard = [['Kyiv'], ['Kharkiv']];

if (!$providedText || $providedText === '/start') {
    $replyText = 'Choose your city';
    $replyMarkup = Keyboard::make(
        ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
    );

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $replyMarkup]
    );

} elseif ($providedText === 'Kyiv') {
    $params = [
        WeatherClient::CITY_KEY => WeatherClient::CITY_KYIV,
        WeatherClient::APPID_KEY => $weatherApiToken
    ];

    $replyText = (new WeatherClient($params))->fetch();

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );
} elseif ($providedText === 'Kharkiv') {
    $params = [
        WeatherClient::CITY_KEY => WeatherClient::CITY_KHARKIV,
        WeatherClient::APPID_KEY => $weatherApiToken
    ];

    $replyText = (new WeatherClient($params))->fetch();

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );
}
