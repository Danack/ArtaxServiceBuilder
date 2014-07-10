<?php

use ArtaxApiBuilder\Service\OauthConfig;

/**
 * @group service
 */

class FlickrOauthTest extends \ArtaxApiBuilder\TestBase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;

    function setup() {
        //$this->provider = createTestProvider();
        parent::setup();
    }


    function testFlickOauthRequest() {


        $oauthConfig = new OauthConfig(
            FLICKR_KEY,
            FLICKR_SECRET
        );

        $oauthService = new \ArtaxApiBuilder\Service\FlickrOauth1($oauthConfig);

        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY);

        $oauth_verifier = '12345';
        
        //$command = $api->GetOauthAccessToken($oauth_verifier);
        $command = $api->GetOauthRequestToken("http://example.com/");
        $request = $command->createRequest();

        $signedRequest = $oauthService->signRequest($request);

        $response = $api->callAPI($signedRequest);
        
        var_dump($response->getBody());
        exit(0);

        //$response = $command->execute();
        

//
//            $flickrGuzzleClient = FlickrGuzzleClient::factory(array('oauth' => TRUE,));
//
//            $params = array(
//                'oauth_callback' => $callbackURL,
//            );
//
//            $command = $flickrGuzzleClient->getCommand('GetOauthRequestToken', $params);
//            $oauthRequestToken  = $command->execute();
//
//            setSessionVariable('oauthToken', $oauthRequestToken->oauthToken);
//            setSessionVariable('tokenSecret', $oauthRequestToken->oauthTokenSecret);
//
//            $flickrURL = "http://www.flickr.com/services/oauth/authorize?oauth_token=".$oauthRequestToken->oauthToken;
//            $this->view->assign('flickrURL', $flickrURL);
//            $this->view->setTemplate("flickr/flickrAuthRequest");
//        }
        
        

    }
    
    
    
    
    /**
     *
     */
    function tstFlickrPeopleGetPublicPhotos() {

        $oauthConfig = new OauthConfig(
            FLICKR_KEY,
            FLICKR_SECRET
        );

        $oauthService = new \ArtaxApiBuilder\Service\FlickrOauth1($oauthConfig);

        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY);
        $user_id = "46085186@N02";
        $per_page = 5;
        $page = 1;


        $command = $api->flickrPeopleGetPublicPhotos($user_id);
        $command->setPage($page);
        $command->setPerPage($per_page);

        $request = $command->createRequest();

        $signedRequest = $oauthService->signRequest($request);

        echo "unsigned";
        $request->debug();

        echo "signed";
        $signedRequest->debug();
        
        //exit(0);
        
        $response = $api->callAPI($signedRequest);
        
        var_dump($response->getBody());

//        $result = $command->execute();
//        $command->dispatch($request);

        /*
        
            $this->assertInstanceOf(
                'AABTest\PhotoList',
                $result,
                'flickr_people_getPublicPhotos did not return an instance of Intahwebz\FlickrGuzzle\DTO\PhotoList'
            );

            $this->assertCount(
                $per_page,
                $result->photos
            );

        */
    }

}

 