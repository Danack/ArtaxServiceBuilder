<?php


$autoloader = require __DIR__.'/../../../vendor/autoload.php';

$classDir = realpath(__DIR__)."/../../fixtures/";
$outputDirectory = realpath(__DIR__).'/../../../var/src';

$autoloader->add('AABTest', [$classDir, $outputDirectory]);
$autoloader->add('ArtaxApiBuilder', [realpath(__DIR__)."/"]);

require "../webFunctions.php";

include_once "../../../../flickrKey.php";

define('SESSION_NAME', 'flickrTest');

session_name(SESSION_NAME);
session_start();


