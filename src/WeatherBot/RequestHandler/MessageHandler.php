<?php

namespace WeatherBot\RequestHandler;

use Elastica\Client as ElasticaClient;
use Telegram\Bot\Objects\Location;
use WeatherBot\{Elastic\Searcher, Helper\WeatherClient, InlineKeyboardTrait, Response};

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
        $this->chatId = $this->telegramUpdate->getMessage()->getChat()->getId();
        $providedText = $this->telegramUpdate->getMessage()->getText();
        $location = $this->telegramUpdate->getMessage()->getLocation();

        if (null !== $location) {
            $response = $this->handleLocation($location);
        } elseif (null !== $providedText) {
            $response = $this->handleText($providedText);
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
                ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken);

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
     *
     * @return Response
     */
    private function handleText(string $text): Response
    {
        $response = (new Response())->setMessageParam(Response::CHAT_ID, $this->chatId);

        if ($text === '/start') {
            $replyText = 'Provide city name, for which you would like to get weather forecast.' . PHP_EOL;
            $replyText .= 'Or just send your location!';

            $response->setMessageParam(Response::TEXT, $replyText);
        } elseif (\is_string($text)) {
            $foundData = (new Searcher($this->elasticaClient))->searchByName($text);

            if (\count($foundData) === 1) {
                $cityId = $foundData[0]['id'];
                $weatherClient = (new WeatherClient())
                    ->setParam(WeatherClient::CITY_KEY, $cityId)
                    ->setParam(WeatherClient::APPID_KEY, $this->weatherApiToken);

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
            } else {
                $replyText = "We couldn't find your city :(";
                $response->setMessageParam(Response::TEXT, $replyText);
            }
        }

        return $response;
    }
}
