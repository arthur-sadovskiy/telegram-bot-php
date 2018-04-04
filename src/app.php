<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\Api;
use WeatherBot\Helper\WeatherClient;
use Telegram\Bot\Keyboard\Keyboard;
use WeatherBot\Elastic\Searcher;

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
//$result = $result[count($result) - 1]; // TODO

if (isset($result['message'])) {
    $providedText = $result['message']['text'];
    $chatId = $result['message']['chat']['id'];
    if (!$providedText || $providedText === '/start') {
        $replyText = 'Provide city name, for which you would like to get weather forecast.';

        $telegram->sendMessage(
            ['chat_id' => $chatId, 'text' => $replyText]
        );
    } elseif (is_string($providedText)) {
        $foundData = (new Searcher())->searchByName($providedText);

        if (count($foundData) === 1) {
            $params = [
                WeatherClient::CITY_KEY => $foundData[0]['id'],
                WeatherClient::APPID_KEY => $weatherApiToken
            ];

            $replyText = (new WeatherClient($params))->fetch();
            $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

            $telegram->sendMessage(
                ['chat_id' => $chatId, 'text' => $replyText]
            );
        } elseif (count($foundData) > 1) {
            $buttons = [];
            // TODO need to set 2-3 buttons per row..
            $inlineKeyboard = Keyboard::make()->inline();
            foreach ($foundData as $city) {
                $buttons[] = Keyboard::inlineButton([
                    'text' => "{$city['name']} ({$city['country']})",
                    'callback_data' => $city['id']
                ]);
                //$buttons[] = [$city['name'] . ', ' . $city['country']];
                if (count($buttons) === 2) {
                    $inlineKeyboard = call_user_func_array([$inlineKeyboard, 'row'], $buttons);
                    $buttons = [];
                }
            }

            $replyText = "We didn't find your city, but there are some very similar:";
            $telegram->sendMessage(
                ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $inlineKeyboard]
            );
        } else {
            $replyText = "We couldn't find your city :(";
            $telegram->sendMessage(
                ['chat_id' => $chatId, 'text' => $replyText]
            );
        }
    } else {
        $replyText = 'Some error happened :(';
        $telegram->sendMessage(
            ['chat_id' => $chatId, 'text' => $replyText]
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
