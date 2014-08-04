<?php


namespace ArtaxServiceBuilder\Service;

use Artax\Response;
use ArtaxServiceBuilder\Service\Link;

/**
 * Class GithubPaginator
 * 
 * Utility class for extracting links from RFC 5988 headers. This class may be usable
 * as a generic RFC 5988 parser, but it has a specific name for now until I can check 
 * other APIs that use RFC 5988.
 * 
 * @package ArtaxServiceBuilder\Service
 */
class GithubPaginator {

    public $earliestPage = null;
    public $furthestPage = null;
    public $nextPage = null;

    public $urlStub = null;

    public $firstLink = null;
    public $previousLink = null;
    public $nextLink = null;
    public $lastLink = null;
    
    const FIRST     = 'first';  //first	Shows the URL of the first page of results.
    const PREVIOUS  = 'prev';   //prev	Shows the URL of the immediate previous page of results.
    const NEXT      = 'next';   //next	Shows the URL of the immediate next page of results.
    const LAST      = 'last';   //last	Shows the URL of the last page of results.

    /** @var \ArtaxServiceBuilder\Service\Link[] */
    public $links = [];

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
     * @param array $linkHeaders
     */
    public function __construct(array $linkHeaders) {
        $this->parseLinksHeaders($linkHeaders);

        if (isset($this->links[self::FIRST])) {
            $this->firstLink = $this->links[self::FIRST];
        }
        if (isset($this->links[self::PREVIOUS])) {
            $this->previousLink = $this->links[self::PREVIOUS];
        }
        if (isset($this->links[self::NEXT])) {
            $this->nextLink = $this->links[self::NEXT];
        }
        if (isset($this->links[self::LAST])) {
            $this->lastLink = $this->links[self::LAST];
        }

        $this->makeDramaticAssumptionsAboutLinks($this->links);
    }

    /**
     * @return Link[]
     */
    public function getLinks() {
        return $this->links;
    }

    /**
     * @param $page
     * @return string
     */
    public function getURLForPage($page) {
        $page = intval($page);
        return $this->urlStub.$page;
    }

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
     * Returns an iterator of the remaining pages if both
     * $nextPage and $furthestPage are set, otherwise returns false
     */
    function getRemainingPages() {
        if ($this->nextPage !== null && 
            $this->furthestPage !== null) {
            return $this->getPages($this->nextPage, $this->furthestPage, $this->urlStub);
        }

        return false;
    }

    /**
     * 
     * 
     * 
     * @return array|bool An array of URI strings on success or false on failure.
     */
    function getAllKnownPages() {
        if ($this->earliestPage !== null && 
            $this->furthestPage !== null) {
            return $this->getPages($this->earliestPage, $this->furthestPage, $this->urlStub);
        }

        return false;
    }
    

    /**
     * Generates an array of the URIs for a set of pages.
     * @param $startPage
     * @param $endPage
     * @param $urlStub
     * @return array
     */
    public function getPages($startPage, $endPage, $urlStub) {
        $pages = [];
        //TODO yield this array - after upgrading to php 5.5
        for($x=$startPage ; $x<=$endPage ; $x++) {
            $pages[] = $urlStub.$x;
        }

        return $pages;
    }


    /**
     * @param Response $response
     * @return GithubPaginator|null
     */
    public static function constructFromResponse(Response $response) {
        //TODO - make this not nullable
        if ($response->hasHeader('Link') == false) {
            return null;
        }

        $linkHeaders = $response->getHeader('Link');
        $instance = new self($linkHeaders);
     
        return $instance;
    }
    
    /**
     * Extract the link header(s) as parse the RFC 5988 style links from them.
     * fyi RFC 5988 is a terrible idea. It means to cache a response, you now need to
     * cache both the body data return as well as the headers.
     * @return \ArtaxServiceBuilder\Service\Link[]
     */
    public function parseLinksHeaders(array $linkHeaders) {

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
     * @param \ArtaxServiceBuilder\Service\Link[] $links
     */
    function makeDramaticAssumptionsAboutLinks(array $links) {

        $earliestKnownLink = null;
        $earliestType = null;

        $furthestKnownLink = null;
        $furthestType = null;
        
        $nextLink = null;

        if (isset($links[self::FIRST]) == true) {
            $earliestKnownLink = $links[self::FIRST];
            $earliestType = self::FIRST;
        }
        else if (isset($links[self::PREVIOUS]) == true) {
            $earliestKnownLink = $links[self::PREVIOUS];
            $earliestType = self::PREVIOUS;
        }
        else if (isset($links[self::NEXT]) == true) {
            $earliestKnownLink = $links[self::NEXT];
            $earliestType = self::NEXT;
        }

        if (isset($links[self::LAST]) == true) {
            $furthestKnownLink = $links[self::LAST];
            $furthestType = self::LAST;
        }
        else if (isset($links[self::NEXT]) == true) {
            $furthestKnownLink = $links[self::NEXT];
            $furthestType = self::NEXT;
            $nextLink = $links[self::NEXT];
        }
        else if (isset($links[self::PREVIOUS]) == true) {
            $furthestKnownLink = $links[self::PREVIOUS];
            $furthestType = self::PREVIOUS;
        }

        
        if (isset(self::$typeOrder[$furthestType]) &&
            isset(self::$typeOrder[$earliestType])) {
            if (self::$typeOrder[$furthestType] <
                self::$typeOrder[$earliestType]
            ) {
                //TODO - links are borked.
            }
        }


        $urlStub1 = null;
        $urlStub2 = null;
        
        if  ($earliestKnownLink) {
            if (preg_match('/(.*page=)(\d+)$/', $earliestKnownLink->url, $matches)) {
                $urlStub1 = $matches[1];
                $this->earliestPage = intval($matches[2]);
            }
        }

        if  ($furthestKnownLink) {
            if (preg_match('/(.*page=)(\d+)$/', $furthestKnownLink->url, $matches)) {
                $urlStub2 = $matches[1];
                $this->furthestPage = intval($matches[2]);
            }
        }

        if  ($nextLink) {
            if (preg_match('/(.*page=)(\d+)$/', $nextLink->url, $matches)) {
                $urlStub2 = $matches[1];
                $this->nextPage = intval($matches[2]);
            }
        }


        if ($urlStub1 && $urlStub2) {
            //TODO - what do we do when they don't match?
        }

        if ($urlStub1) {
            $this->urlStub = $urlStub1;
        }
        else if ($urlStub2) {
            $this->urlStub = $urlStub2;
        }
    }
}

 