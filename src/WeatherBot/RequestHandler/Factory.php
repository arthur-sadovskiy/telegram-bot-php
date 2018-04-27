<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\Update;

class Factory
{
    /**
     * @param Update $telegramUpdate
     *
     * @return null|AbstractHandler
     */
    public function getHandlerObject(Update $telegramUpdate): ?AbstractHandler
    {
        $handler = null;
        if ($telegramUpdate->isType('message')) {
            $handler = new MessageHandler($telegramUpdate);
        } elseif ($telegramUpdate->isType('callback_query')) {
            $handler = new CallbackHandler($telegramUpdate);
        }

        return $handler;
    }
}
