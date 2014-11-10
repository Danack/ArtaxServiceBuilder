<?php


namespace ArtaxServiceBuilder;


class BasicAuthToken {

    private $username;
    private $password;
    
    function __construct($username, $password) {
        $this->username = $username; 
        $this->password = $password;
    }
    
    /**
     * Format the basic auth token as Github expect it.
     * @param $usernameColonpassword
     * @return null|string
     */
    public function __toString() {
        $usernameColonpassword = $this->username.':'.$this->password;

        return "Basic ".base64_encode($usernameColonpassword);
    }
    
}

