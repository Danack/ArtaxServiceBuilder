<?php

namespace ArtaxServiceBuilder;

class BadResponseException extends \Exception {

    /**
     * @var \Artax\Request
     */
    private $request;

    /**
     * @var \Artax\Response
     */
    private $response;

    public function __construct($message = "", \Artax\Request $request, \Artax\Response $response, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return \Artax\Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return \Artax\Response
     */
    public function getResponse() {
        return $this->response;
    }
};

 
 