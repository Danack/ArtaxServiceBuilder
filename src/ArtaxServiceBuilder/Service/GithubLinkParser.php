<?php


namespace ArtaxServiceBuilder\Service;

use Artax\Response;
//use ArtaxServiceBuilder\Service\HTTP2\HTTP2;
use ArtaxServiceBuilder\Service\Link;

/**
 * Class GithubLinkParser
 * 
 * Utility class for extracting links from RFC 5988 headers. This class may be usable
 * as a generic RFC 5988 parser, but it has a specific name for now until I can check 
 * other APIs that use RFC 5988.
 * 
 * @package ArtaxServiceBuilder\Service
 */
class GithubLinkParser {

    const FIRST = 'first';
    const PREVIOUS = 'prev';
    const NEXT = 'next';
    const LAST = 'last';

    /**
     * @var \Artax\Response
     */
    private $response;

    //next	Shows the URL of the immediate next page of results.
    //last	Shows the URL of the last page of results.
    //first	Shows the URL of the first page of results.
    //prev	Shows the URL of the immediate previous page of results.
    private $linkTypes = [
        self::FIRST,
        self::PREVIOUS,
        self::NEXT,
        self::LAST
    ];
    
    public function __construct(Response $response) {
        $this->response = $response;
    }
    
    /**
     * Extract the link header(s) as parse the RFC 5988 style links from them.
     * fyi RFC 5988 is a terrible idea. It means to cache a response, you now need to
     * cache both the body data return as well as the headers.
     * @return \ArtaxServiceBuilder\Service\Link[]
     */
    public function parseResponse() {
        $linkHeaders = $this->response->getHeader('Link');
        $links = array();

        foreach ($linkHeaders as $linkHeader) {
            $linkInfoArray = \HTTP2\HTTP2::parseLinks($linkHeader);

            foreach ($linkInfoArray as $linkInfo) {
                $url = null;
                $description = null;
                
                if (isset($linkInfo['_uri']) == true) {
                    $url = $linkInfo['_uri'];
                }
                
                if (isset($linkInfo['rel']) == true) {
                    $relInfo = $linkInfo['rel'];
                    if (is_array($relInfo)) {
                        foreach ($relInfo as $linkType) {
                            $description = $linkType;
                        }
                    }
                }

                if ($url != null && $description != null) {
                    //Check that it's not a new type that we don't understand
                    if (in_array($description, $this->linkTypes)) {
                        $links[$description] = new Link($url, $description);
                    }
                }
            }
        }

        return $links;
    }
}

 