<?php

namespace ArtaxServiceBuilder;

class BadResponseException extends \Exception {

    /**
     * @var \Amp\Artax\Request
     */
    private $request;

    /**
     * @var \Amp\Artax\Response
     */
    private $response;

    public function __construct($message = "", \Amp\Artax\Request $request, \Amp\Artax\Response $response, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return \Amp\Artax\Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return \Amp\Artax\Response
     */
    public function getResponse() {
        return $this->response;
    }
};

 
 