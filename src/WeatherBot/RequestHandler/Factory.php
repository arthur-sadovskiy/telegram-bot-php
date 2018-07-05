<?php

namespace WeatherBot\RequestHandler;

use Telegram\Bot\Objects\Update;

class Factory
{
    /**
     * @param Update $telegramUpdate
     *
     * @throws \UnexpectedValueException
     *
     * @return AbstractHandler
     */
    public function getHandlerObject(Update $telegramUpdate): AbstractHandler
    {
        if ($telegramUpdate->isType('message')) {
            $handler = new MessageHandler($telegramUpdate);
        } elseif ($telegramUpdate->isType('callback_query')) {
            $handler = new CallbackHandler($telegramUpdate);
        } else {
            throw new \UnexpectedValueException('Unknown type of telegram update.');
        }

        return $handler;
    }
}
