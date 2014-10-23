<?php


namespace ArtaxServiceBuilder\ResponseCache;

use Amp\Artax\Request;
use Amp\Artax\Response;
use ArtaxServiceBuilder\ResponseCache;

class FileResponseCache implements ResponseCache {

    private $cacheDirectory;

    public function __construct($cacheDirectory) {
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * Calculate the filename that the request should be cached as
     */
    public function calculateRequestFilename(Request $request) {
        $string = $request->getUri();
        $filename = parse_url($string, PHP_URL_HOST);
        $filename .= '_'.parse_url($string, PHP_URL_PATH);
        $headers = $request->getAllHeaders();
        ksort($headers);
        foreach ($headers as $header => $values) {
            $string .= $header;
            foreach ($values as $value) {
                $string .= $value;
            }
        }

        $filename .= '_'.sha1($string);

        if (strpos($filename, '_') === 0) {
            //Makes sorting not be crap
            $filename = substr($filename, 1);
        }

        return $this->cacheDirectory.'/'.$filename.'.cache';
    }

    private function getCachedResponse($cacheFilename) {
        if (file_exists($cacheFilename) == true) {
            $fileContents = file_get_contents($cacheFilename);
            $cachedResponse = unserialize($fileContents);
            return $cachedResponse;
        }
        
        return null;
    }
    
    /**
     * @param $request
     * @return array
     */
    public function getCachingHeaders(Request $request) {
        if (strcasecmp($request->getMethod(), 'GET') !== 0) {
            //We only cache GET requests.
            return [];
        }
        $headers = [];

        $cacheFilename = $this->calculateRequestFilename($request);
        $cachedResponse = $this->getCachedResponse($cacheFilename);

        if ($cachedResponse != null) {
            /** @var $cachedResponse \Amp\Artax\Response */
            if ($cachedResponse->hasHeader('ETag')) {
                $etagValues = $cachedResponse->getHeader('ETag');
                foreach ($etagValues as $value) {
                    $headers['If-None-Match'] = $value;
                    //@TODO - are multiple 'if-none-match headers allowed?
                }
            }
            
            if ($cachedResponse->hasHeader('Last-Modified')) {
                $ifModifiedValues = $cachedResponse->getHeader('Last-Modified');
                foreach ($ifModifiedValues as $value) {
                    $headers['If-Modified-Since'] = $value;
                }
            }
        }
        
        if (count($headers) == 0) {
            echo "hmm";
        }

        return $headers;
    }

    /**
     * Returns an array caching HTTP headers that should be set for this request e.g.
     * ['If-None-Match' => $matchValue, 'If-Modified-Since' => $modifiedValue]
     * @param Request $request
     * @return \Amp\Artax\Response
     */
    public function getResponse(Request $request) {
        $cacheFilename = $this->calculateRequestFilename($request);

        return $this->getCachedResponse($cacheFilename);
    }

    /**
     * @param $response
     * @return mixed
     */
    public function storeResponse(Request $request, Response $response) {
        $cacheFilename = $this->calculateRequestFilename($request);
        $data = serialize($response);
        $directory = dirname($cacheFilename);
        @mkdir($directory, 0755, true);
        file_put_contents($cacheFilename, $data);
    }
}

 