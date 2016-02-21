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

    /**
     * Set the original response. This may be different from the usable response
     * e.g. when there is a cache hit.
     * @param Response $response
     * @return mixed
     */
    public function setOriginalResponse(\Amp\Artax\Response $response);
    
    /**
     * Set the original response. When a result is a cache hit the response
     * in the operation will be the cached response. Rate limit info and possibly
     * other things, need access to the actual original non-cache version of the
     * response.
     * @return \Amp\Artax\Response 
     */
    public function getOriginalResponse();
    
    public function getResultInstantiationInfo();
    
} 