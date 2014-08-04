<?php


namespace ArtaxServiceBuilder;

use ArtaxServiceBuilder\Service\GithubPaginator;


class PaginatorTest extends TestBase {


    function testLinkParsing() {
        $firstPage = 2;
        $lastPage = 10;
        
        $linkHeaders = ['<https://api.github.com/repositories/448045/tags?page=2>; rel="next", <https://api.github.com/repositories/448045/tags?page=10>; rel="last"'];

        $paginator = new GithubPaginator($linkHeaders);

        $this->assertEquals(
            "https://api.github.com/repositories/448045/tags?page=$firstPage",
            $paginator->nextLink->url
        );

        $this->assertEquals(
            "https://api.github.com/repositories/448045/tags?page=$lastPage",
            $paginator->lastLink->url
        );

        $pageRange = $paginator->getPageRange();
        
        $this->assertCount(
            2,
            $pageRange
        );

        $this->assertEquals($firstPage, $pageRange[0]);
        $this->assertEquals($lastPage, $pageRange[1]);
    }
}

 