<?php


namespace ArtaxServiceBuilder;

use Amp\Artax\Response;

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

    public function shouldResponseBeProcessed(Response $response);

    public function shouldResponseBeCached(Response $response);

    public function shouldUseCachedResponse(Response $response);

    public function translateResponseToException(Response $response);

    public function setResponse(\Amp\Artax\Response $response);
    
} 