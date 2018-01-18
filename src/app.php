<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\Api;
use WeatherBot\Helper\WeatherClient;
use Telegram\Bot\Keyboard\Keyboard;

$config = file_exists('app_config.ini')
    ? parse_ini_file('app_config.ini')
    : [];

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
$inlineKeyboard = Keyboard::make()->inline()->row(
    Keyboard::inlineButton(['text' => 'Kyiv', 'callback_data' => WeatherClient::CITY_KYIV]),
    Keyboard::inlineButton(['text' => 'Kharkiv', 'callback_data' => WeatherClient::CITY_KHARKIV])
);

if (isset($result['message'])) {
    $providedText = $result['message']['text'];
    if (!$providedText || $providedText === '/start') {
        $chatId = $result['message']['chat']['id'];
        $replyText = 'Choose your city';

        $telegram->sendMessage(
            ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $inlineKeyboard]
        );
    }
} elseif ($result->isType('callback_query')) {
    $callbackQuery = $result->getCallbackQuery();
    $callbackQueryId = $callbackQuery->getId();
    $callbackData = $callbackQuery->getData();
    $chatId = $callbackQuery->getFrom()->getId();

    $params = [
        WeatherClient::CITY_KEY => $callbackData,
        WeatherClient::APPID_KEY => $weatherApiToken
    ];

    $replyText = (new WeatherClient($params))->fetch();
    $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );

    $telegram->answerCallbackQuery(
        ['callback_query_id' => $callbackQueryId]
    );
}
