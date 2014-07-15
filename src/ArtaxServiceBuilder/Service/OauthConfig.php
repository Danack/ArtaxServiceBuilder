<?php


namespace ArtaxServiceBuilder\Service;


class OauthConfig {


    private $params = [];

    private $consumer_secret;
    public $request_method;

//    // Optional parameters should not be set if they have not been set in
//    // the config as the parameter may be considered invalid by the Oauth
//    // service.
//$optionalParams = [
//'callback'  => 'oauth_callback',
//'token'     => 'oauth_token',
//'verifier'  => 'oauth_verifier',
//'version'   => 'oauth_version'
//];

    function __construct(
        $consumerKey,
        $consumerSecret,
        $signatureMethod = Oauth1::SIGNATURE_METHOD_HMAC,
        $requestMethod = Oauth1::REQUEST_METHOD_HEADER,
        $version = "1.0"
    ) {
        $this->params['oauth_consumer_key'] = $consumerKey;
        $this->consumer_secret = $consumerSecret;

        if ($signatureMethod !== null) {
            $this->params['oauth_signature_method'] = $signatureMethod;
        }

        if ($requestMethod !== null) {
            $this->request_method = $requestMethod;
        }

        if ($version !== null) {
            $this->params['oauth_version'] = $version;
        }
    }

    function getConsumerSecret() {
        return $this->consumer_secret;
    }


    function setOauthToken($callback) {
        $this->params['oauth_token'] = $callback;
    }

    function setOauthVerifier($verifier) {
        $this->params['oauth_verifier'] = $verifier;
    }

    function setRealm($realm) {
        $this->params['realm'] = $realm;
    }

    function setVersion($version) {
        $this->params['oauth_version'] = $version;
    }

    /**
     * @return array
     */
    function toArray($overridingParams) {
        $params = array_merge($this->params, $overridingParams);

        return $params;
    }
}

 