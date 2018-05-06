<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\CallbackQuery;
use WeatherBot\{Helper\WeatherClient, InlineKeyboardTrait, Response};

class CallbackHandler extends AbstractHandler
{
    use InlineKeyboardTrait;

    /**
     * @return Response
     */
    public function handle(): Response
    {
        /** @var CallbackQuery $callbackQuery */
        $callbackQuery = $this->telegramUpdate->getCallbackQuery();
        $callbackQueryId = $callbackQuery->getId();
        $callbackData = (int) $callbackQuery->getData();
        $chatId = $callbackQuery->getFrom()->getId();

        $params = [
            WeatherClient::CITY_KEY => $callbackData,
            WeatherClient::APPID_KEY => $this->weatherApiToken
        ];

        $replyText = (new WeatherClient($params))->fetch();
        $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

        return (new Response())
            ->setChatId($chatId)
            ->setText($replyText)
            ->setReplyMarkup($this->getInlineKeyboardRepeat($callbackData))
            ->setCallbackQueryId($callbackQueryId);
    }
}
