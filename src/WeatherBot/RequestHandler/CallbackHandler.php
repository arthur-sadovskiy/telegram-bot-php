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

        $weatherClient = (new WeatherClient())
            ->setParam(WeatherClient::CITY_KEY, $callbackData)
            ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken);

        $replyText = $weatherClient->fetch();
        $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

        return (new Response())
            ->setMessageParam(Response::CHAT_ID, $chatId)
            ->setMessageParam(Response::TEXT, $replyText)
            ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardRepeat($callbackData))
            ->setCallbackAnswerParam(Response::CALLBACK_QUERY_ID, $callbackQueryId);
    }
}
