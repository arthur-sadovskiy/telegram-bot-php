<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\CallbackQuery;
use WeatherBot\{
    Helper\WeatherClient, InlineKeyboardTrait, RedisHelper, Response
};

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
            ->setIsDetailedForecast($isDetailed)
            ->setRedisHelper(
                (new RedisHelper())->createRedisClient($this->getRedisClientConfig())
            );

        $replyText = $weatherClient->fetch();
        $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

        return (new Response())
            ->setMessageParam(Response::CHAT_ID, $chatId)
            ->setMessageParam(Response::TEXT, $replyText)
            ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardRepeat($cityId, $isDetailed))
            ->setCallbackAnswerParam(Response::CALLBACK_QUERY_ID, $callbackQueryId);
    }
}