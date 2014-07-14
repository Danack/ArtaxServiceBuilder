<?php

use ArtaxApiBuilder\OperationDefinition;
use ArtaxApiBuilder\APIGenerator;

$autoloader = require_once(__DIR__ . '/../vendor/autoload.php');

$outputDirectory = realpath(__DIR__).'/../var/src';
$autoloader->add('AABTest', [$outputDirectory]);


define('FLICKR_KEY', 12345);

$constructorParms = ['api_key'];



$apiGenerator = new \ArtaxApiBuilder\APIGenerator(
    $outputDirectory,
    $constructorParms
);


//
//$apiGenerator->addAPIParameters([
//    'api_key' => 'string'
//]);

$apiGenerator->addAPIParameter('api_key', 'string');



$apiGenerator->addParameterTranslation([
    'api_key' => 'apiKey',
    'per_page' => 'perPage',
    'content_type' => 'contentType',
    'max_taken_date' => 'maxTakenDate',
    'min_taken_date' => 'minTakenDate',
    'user_id' => 'userID',
    'safe_search' => 'safeSearch',
    'Is_public' => 'isPublic'
]);

if (true) {
    $apiGenerator->includeMethods([
        'GetOauthAccessToken',
        'GetOauthRequestToken',
        "flickr.people.getPublicPhotos",
        "flickr.people.getPhotos",
        "flickr.test.login"
    ]);
    
    //$apiGenerator->includePattern('flickr\.people\.get.*');
}

/*
$apiGenerator->excludeMethods(['defaultGetOperation']);
$apiGenerator->parseAndAddServiceFromFile(__DIR__.'/fixtures/flickrService.php');
$apiGenerator->addInterface('AABTest\FlickrAPI');
$apiGenerator->setFQCN('AABTest\FlickrAPI\FlickrAPI');
$apiGenerator->generate();
$apiGenerator->generateInterface('AABTest\FlickrAPI');
*/

$constructorParams = ['userAgent'];

//Start of github
$apiGenerator = new \ArtaxApiBuilder\APIGenerator(
    $outputDirectory,
    $constructorParams
);

$apiGenerator->addAPIParameter('userAgent');

$apiGenerator->addParameterTranslation([
    'User-Agent' => 'userAgent',
]);


$apiGenerator->excludeMethods(['defaultGetOperation']);
$apiGenerator->parseAndAddServiceFromFile(__DIR__.'/fixtures/githubService.php');
$apiGenerator->addInterface('AABTest\GithubAPI');
$apiGenerator->setFQCN('AABTest\GithubAPI\GithubAPI');
$apiGenerator->generate();
$apiGenerator->generateInterface('AABTest\GithubAPI');

