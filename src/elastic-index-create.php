<?php
// This script helps to parse all data about available cities
// and to create index for them in Elasticsearch

require_once '../vendor/autoload.php';
require_once 'bootstrap.php';

$citiesListRaw = file_get_contents('../city.list.json');
$citiesList = json_decode($citiesListRaw, true);
if (empty($citiesList)) {
    exit('There are no cities!');
}

$elasticaClient = new \Elastica\Client([
    'host' => '172.17.0.2',
    'port' => 9200
]);

$index = $elasticaClient->getIndex('telegram-bot');
if (!$index->exists()) {
    $index->create();
}
//$index->delete(); exit;

$type = $index->getType('cities');

// mapping is needed here to fix PHP Fatal error:
// "index: /telegram-bot/cities/1283378 caused mapper [coord.lat] of different type,
// current_type [double], merged_type [long]"
$mapping = new \Elastica\Type\Mapping();
$mapping->setType($type);
$mapping->setProperties([
    'coord' => [
        'properties' => [
            'lat' => ['type' => 'double'],
            'lon' => ['type' => 'double'],
        ]
    ]
]);
$mapping->send();

$documents = [];
foreach ($citiesList as $city) {
    $documents[] = new \Elastica\Document($city['id'], $city);
}

$type->addDocuments($documents);
