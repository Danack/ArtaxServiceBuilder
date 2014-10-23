<?php


namespace ArtaxServiceBuilder;


interface Operation {
    function getParameters();
    
    /**
     * @return \Amp\Artax\Request
     */
    function createRequest();


    /**
     * @param \Amp\Artax\Response $response
     * @return mixed
     */
    public function processResponse(\Amp\Artax\Response $response);

    public function isErrorResponse(\Amp\Artax\Response $response);

    public function shouldResponseBeProcessed(\Amp\Artax\Response $response);

    public function setResponse(\Amp\Artax\Response $response);
    
} 