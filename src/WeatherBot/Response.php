<?php

namespace WeatherBot;

class Response
{
    public const CHAT_ID = 'chat_id';
    public const TEXT = 'text';
    public const REPLY_MARKUP = 'reply_markup';
    public const CALLBACK_QUERY_ID = 'callback_query_id';

    /**
     * @var array
     */
    private $messageParams;

    /**
     * @var array
     */
    private $callbackAnswerParams;

    /**
     * @param string $key
     * @param string $value
     *
     * @return Response
     */
    public function setMessageParam(string $key, string $value): Response
    {
        $this->messageParams[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessageParams(): array
    {
        return $this->messageParams;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return Response
     */
    public function setCallbackAnswerParam(string $key, string $value): Response
    {
        $this->callbackAnswerParams[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCallbackAnswerParams(): array
    {
        return $this->callbackAnswerParams;
    }
}
