<?php

include('../vendor/autoload.php');
use Telegram\Bot\Api;

$token = getenv('TOKEN');
if (!$token) {
    $config = parse_ini_file('app_config.ini');
    $token = $config['token'];
}

$telegram = new Api($token);
$result = $telegram->getWebhookUpdates();

$providedText = $result['message']['text'];
$chatId = $result['message']['chat']['id'];
$keyboard = [['Weather: Kyiv']];

if (!$providedText) {
    $replyText = 'Make your choice';
    $replyMarkup = $telegram->replyKeyboardMarkup(
        ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
    );

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText, 'reply_markup' => $replyMarkup]
    );

} elseif ($providedText === 'Weather: Kyiv') {
    $replyText = '25 С';

    $telegram->sendMessage(
        ['chat_id' => $chatId, 'text' => $replyText]
    );
}
