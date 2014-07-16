<?php


namespace ArtaxServiceBuilder;


use ArtaxServiceBuilder\APIGenerator;

/**
 * @group service
 */

class ArtaxServiceBuilderTest extends TestBase {


    function testFlickrPeopleGetPublicPhotos() {

        $outputDirectory = __DIR__.'/../../var/src';
        
        $apiGenerator = new APIGenerator(
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

 