<?php


namespace AABTest;

use ArtaxApiBuilder\Operation;
use Artax\Response;

class PhotoList {

    use DataMapper;

    static protected $dataMap = array(
        ['page', 'page'],
        ['pages', 'pages'],
        ['perPage', 'perpage'],
        ['total', 'total'],
        ['photos', 'photo', 'class' => 'AABTest\Photo', 'multiple' => TRUE ],
    );

    var $page;
    var $pages;
    var $perPage;
    var $total;

    var $photos = array();

    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        
        return self::createFromJson($jsonData['photos']);
    }
    
//    static function createFromResponse() {
//        $jsonData = json_decode($data, true);
//        return self::createFromJson($jsonData['photos']);
//    }
}
