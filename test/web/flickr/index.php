<?php

require "flickrBootstrap.php";

use ArtaxApiBuilder\Service\OauthConfig;

$action = getVariable('action');

if ($action === 'delete') {
    unsetSessionVariable('oauthAccessToken');
    unsetSessionVariable('oauthRequest');
}

echo <<< END

<html>
<body>
<h3><a href='/'>Oauth test home</a> </h3>
END;


/** @var \AABTest\OauthAccessToken $oauthAccessToken */
$oauthAccessToken = getSessionVariable('oauthAccessToken');

if ($oauthAccessToken == null) {
    echo "<p>You are not flickr authorised.</p>";
    createOauthRequest();
}
else {
    echo "<p>You are flickr authorised.</p>";
    showFlickrStatus($oauthAccessToken);
    
    echo "<p><a href='/flickr/index.php?action=delete'>Delete authority</a></p>";
}

echo <<< END

</body>
</html>

END;


function showFlickrStatus(\AABTest\OauthAccessToken $oauthAccessToken) {

    $oauthConfig = new OauthConfig(
        FLICKR_KEY,
        FLICKR_SECRET
    );

    $oauthService = new \ArtaxApiBuilder\Service\FlickrOauth1($oauthConfig);

    $oauthService->setOauthToken($oauthAccessToken->oauthToken);
    $oauthService->setTokenSecret($oauthAccessToken->oauthTokenSecret);
    
    $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY, $oauthService);
    $command = $api->flickrTestLogin();
    $loginInfo = $command->execute();
    
    echo "User ID: ".$loginInfo->user."<br/>";
    echo "User name: ".$loginInfo->username."<br/>";
}


function createOauthRequest() {

    $oauthConfig = new OauthConfig(
        FLICKR_KEY,
        FLICKR_SECRET
    );

    try {
        $oauthService = new \ArtaxApiBuilder\Service\FlickrOauth1($oauthConfig);
        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY, $oauthService);
        $command = $api->GetOauthRequestToken("http://localhost:8000/flickr/return.php");
        $oauthRequest = $command->execute();

        setSessionVariable('oauthRequest', $oauthRequest);

        $flickrURL = "http://www.flickr.com/services/oauth/authorize?oauth_token=".$oauthRequest->oauthToken;
        echo sprintf("Please click <a href='%s'>here to auth</a>. ", $flickrURL);
    }
    catch(\AABTest\FlickrAPI\FlickrAPIException $fae) {
        echo "FlickrAPIException response body.";
        var_dump($fae->getResponse()->getBody());
    }
}

 