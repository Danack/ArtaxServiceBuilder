<?php


namespace ArtaxServiceBuilder;


class Oauth2Token {
    
    private $accessToken;

    function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Format the auth/bearer token as Github expect it.
     * @param $accessToken
     * @return string
     */
    public function __toString() {
        if ($this->accessToken === null) {
            return null;
        }

        return "token ".$this->accessToken;
    }
}

