<?php


namespace AABTest\Github;

use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;


class AuthResponse {

    use DataMapper;

    public $email;
    public $verified;
    public $primary;

    static protected $dataMap = array(
        ['email', 'email'],
        ['verified', 'verified'],
        ['primary', 'primary'],
    );

    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        
        var_dump($data);
        
        exit(0);
        
        //$jsonData = json_decode($data, true);

        //return self::createFromJson($jsonData['photos']);
    }
}
