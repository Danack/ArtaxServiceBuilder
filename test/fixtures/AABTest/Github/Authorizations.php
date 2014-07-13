<?php


namespace AABTest\Github;

use Artax\Response;
use AABTest\DataMapper;
use ArtaxApiBuilder\Operation;

class Authorizations implements \IteratorAggregate  {

    use DataMapper;

    /** @var  \AABTest\Github\Authorization[] */
    public $authorizations = [];
    
    static protected $dataMap = array(
        ['authorizations', [], 'class' => 'AABTest\Github\Authorization', 'multiple' => true],
    );

    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }

    /**
     * @return \AABTest\Github\Authorization[]
     */
    public function getIterator() {
        return new \ArrayIterator($this->authorizations);
    }
}

 