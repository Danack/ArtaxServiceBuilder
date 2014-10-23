<?php


namespace ArtaxServiceBuilder;

use Amp\Artax\Request;
use Amp\Artax\Response;

interface ResponseCache {

    /**
     * @param $request
     * @return array
     */
    public function getCachingHeaders(Request $request);

    /**
     * Returns an array caching HTTP headers that should be set for this request e.g.
     * ['If-None-Match' => $matchValue, 'If-Modified-Since' => $modifiedValue]
     * @param Request $request
     * @return \Amp\Artax\Response
     */
    public function getResponse(Request $request);


    /**
     * @param $response
     * @return mixed
     */
    public function storeResponse(Request $request, Response $response);
}