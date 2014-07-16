<?php

error_reporting(E_ALL);

$autoloader = require __DIR__.'/../vendor/autoload.php';
$classDir = realpath(__DIR__)."/fixtures/";

$autoloader->add('ArtaxServiceBuilder', [realpath(__DIR__)."/"]);

//$outputDirectory = realpath(__DIR__).'/../var/src';
//$autoloader->add('AABTest', [$classDir, $outputDirectory]);




