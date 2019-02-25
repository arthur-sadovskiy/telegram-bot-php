<?php

namespace WeatherBot\RequestHandler;

use Elastica\Client as ElasticaClient;
use Telegram\Bot\Objects\Location;
use WeatherBot\{
    Elastic\Searcher, Helper\WeatherClient, InlineKeyboardTrait, RedisHelper, Response
};
use Elastica\Exception\Connection\HttpException;

class MessageHandler extends AbstractHandler
{
    use InlineKeyboardTrait;

    /**
     * @var int
     */
    private $chatId;

    /**
     * @var ElasticaClient
     */
    private $elasticaClient;

    /**
     * @param ElasticaClient $elasticaClient
     */
    public function setElasticaClient(ElasticaClient $elasticaClient)
    {
        $this->elasticaClient = $elasticaClient;
    }

    /**
     * @return Response
     */
    public function handle(): Response
    {
        $message = $this->telegramUpdate->getMessage();
        $this->chatId = $message->getChat()->getId();
        $providedText = $message->getText();
        $location = $message->getLocation();
        $username = $message->getFrom()->getFirstName() ?? $message->getFrom()->getUsername();

        if (null !== $location) {
            $response = $this->handleLocation($location);
        } elseif (null !== $providedText) {
            $response = $this->handleText($providedText, $username);
        } else {
            $response = (new Response())
                ->setMessageParam(Response::CHAT_ID, $this->chatId)
                ->setMessageParam(Response::TEXT, 'Some error happened :(');
        }

        return $response;
    }

    /**
     * @param Location $location
     *
     * @return Response
     */
    private function handleLocation(Location $location): Response
    {
        $userLocation = [
            'lat' => $location->getLatitude(),
            'lon' => $location->getLongitude()
        ];

        $foundData = (new Searcher($this->elasticaClient))->searchByLocation($userLocation);
        $cityId = $foundData[0]['id'];

        $response = (new Response())->setMessageParam(Response::CHAT_ID, $this->chatId);

        if (!empty($foundData)) {
            $weatherClient = (new WeatherClient())
                ->setParam(WeatherClient::CITY_KEY, $cityId)
                ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken)
                ->setRedisHelper(
                    (new RedisHelper())->createRedisClient($this->getRedisClientConfig())
                );

            $replyText = $weatherClient->fetch();
            $replyText .= PHP_EOL . PHP_EOL;
            $replyText .= 'To get the weather forecast for the same city use one of the buttons below.';
            $replyText .= PHP_EOL;
            $replyText .= 'Or provide a new city name / send another location!';

            $response->setMessageParam(Response::TEXT, $replyText)
                ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardRepeat($cityId));
        } else {
            $replyText = "We couldn't find your city :(";
            $response->setMessageParam(Response::TEXT, $replyText);
        }

        return $response;
    }

    /**
     * @param string $text
     * @param string $username
     *
     * @return Response
     */
    private function handleText(string $text, string $username): Response
    {
        $response = (new Response())->setMessageParam(Response::CHAT_ID, $this->chatId);

        if ($text === '/start') {
            $replyText = "Hey, {$username}!" . PHP_EOL;
            $replyText .= 'Provide city name, for which you would like to get weather forecast.' . PHP_EOL;
            $replyText .= 'Or just send your location!';

            $response->setMessageParam(Response::TEXT, $replyText);
        } elseif (\is_string($text)) {
            try {
                $foundData = (new Searcher($this->elasticaClient))->searchByName($text);
            } catch (HttpException $e) {
                $foundData = [];
                $errorText = 'Whoops, an error has occurred (code #' . CURLE_COULDNT_CONNECT . ').';
            }

            if (\count($foundData) === 1) {
                $cityId = $foundData[0]['id'];
                $weatherClient = (new WeatherClient())
                    ->setParam(WeatherClient::CITY_KEY, $cityId)
                    ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken)
                    ->setRedisHelper(
                        (new RedisHelper())->createRedisClient($this->getRedisClientConfig())
                    );

                $replyText = $weatherClient->fetch();
                $replyText .= PHP_EOL . PHP_EOL;
                $replyText .= 'To get the weather forecast for the same city use one of the buttons below.';
                $replyText .= PHP_EOL;
                $replyText .= 'Or provide a new city name / send another location!';

                $response->setMessageParam(Response::TEXT, $replyText)
                    ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardRepeat($cityId));
            } elseif (\count($foundData) > 1) {
                $replyText = "We didn't find your city, but there are some very similar:";

                $response->setMessageParam(Response::TEXT, $replyText)
                    ->setMessageParam(Response::REPLY_MARKUP, $this->getInlineKeyboardMultipleChoices($foundData));
            } elseif (empty($foundData) && !isset($errorText)) {
                $replyText = "We couldn't find your city :(";
                $response->setMessageParam(Response::TEXT, $replyText);
            } else {
                $response->setMessageParam(Response::TEXT, $errorText);
            }
        }

        return $response;
    }
}
