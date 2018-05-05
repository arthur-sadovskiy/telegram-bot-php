<?php

namespace WeatherBot;

class Response
{
    /**
     * @var array
     */
    private $messageParams;

    /**
     * @var array
     */
    private $callbackAnswerParams;

    /**
     * @param int $chatId
     *
     * @return Response
     */
    public function setChatId(int $chatId): Response
    {
        $this->messageParams['chat_id'] = $chatId;

        return $this;
    }

    /**
     * @param string $text
     *
     * @return Response
     */
    public function setText(string $text): Response
    {
        $this->messageParams['text'] = $text;

        return $this;
    }

    /**
     * @param string $replyMarkup
     *
     * @return Response
     */
    public function setReplyMarkup(string $replyMarkup): Response
    {
        $this->messageParams['reply_markup'] = $replyMarkup;

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
     * @param string $callbackQueryId
     *
     * @return Response
     */
    public function setCallbackQueryId(string $callbackQueryId): Response
    {
        $this->callbackAnswerParams['callback_query_id'] = $callbackQueryId;

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
