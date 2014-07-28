<?php


namespace ArtaxServiceBuilder;


interface Operation {
    function getParameters();
    
    /**
     * @return \Artax\Request
     */
    function createRequest();


    /**
     * @param \Artax\Response $response
     * @return mixed
     */
    public function processResponse(\Artax\Response $response);
    
} 