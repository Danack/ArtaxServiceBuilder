<?php


namespace ArtaxServiceBuilder\Service;

use Artax\Response;
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

    public $earliestPage = null;
    public $furthestPage = null;

    public $firstLink = null;
    public $previousLink = null;
    public $nextLink = null;
    public $lastLink = null;
    
    const FIRST     = 'first';
    const PREVIOUS  = 'prev';
    const NEXT      = 'next';
    const LAST      = 'last';

    public $links = [];
    
    /**
     * @var \Artax\Response
     */
    private $response;

    //next	Shows the URL of the immediate next page of results.
    //last	Shows the URL of the last page of results.
    //first	Shows the URL of the first page of results.
    //prev	Shows the URL of the immediate previous page of results.
    private static $linkTypes = [
        self::FIRST,
        self::PREVIOUS,
        self::NEXT,
        self::LAST
    ];

    /**
     * We may need to re-order or sanity check the results of paging,
     * so list the order things should be in. Obviously the first link can be
     * equal to the previous link
     * @var array
     */
    public static $typeOrder = [
        self::FIRST => 0,
        self::PREVIOUS => 1,
        self::NEXT => 3,
        self::LAST => 4
    ];


    /**
     * @return array
     */
    public function getPageRange() {
        if ($this->earliestPage && $this->furthestPage) {
            return [$this->earliestPage, $this->furthestPage];
        }

        return [];
    }


    /**
     * @param Response $response
     */
    public static function constructFromResponse(Response $response) {
        $this->response = $response;
        $this->parseResponse($response);

        if (isset($this->links[GithubLinkParser::FIRST])) {
            $this->firstLink = $this->links[GithubLinkParser::FIRST];
        }

        if (isset($this->links[GithubLinkParser::PREVIOUS])) {
            $this->previousLink = $this->links[GithubLinkParser::PREVIOUS];
        }

        if (isset($this->links[GithubLinkParser::NEXT])) {
            $this->nextLink = $this->links[GithubLinkParser::NEXT];
        }

        if (isset($this->links[GithubLinkParser::LAST])) {
            $this->lastLink = $this->links[GithubLinkParser::LAST];
        }

        $this->makeDramaticAssumptionsAboutLinks($this->links);
    }
    
    /**
     * Extract the link header(s) as parse the RFC 5988 style links from them.
     * fyi RFC 5988 is a terrible idea. It means to cache a response, you now need to
     * cache both the body data return as well as the headers.
     * @return \ArtaxServiceBuilder\Service\Link[]
     */
    public function parseResponse(Response $response) {
        
        if ($response->hasHeader('Link') == false) {
            return [];
        }
        
        $linkHeaders = $response->getHeader('Link');
        
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
                    if (in_array($description, self::$linkTypes)) {
                        $links[$description] = new Link($url, $description);
                    }
                }
            }
        }

        $this->links = $links;
    }
    


    /**
     * This is a stupid function and I feel bad writing it, but it's not my fault.
     * Github return information about what links are available in text in the rel
     * links.....which means we need to manually extract the information about
     * what pages are available by hand. Which sucks, as it just completely fragile
     * and makes dramatic assumptions about the URI that github has return to us
     */
    function makeDramaticAssumptionsAboutLinks($links) {

        $earliestKnownURI = null;
        $earliestType = null;

        $furthestKnownURI = null;
        $furthestType = null;

        if (isset($links[GithubLinkParser::FIRST]) == true) {
            $earliestKnownURI = $links[GithubLinkParser::FIRST];
            $earliestType = GithubLinkParser::FIRST;
        }
        else if (isset($links[GithubLinkParser::PREVIOUS]) == true) {
            $earliestKnownURI = $links[GithubLinkParser::PREVIOUS];
            $earliestType = GithubLinkParser::PREVIOUS;
        }
        else if (isset($links[GithubLinkParser::NEXT]) == true) {
            $earliestKnownURI = $links[GithubLinkParser::NEXT];
            $earliestType = GithubLinkParser::NEXT;
        }

        if (isset($links[GithubLinkParser::LAST]) == true) {
            $furthestKnownURI = $links[GithubLinkParser::LAST];
            $furthestType = GithubLinkParser::LAST;
        }
        else if (isset($links[GithubLinkParser::NEXT]) == true) {
            $furthestKnownURI = $links[GithubLinkParser::NEXT];
            $furthestType = GithubLinkParser::NEXT;
        }
        else if (isset($links[GithubLinkParser::PREVIOUS]) == true) {
            $furthestKnownURI = $links[GithubLinkParser::PREVIOUS];
            $furthestType = GithubLinkParser::PREVIOUS;
        }

        if (GithubLinkParser::$typeOrder[$furthestType] <
            GithubLinkParser::$typeOrder[$earliestType]) {
            //TODO - links are borked.
        }

        if  ($earliestKnownURI) {
            if (preg_match('/page=(\d+)$/', $earliestKnownURI, $matches)) {
                $this->earliestPage = $matches[1];
            }
        }

        if  ($furthestKnownURI) {
            if (preg_match('/page=(\d+)$/', $furthestKnownURI, $matches)) {
                $this->furthestPage = $matches[1];
            }
        }
    }
}

 