<?php


namespace ArtaxServiceBuilder;


interface Operation {
    function getParameters();
    
    /**
     * @return \Artax\Request
     */
    function createRequest();
    
} 