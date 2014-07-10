<?php

error_reporting(E_ALL);

$autoloader = require __DIR__.'/../vendor/autoload.php';
$classDir = realpath(__DIR__)."/fixtures/";
$outputDirectory = realpath(__DIR__).'/../var/src';

$autoloader->add('AABTest', [$classDir, $outputDirectory]);
$autoloader->add('ArtaxApiBuilder', [realpath(__DIR__)."/"]);

$included = include_once "../../flickrKey.php";


if (defined('FLICKR_KEY') == false) {
    echo "To run the Flickr tests you must define a Flickr API key to use.";
    define('FLICKR_KEY', 12345);
}

if (defined('FLICKR_SECRET') == false) {
    echo "To run the Flickr oauth tests you must define a Flickr API key to use.";
    define('FLICKR_SECRET', 54321);
}

