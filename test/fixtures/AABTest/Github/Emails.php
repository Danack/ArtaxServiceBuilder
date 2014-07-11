<?php

namespace AABTest\Github;

use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;


class Emails {

    use DataMapper;

    /** @var  \AABTest\Github\Email[] */
    public $emails;

    static protected $dataMap = array(
        ['emails', [], 'class' => 'AABTest\Github\Email', 'multiple' => true],
    );

    static function createFromResponse(Response $response, Operation $operation) {
        $data = $response->getBody();
        $jsonData = json_decode($data, true);

        return self::createFromJson($jsonData);
    }
}
