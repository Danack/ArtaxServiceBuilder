<?php


namespace AABTest\Github;

use Artax\Response;
use AABTest\DataMapper;
use ArtaxApiBuilder\Operation;

class Authorization {

    use DataMapper;
    
    public $id;
    public $url;
    public $scopes;
    public $token;
    
    /** @var  \AABTest\Github\Application */
    public $application;
    
    public $note;
    public $noteURL;
    public $updatedAt;
    public $createdAt;
    
    static protected $dataMap = array(
        ['id', 'id' ],
        ['url', 'url'],
        ['scopes', 'scopes', 'multiple' => true],
        ['token', 'token'],
        ['application', ['app'], 'class' => '\AABTest\Github\Application'],
        ['note', 'note'],
        ['noteURL', 'note_url'],
        ['updatedAt', 'updated_at'],
        ['createdAt', 'created_at']
    );
    

    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }
}

 