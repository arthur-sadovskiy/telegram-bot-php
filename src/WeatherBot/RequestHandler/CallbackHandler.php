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
        $callbackData = $callbackQuery->getData();
        $callbackDataParts = explode('##', $callbackData);
        $cityId = (int) $callbackDataParts[0];
        $isDetailed = strpos($callbackData, '##') !== false
            ? (bool) $callbackDataParts[1]
            : true;
        $chatId = $callbackQuery->getFrom()->getId();

        $weatherClient = (new WeatherClient())
            ->setParam(WeatherClient::CITY_KEY, $cityId)
            ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken)
            ->setIsDetailedForecast($isDetailed);

        $replyText = $weatherClient->fetch();
        $replyText .= PHP_EOL . PHP_EOL;
        $replyText .= 'To get the weather forecast for the same city use one of the buttons below.';
        $replyText .= PHP_EOL;
        $replyText .= 'Or provide a new city name / send another location!';

        return (new Response())
            ->setMessageParam(Response::CHAT_ID, $chatId)
            ->setMessageParam(Response::TEXT, $replyText)
            ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardRepeat($cityId, $isDetailed))
            ->setCallbackAnswerParam(Response::CALLBACK_QUERY_ID, $callbackQueryId);
    }
}
