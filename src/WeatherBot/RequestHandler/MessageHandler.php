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
            $response = (new Response())->setChatId($this->chatId)
                ->setText('Some error happened :(');
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

        $response = (new Response())->setChatId($this->chatId);

        if (!empty($foundData)) {
            $params = [
                WeatherClient::CITY_KEY => $cityId,
                WeatherClient::APPID_KEY => $this->weatherApiToken
            ];

            $replyText = (new WeatherClient($params))->fetch();
            $replyText .= PHP_EOL . PHP_EOL;
            $replyText .= 'Type "/start" to see menu or provide your location for immediate weather forecast';

            $response->setText($replyText)
                ->setReplyMarkup($this->getInlineKeyboardRepeat($cityId));
        } else {
            $replyText = "We couldn't find your city :(";
            $response->setText($replyText);
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
        $response = (new Response())->setChatId($this->chatId);

        if ($text === '/start') {
            $replyText = 'Provide city name, for which you would like to get weather forecast.' . PHP_EOL;
            $replyText .= 'Or just send your location!';

            $response->setText($replyText);
        } elseif (\is_string($text)) {
            $foundData = (new Searcher($this->elasticaClient))->searchByName($text);

            if (\count($foundData) === 1) {
                $cityId = $foundData[0]['id'];
                $params = [
                    WeatherClient::CITY_KEY => $cityId,
                    WeatherClient::APPID_KEY => $this->weatherApiToken
                ];

                $replyText = (new WeatherClient($params))->fetch();
                $replyText .= PHP_EOL . PHP_EOL . 'To see menu again, type "/start"';

                $response->setText($replyText)
                    ->setReplyMarkup($this->getInlineKeyboardRepeat($cityId));
            } elseif (\count($foundData) > 1) {
                $replyText = "We didn't find your city, but there are some very similar:";

                $response->setText($replyText)
                    ->setReplyMarkup($this->getInlineKeyboardMultipleChoices($foundData));
            } else {
                $replyText = "We couldn't find your city :(";
                $response->setText($replyText);
            }
        }

        return $response;
    }
}
