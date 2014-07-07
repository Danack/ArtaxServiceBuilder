<?php

use BaseReality\ArtaxBuilder\OperationDefinition;
use BaseReality\ArtaxBuilder\APIGenerator;

require_once(__DIR__.'/../vendor/autoload.php');




$constructorParms = ['api_key'];

$apiGenerator = new \ArtaxApiBuilder\APIGenerator(
    realpath(__DIR__).'/../var/src/',
    $constructorParms
);

$apiGenerator->addAPIParameters([
    'api_key'
]);

$apiGenerator->addParameterTranslation([
    'api_key' => 'apiKey',
    'per_page' => 'perPage',
    'content_type' => 'contentType',
    'max_taken_date' => 'maxTakenDate',
    'min_taken_date' => 'minTakenDate',
    'user_id' => 'userID',
    'safe_search' => 'safeSearch',
]);



$apiGenerator->includeMethods(["flickr.people.getPublicPhotos", "flickr.people.getPhotos"]);


$apiGenerator->parseAndAddService(__DIR__.'/fixtures/flickrService.php');

$apiGenerator->addInterface('BaseReality\Service\FlickrAPI');
$apiGenerator->setFQCN('BaseReality\FlickrAPI\FlickrAPI');
$apiGenerator->generate();

