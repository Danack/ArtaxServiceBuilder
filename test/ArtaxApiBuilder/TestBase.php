<?php

namespace ArtaxApiBuilder;


/**
 * Class TestBase
 * 
 * Allows checking that no code has output characters, or left the output buffer in a bad state.
 * 
 * @package ArtaxApiBuilder
 */
class TestBase extends \PHPUnit_Framework_TestCase {

    private $startLevel = null;

    function setup() {
        $this->startLevel = ob_get_level();
        ob_start();
    }

    function teardown() {
        
        if ($this->startLevel === null) {
            $this->assertEquals(0, 1, "startLevel was not set, cannot complete teardown");
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $endLevel = ob_get_level();
        $this->assertEquals($endLevel, $this->startLevel, "Mismatched ob_start/ob_end calls....somewhere");
        $this->assertEquals(
            0,
            strlen($contents),
            "Something has directly output to the screen: [".substr($contents, 0, 50)."]"
        );
    }
    
    
}

 