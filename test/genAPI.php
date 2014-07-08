<?php

use ArtaxApiBuilder\OperationDefinition;
use ArtaxApiBuilder\APIGenerator;

$autoloader = require_once(__DIR__ . '/../vendor/autoload.php');

$outputDirectory = realpath(__DIR__).'/../var/src';
$autoloader->add('AABTest', [$outputDirectory]);


define('FLICKR_KEY', 12345);

$constructorParms = ['api_key'];

function getNamespace($namespaceClass) {

    if (is_object($namespaceClass)) {
        $namespaceClass = get_class($namespaceClass);
    }

    $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

    if ($lastSlashPosition !== false) {
        return mb_substr($namespaceClass, 0, $lastSlashPosition);
    }

    return "";
}


function getClassName($namespaceClass) {
    $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

    if ($lastSlashPosition !== false) {
        return mb_substr($namespaceClass, $lastSlashPosition + 1);
    }

    return $namespaceClass;
}

$apiGenerator = new \ArtaxApiBuilder\APIGenerator(
    $outputDirectory,
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



$apiGenerator->includeMethods([
    "flickr.people.getPublicPhotos",
    "flickr.people.getPhotos"
]);
$apiGenerator->parseAndAddService(__DIR__.'/fixtures/flickrService.php');
$apiGenerator->addInterface('AABTest\FlickrAPI');
$apiGenerator->setFQCN('AABTest\FlickrAPI\FlickrAPI');
$apiGenerator->generate();

