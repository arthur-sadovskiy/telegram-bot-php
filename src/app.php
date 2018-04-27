<?php

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use Telegram\Bot\Api;
use WeatherBot\Config;
use WeatherBot\RequestHandler\{Factory, MessageHandler, CallbackHandler};

$config = new Config();

$telegram = new Api($config->get('telegram_api_token'));
$telegramUpdate = $telegram->getWebhookUpdate();

$handler = (new Factory())->getHandlerObject($telegramUpdate);
$handler->setWeatherApiToken($config->get('weather_api_token'));
if ($handler instanceof MessageHandler) {
    $client = new \Elastica\Client([
        'host' => $config->get('elastic_host'),
        'port' => $config->get('elastic_port')
    ]);

    $handler->setElasticaClient($client);
}

$response = $handler->handle();
$telegram->sendMessage($response['main']);
if ($handler instanceof CallbackHandler) {
    $telegram->answerCallbackQuery($response['callback']);
}
