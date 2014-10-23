<?php

namespace ArtaxServiceBuilder\ResponseCache;

use Amp\Artax\Request;
use Amp\Artax\Response;
use ArtaxServiceBuilder\ResponseCache;

class NullResponseCache implements ResponseCache {

    public function getCachingHeaders(Request $request) {
        return [];
    }

    public function getResponse(Request $request) {
        return null;
    }

    public function storeResponse(Request $request, Response $response) {
        //Null cache does nothing.  
    }
}

 