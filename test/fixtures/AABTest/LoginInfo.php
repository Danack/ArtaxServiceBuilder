<?php


namespace AABTest;


use ArtaxApiBuilder\Operation;
use Artax\Response;


class LoginInfo {

    use DataMapper;

    static protected $dataMap = array(
        ['user', ['user', 'id']],
        ['username', ['user', 'username', '_content']],
    );

    var $user; //shurely NSID?
    var $username;
    
    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }
}

