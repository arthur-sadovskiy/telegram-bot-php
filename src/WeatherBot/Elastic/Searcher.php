<?php

namespace WeatherBot\Elastic;

class Searcher
{
    private $search;

    public function __construct()
    {
        $client = (new Client())->create();
        $this->search = new \Elastica\Search($client);

        $index = $client->getIndex('telegram-bot');
        $this->search->addIndex($index);
        $this->search->addType($index->getType('cities'));
    }

    public function searchByName(string $cityName, bool $isFuzzy = false): array
    {
        $searchParam = $isFuzzy ? 'fuzzy' : 'match_phrase';
        //$searchParam = $isFuzzy ? 'fuzzy' : 'term'; // term doesn't detect new york

        $query = new \Elastica\Query([
            'query' => [
                $searchParam => ['name' => strtolower($cityName)],
            ],
        ]);

        $query->setSize(1000); // magic number, move to const

        $this->search->setQuery($query);

        $foundCities = [];
        $resultsCount = $this->search->count();
        if ($resultsCount) {
            $resultSet = $this->search->search();
            $results = $resultSet->getResults();
            $cityNamePartsCount = str_word_count($cityName);

            foreach($results as $result){
                $source = $result->getSource();
                if (!$isFuzzy && ($cityNamePartsCount > 1) && ($source['name'] !== $cityName)) {
                    continue;
                }

                $foundCities[] = $source;
            }
        }

        if (empty($foundCities) && !$isFuzzy) {
            $foundCities = $this->searchByName($cityName, $isFuzzy = true);
        }

        return $foundCities;
    }
}
