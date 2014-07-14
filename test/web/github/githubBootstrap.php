<?php


define('SERVER_HOSTNAME', 'localhost:8000');

$autoloader = require __DIR__.'/../../../vendor/autoload.php';

$classDir = realpath(__DIR__)."/../../fixtures/";
$outputDirectory = realpath(__DIR__).'/../../../var/src';

$autoloader->add('AABTest', [$classDir, $outputDirectory]);
$autoloader->add('ArtaxApiBuilder', [realpath(__DIR__)."/"]);

require "../webFunctions.php";

//include_once "../../../../flickrKey.php";

define('GITHUB_USER_AGENT', '');
define('GITHUB_CLIENT_ID', '');
define('GITHUB_CLIENT_SECRET', ''); //Client Secret 


define('SESSION_NAME', 'githubTest');

session_name(SESSION_NAME);
session_start();


