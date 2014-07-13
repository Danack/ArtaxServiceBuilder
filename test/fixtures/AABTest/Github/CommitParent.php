<?php


namespace AABTest\Github;


use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;

class CommitParent {

    use DataMapper;

    static protected $dataMap = array(
        ['url', 'url'],
        ['sha', 'sha'],
    );
}

 