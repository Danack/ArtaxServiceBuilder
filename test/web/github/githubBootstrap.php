<?php


define('SERVER_HOSTNAME', 'localhost:8000');

$autoloader = require __DIR__.'/../../../vendor/autoload.php';

$classDir = realpath(__DIR__)."/../../fixtures/";
$outputDirectory = realpath(__DIR__).'/../../../var/src';

$autoloader->add('AABTest', [$classDir, $outputDirectory]);
$autoloader->add('ArtaxApiBuilder', [realpath(__DIR__)."/"]);

require "../webFunctions.php";

//include_once "../../../../flickrKey.php";

define('GITHUB_USER_AGENT', 'Danack_ArtaxAPIBuilder');
define('GITHUB_CLIENT_ID', '4b3d5330e6af849059a5');
define('GITHUB_CLIENT_SECRET', '5e16642a8104ba141b94b115a398283fe3f6a930'); //Client Secret 


define('SESSION_NAME', 'githubTest');

session_name(SESSION_NAME);
session_start();


