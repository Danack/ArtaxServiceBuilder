<?php


namespace ArtaxApiBuilder;


/**
 * @group service
 */

class ArtaxServiceBuilderTest extends \ArtaxApiBuilder\TestBase {


    function testFlickrPeopleGetPublicPhotos() {

        $outputDirectory = __DIR__.'/../../var/src';
        
        $apiGenerator = new \ArtaxApiBuilder\APIGenerator(
            $outputDirectory,
            []
        );

        $testServiceFilename = __DIR__.'/../fixtures/testService.php';
        
        $service = require_once $testServiceFilename;

        if (is_array($service) == false) {
            $this->fail("Failed to include `$testServiceFilename` cannot process test.");
        }

        $apiGenerator->parseAndAddService($service);

        $operations = $apiGenerator->getOperations();

        $this->assertArrayHasKey('overrideURL', $operations);

        $overrideOperation = $operations['overrideURL'];
            
        $this->assertEquals(
            'https://example.com/overrideURL',
            $overrideOperation->getURL()
        );
            
            
        

    }
    
    
}

 