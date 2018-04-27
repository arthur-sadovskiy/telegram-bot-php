<?php

namespace WeatherBot\RequestHandler;

use Elastica\Client as ElasticaClient;
use Telegram\Bot\{Keyboard\Keyboard, Objects\Location};
use WeatherBot\{Elastic\Searcher, Helper\WeatherClient};

class MessageHandler extends AbstractHandler
{
    /**
     * @var int
     */
    private $chatId;

    /**
     * @var ElasticaClient
     */
    private $elasticaClient;

    /**
     * @return array
     */

    /**
     * @param ElasticaClient $elasticaClient
     */
    public function setElasticaClient(ElasticaClient $elasticaClient)
    {
        $this->elasticaClient = $elasticaClient;
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        $this->chatId = $this->telegramUpdate->getMessage()->getChat()->getId();
        $providedText = $this->telegramUpdate->getMessage()->getText();
        $location = $this->telegramUpdate->getMessage()->getLocation();

        if (null !== $location) {
            $response = $this->handleLocation($location);
        } elseif (null !== $providedText) {
            $response = $this->handleText($providedText);
        } else {
            $response = [
                'main' => [
                    'chat_id' => $this->chatId,
                    'text' => 'Some error happened :('
                ]
            ];
        }

        return $response;
    }

    /**
     * @param Location $location
     *
     * @return array
     */
    private function handleLocation(Location $location): array
    {
        $userLocation = [
            'lat' => $location->getLatitude(),
            'lon' => $location->getLongitude()
        ];

        $foundData = (new Searcher($this->elasticaClient))->searchByLocation($userLocation);
        $cityId = $foundData[0]['id'];

        $responseMessageParams['chat_id'] = $this->chatId;

        if (!empty($foundData)) {
            $params = [
                WeatherClient::CITY_KEY => $cityId,
                WeatherClient::APPID_KEY => $this->weatherApiToken
            ];

            $replyText = (new WeatherClient($params))->fetch();
            $replyText .= PHP_EOL . PHP_EOL;
            $replyText .= 'Type "/start" to see menu or provide your location for immediate weather forecast';
            $responseMessageParams['text'] = $replyText;

            $inlineKeyboard = Keyboard::make()
                ->inline()
                ->row(
                    Keyboard::inlineButton([
                        'text' => 'Repeat last request',
                        'callback_data' => $cityId
                    ])
                );

            $responseMessageParams['reply_markup'] = $inlineKeyboard;
        } else {
            $replyText = "We couldn't find your city :(";
            $responseMessageParams['text'] = $replyText;
        }

        return [
            'main' => $responseMessageParams
        ];
    }

    /**
     * @param string $text
     *
     * @return array
     */
    private function handleText(string $text): array
    {
        $responseMessageParams['chat_id'] = $this->chatId;

        if ($text === '/start') {
            $replyText = 'Provide city name, for which you would like to get weather forecast.' . PHP_EOL;
            $replyText .= 'Or just send your location!';

            $responseMessageParams['text'] = $replyText;
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

                $inlineKeyboard = Keyboard::make()
                    ->inline()
                    ->row(
                        Keyboard::inlineButton([
                            'text' => 'Repeat last request',
                            'callback_data' => $cityId
                        ])
                    );

                $responseMessageParams['text'] = $replyText;
                $responseMessageParams['reply_markup'] = $inlineKeyboard;
            } elseif (\count($foundData) > 1) {
                $buttons = [];
                $inlineKeyboard = Keyboard::make()->inline();
                foreach ($foundData as $city) {
                    $buttons[] = Keyboard::inlineButton([
                        'text' => "{$city['name']} ({$city['country']})",
                        'callback_data' => $city['id']
                    ]);
                    if (\count($buttons) === 2) {
                        $inlineKeyboard = \call_user_func_array([$inlineKeyboard, 'row'], $buttons);
                        $buttons = [];
                    }
                }

                $replyText = "We didn't find your city, but there are some very similar:";

                $responseMessageParams['text'] = $replyText;
                $responseMessageParams['reply_markup'] = $inlineKeyboard;
            } else {
                $responseMessageParams['text'] = "We couldn't find your city :(";
            }
        }

        return [
            'main' => $responseMessageParams
        ];
    }
}
