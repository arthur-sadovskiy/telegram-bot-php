<?php
// This script helps to parse all data about available cities
// and to create index for them in Elasticsearch

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

use WeatherBot\Config;

echo 'Starting..' . PHP_EOL;

$config = new Config();
$params = [
    'host' => $config->get('elastic_host'),
    'port' => $config->get('elastic_port')
];

$elasticaClient = new \Elastica\Client($params);

$index = $elasticaClient->getIndex('telegram-bot');
if (!$index->exists()) {
    $index->create();
    echo 'Created index..' . PHP_EOL;
}
//$index->delete(); exit('Removed index ' . $index->getName() . PHP_EOL);

$type = $index->getType('cities');

$mapping = new \Elastica\Type\Mapping();
$mapping->setType($type);
$mapping->setProperties([
    'coord' => [
        'type' => 'geo_point'
    ]
]);
$mapping->send();
echo 'Set mapping..' . PHP_EOL;

for ($i = 0; $i < 21; $i++) {
    $citiesListRaw = file_get_contents("../cities_list_{$i}.json");
    /** @var array $citiesList */
    $citiesList = json_decode($citiesListRaw, true);
    unset($citiesListRaw);
    if (empty($citiesList)) {
        exit('There are no cities!');
    }

    $documents = [];
    foreach ($citiesList as $city) {
        $documents[] = new \Elastica\Document($city['id'], $city);
    }

    $type->addDocuments($documents);

    unset($citiesList);
    echo 'Done file #' . $i . PHP_EOL;
}
