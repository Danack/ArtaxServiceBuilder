<?php

/**
 * @group internet
 */

class FlickrAPITest extends \ArtaxApiBuilder\TestBase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;

    function setup() {
        //$this->provider = createTestProvider();
        parent::setup();
    }

    /**
     *
     */
    function testFlickrPeopleGetPublicPhotos() {
        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY);
        $user_id = "46085186@N02";
        $per_page = 5;
        $page = 1;

        try {
            $command = $api->flickrPeopleGetPublicPhotos($user_id);
            $command->setPage($page);
            $command->setPerPage($per_page);
            $result = $command->execute();

            $this->assertInstanceOf(
                'AABTest\PhotoList',
                $result,
                'flickr_people_getPublicPhotos did not return an instance of Intahwebz\FlickrGuzzle\DTO\PhotoList'
            );

            $this->assertCount(
                $per_page,
                $result->photos
            );
        }
        catch (\AABTest\FlickrAPI\FlickrAPIException $fae) {
            $this->fail("Test failed due to FlickrAPIException: "."Response body: ".$fae->getResponse()->getBody());
        }
    }


    /**
     * Test that requires oauth signing.
     */
    function testFlickrPeopleGetPhotos() {
        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY);
        $user_id = "46085186@N02";
        $per_page = 5;
        $page = 1;

        try {
            $command = $api->flickrPeopleGetPhotos($user_id);
            $command->setPage($page);
            $command->setPerPage($per_page);
            $result = $command->execute();

            $this->assertInstanceOf(
                'AABTest\PhotoList',
                $result,
                'flickr_people_getPublicPhotos did not return an instance of Intahwebz\FlickrGuzzle\DTO\PhotoList'
            );

            $this->assertCount(
                $per_page,
                $result->photos
            );
        }
        catch (\AABTest\FlickrAPI\FlickrAPIException $fae) {
            $this->fail("Test failed due to FlickrAPIException: "."Response body: ".$fae->getResponse()->getBody());
        }
    }


    /**
     * 
     */
    function testUpload() {

        $api = new \AABTest\FlickrAPI\FlickrAPI(FLICKR_KEY);
        $user_id = "46085186@N02";

        $command = $api->UploadPhoto(
            __DIR__."/../fixtures/TestImage.jpg",
            "Test image",
            "Test description",
            '',//['testing'],
            true
        );

        $command->setIs_public(false);
        

        $fileUploadResponse = $command->execute();
        

    }
    
}

 