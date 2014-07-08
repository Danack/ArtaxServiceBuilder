<?php

error_reporting(E_ALL);

$autoloader = require __DIR__.'/../vendor/autoload.php';
$currentDir = realpath(__DIR__)."/";
$outputDirectory = realpath(__DIR__).'/../var/src';

$autoloader->add('AABTest', [$currentDir, $outputDirectory]);
$autoloader->add('ArtaxApiBuilder', [realpath(__DIR__)."/"]);

$included = include_once "../../flickrKey.php";


if (defined('FLICKR_KEY') == false) {
    echo "To run the Flickr tests you must define a Flickr API key to use.";
    define('FLICKR_KEY', 12345);
}