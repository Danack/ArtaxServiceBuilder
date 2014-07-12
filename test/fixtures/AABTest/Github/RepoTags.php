<?php


namespace AABTest\Github;

use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;



class RepoTags implements \IteratorAggregate {

    use DataMapper;

    /** @var  \AABTest\Github\RepoTag[] */
    public $repoTags;
    

    static protected $dataMap = array(
        ['repoTags', [], 'class' => 'AABTest\Github\RepoTag', 'multiple' => true],
    );


    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }

    /**
     * @return \AABTest\Github\RepoTag[]
     */
    public function getIterator() {
        return new \ArrayIterator($this->repoTags);
    }
}

 