<?php

namespace ArtaxServiceBuilder;

class BadResponseException extends \Exception {

    /**
     * @var \Amp\Artax\Response
     */
    private $response;

    public function __construct($message = "", \Amp\Artax\Response $response, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @return \Amp\Artax\Response
     */
    public function getResponse() {
        return $this->response;
    }
};

 
 