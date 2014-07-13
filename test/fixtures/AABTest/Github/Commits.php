<?php


namespace AABTest\Github;

use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;


class Commits {

    use DataMapper;

    /**
     * @var \AABTest\Github\Commit[]
     */
    public $commits = [];

    static protected $dataMap = array(
        ['commits', [], 'class' => 'AABTest\Github\Commit', 'multiple' => true],
    );


    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }

    /**
     * @return \AABTest\Github\Commit[]
     */
    public function getIterator() {
        return new \ArrayIterator($this->commits);
    }
}