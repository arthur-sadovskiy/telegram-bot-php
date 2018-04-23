<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\{Api, Keyboard\Keyboard};
use WeatherBot\{Config, Elastic\Searcher, Helper\WeatherClient};

$config = new Config();
$weatherApiToken = $config->get('weather_api_token');

$telegram = new Api($config->get('telegram_api_token'));
$result = $telegram->getWebhookUpdate();
////$result = $telegram->getUpdates(['offset' => 719560554]);
//$result = $telegram->getUpdates(); // TODO
//$result = $result[0]; // TODO
////$result = $result[count($result) - 1]; // TODO

if (isset($result['message'])) {
    $providedText = $result['message']['text'] ?? null;
    $chatId = $result['message']['chat']['id'];

    $client = new \Elastica\Client([
        'host' => $config->get('elastic_host'),
        'port' => $config->get('elastic_port')
    ]);

    /** @var Telegram\Bot\Objects\Location $location */
    $location = $result['message']['location'] ?? null;
    if (null !== $location) {
        $userLocation = [
            'lat' => $location->getLatitude(),
            'lon' => $location->getLongitude()
        ];

        $foundData = (new Searcher($client))->searchByLocation($userLocation);
        $cityId = $foundData[0]['id'];

        $responseMessageParams['chat_id'] = $chatId;

        if (count($foundData) === 1) {
            $params = [
                WeatherClient::CITY_KEY => $cityId,
                WeatherClient::APPID_KEY => $weatherApiToken
            ];

            $replyText = (new WeatherClient($params))->fetch();
            $replyText .= PHP_EOL . PHP_EOL;
            $replyText .= 'Type "/start" to see menu or provide your location for immediate weather forecast';
            $responseMessageParams['text'] = $replyText;

            $inlineKeyboard = Keyboard::make()
                ->inline()
                ->row(
                    Keyboard::inlineButton([
                        'text' => 'Repeat last request',
                        'callback_data' => $cityId
                    ])
                );

            $responseMessageParams['reply_markup'] = $inlineKeyboard;
        } else {
            $replyText = "We couldn't find your city :(";
            $responseMessageParams['text'] = $replyText;
        }

        $telegram->sendMessage($responseMessageParams);

    } elseif (!$providedText || $providedText === '/start') {
        $replyText = 'Provide city name, for which you would like to get weather forecast.' . PHP_EOL;
        $replyText .= 'Or just send your location!';

        $telegram->sendMessage(
            ['chat_id' => $chatId, 'text' => $replyText]
        );
    } elseif (is_string($providedText)) {
        $foundData = (new Searcher($client))->searchByName($providedText);

        if (count($foundData) === 1) {
            $cityId = $foundData[0]['id'];
            $params = [
                WeatherClient::CITY_KEY => $cityId,
                WeatherClient::APPID_KEY => $weatherApiToken
            ];

            $replyText = (new WeatherClient($params))->fetch();
            $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

            $inlineKeyboard = Keyboard::make()
                ->inline()
                ->row(
                    Keyboard::inlineButton([
                        'text' => 'Repeat last request',
                        'callback_data' => $cityId
                    ])
                );

            $responseMessageParams['chat_id'] = $chatId;
            $responseMessageParams['text'] = $replyText;
            $responseMessageParams['reply_markup'] = $inlineKeyboard;

            $telegram->sendMessage($responseMessageParams);
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

    $inlineKeyboard = Keyboard::make()
        ->inline()
        ->row(
            Keyboard::inlineButton([
                'text' => 'Repeat last request',
                'callback_data' => $callbackData
            ])
        );

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $inlineKeyboard]
    );

    $telegram->answerCallbackQuery(
        ['callback_query_id' => $callbackQueryId]
    );
}
