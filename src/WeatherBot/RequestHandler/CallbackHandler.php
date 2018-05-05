<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\{Keyboard\Keyboard, Objects\CallbackQuery};
use WeatherBot\{Helper\WeatherClient, Response};

class CallbackHandler extends AbstractHandler
{
    /**
     * @return Response
     */
    public function handle(): Response
    {
        /** @var CallbackQuery $callbackQuery */
        $callbackQuery = $this->telegramUpdate->getCallbackQuery();
        $callbackQueryId = $callbackQuery->getId();
        $callbackData = $callbackQuery->getData();
        $chatId = $callbackQuery->getFrom()->getId();

        $params = [
            WeatherClient::CITY_KEY => $callbackData,
            WeatherClient::APPID_KEY => $this->weatherApiToken
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

        return (new Response())
            ->setChatId($chatId)
            ->setText($replyText)
            ->setReplyMarkup($inlineKeyboard)
            ->setCallbackQueryId($callbackQueryId);
    }
}
