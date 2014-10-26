<?php

namespace ArtaxServiceBuilder\Service;

//Originally from https://github.com/guzzle/oauth-subscriber

use Amp\Artax\Request;
use ArtaxServiceBuilder\ArtaxServiceException;


/**
 * OAuth 1.0 signature plugin.
 *
 * Portions of this code comes from HWIOAuthBundle and a Guzzle 3 pull request:
 * @author Alexander <iam.asm89@gmail.com>
 * @author Joseph Bielawski <stloyd@gmail.com>
 * @author Francisco Facioni <fran6co@gmail.com>
 * @link https://github.com/hwi/HWIOAuthBundle
 * @link https://github.com/guzzle/guzzle/pull/563 Original Guzzle 3 pull req.
 *
 * @link http://oauth.net/core/1.0/#rfc.section.9.1.1 OAuth specification
 */
class Oauth1
{
    /**
     * Consumer request method constants. See http://oauth.net/core/1.0/#consumer_req_param
     */
    const REQUEST_METHOD_HEADER = 'header';
    const REQUEST_METHOD_QUERY  = 'query';

    const SIGNATURE_METHOD_HMAC      = 'HMAC-SHA1';
    const SIGNATURE_METHOD_RSA       = 'RSA-SHA1';
    const SIGNATURE_METHOD_PLAINTEXT = 'PLAINTEXT';

    /**
     * @var OauthConfig
     */
    private $oauthConfig;

    private $consumer_secret;
    private $realm;
    private $signature_method;
    private $token_secret = null;
    private $version = '1.0';
    protected $disable_post_params;

    private $oauth_token;


    /**
     * Create a new OAuth 1.0 plugin.
     *
     * The configuration array accepts the following options:
     *
     * - request_method: Consumer request method. One of 'header' or 'query'.
     *   Defaults to 'header'.
     * - callback: OAuth callback
     * - consumer_key: Consumer key string. Defaults to "anonymous".
     * - consumer_secret: Consumer secret. Defaults to "anonymous".
     * - token: Client token
     * - token_secret: Client secret token
     * - verifier: OAuth verifier.
     * - version: OAuth version. Defaults to '1.0'.
     * - realm: OAuth realm.
     * - signature_method: Signature method. One of 'HMAC-SHA1', 'RSA-SHA1', or
     *   'PLAINTEXT'. Defaults to 'HMAC-SHA1'.
     *
     * @param array $config Configuration array.
     */
    public function __construct(OauthConfig $oauthConfig) {
        $this->oauthConfig = $oauthConfig;
        $this->version          = '1.0';
        $this->signature_method = self::SIGNATURE_METHOD_HMAC;
    }

    
    function setOauthToken($oauth_token) {
        $this->oauth_token = $oauth_token;
    }
    
    function setTokenSecret($token_secret) {
        $this->token_secret = $token_secret;
    }
    
    /**
     * Decide whether the post fields should be added to the base string that Oauth signs.
     * Non-conformant APIs may require that this method be
     * overwritten e.g. the Flickr API incorrectly adds the post fields when the Content-Type
     * is 'application/x-www-form-urlencoded'
     *
     * @param $request
     * @return bool Whether the post fields should be signed or not
     */
    public function shouldPostFieldsBeSigned(Request $request) {
        $returnValue = false;
        
        if ($request->hasHeader('Content-Type')) {
            $contentType = $request->getHeader('Content-Type');
            //TODO - not safe
            if ($contentType !== 'application/x-www-form-urlencoded') {
                $returnValue = true;
            }
        }

        // Don't sign POST fields if the request uses POST fields and no files
        if ($request->getFileCount() == 0) {
            $returnValue = false;
        }

        return $returnValue;
    }


    /**
     * TODO make this return a new Request
     * @param Request $request
     * @return Request
     */
    public function signRequest(Request $request) {

        $requestParams = $this->getRequestParamsToSign($request);
        $oauthParams = $this->getOauthParams($this->generateNonce($request));
        $params = array_merge($requestParams, $oauthParams);

        ksort($params);
        
        $baseString = $this->createBaseString(
            $request,
            $this->prepareParameters($params)
        );

        uksort($oauthParams, 'strcmp');

        $oauthParams['oauth_signature'] = $this->getSignature($baseString, $params);

        if ($this->oauthConfig->request_method === self::REQUEST_METHOD_HEADER) {
            return $this->createHeaderSignedRequest($request, $oauthParams);
        }

        if ($this->oauthConfig->request_method === self::REQUEST_METHOD_QUERY) {
            return $this->createQuerySignedRequest($request, $oauthParams);
        }

        throw new ArtaxServiceException(sprintf(
            'Invalid request_method "%s"',
            $this->oauthConfig->request_method
        ));
    }

    /**
     * @param Request $request
     * @param $params
     * @return Request
     */
    function createHeaderSignedRequest(Request $request, $params) {
        list($header, $value) = $this->buildAuthorizationHeader($params);
        $request->setHeader($header, $value);

        return $request;
    }

    /**
     * @param Request $request
     * @param $params
     * @return Request
     */
    function createQuerySignedRequest(Request $request, $params) {
        $request->setQueryFields($params);
        return $request;
    }


    /**
     * @param Request $request
     * @return array
     */
    function getRequestParamsToSign(Request $request) {
        $params = [];
        
        if ($this->shouldPostFieldsBeSigned($request)) {
            //@TODO - not implemented
            $formFields = $request->getFormFields();
            $params += $formFields;
        }

        // Parse & add query string parameters as base string parameters
        //$queryString = Query::fromString((string)$request->getQuery());

        $uri = $request->getUri();
        $queryString = '';
        if ($questionMark = strpos($uri, '?')) {
            $queryString = substr($uri, $questionMark + 1);
        }

        $queryString = Query::fromString($queryString);
        $params += $queryString->getParams();

        return $params;
    }
    

    /**
     * Calculate signature for request
     *
     * @param Request    $request Request to generate a signature for
     * @param array      $params  Oauth parameters.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getSignature($baseString, array $params) {
        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        unset($params['oauth_signature']);

        if ($this->signature_method === 'HMAC-SHA1') {
            $result = $this->sign_HMAC_SHA1($baseString);
        }
        else if ($this->signature_method == 'RSA-SHA1') {
            $result = $this->sign_RSA_SHA1($baseString);
        }
        else if ($this->signature_method == 'PLAINTEXT') {
            $result = $this->sign_PLAINTEXT($baseString);
        }
        else {
            throw new ArtaxServiceException('Unknown signature method: '
                . $this->signature_method);
        }

        return base64_encode($result);
    }

    /**
     * Returns a Nonce Based on the unique id and URL.
     *
     * This will allow for multiple requests in parallel with the same exact
     * timestamp to use separate nonce's.
     *
     * @param \Amp\Artax\Request $request Request to generate a nonce for
     *
     * @return string
     */
    public function generateNonce(Request $request)
    {
        return sha1(uniqid('', true) . $request->getUri());
    }

    /**
     * Creates the Signature Base String.
     *
     * The Signature Base String is a consistent reproducible concatenation of
     * the request elements into a single string. The string is used as an
     * input in hashing or signing algorithms.
     *
     * @param \Amp\Artax\Request $request Request being signed
     * @param array            $params  Associative array of OAuth parameters
     *
     * @return string Returns the base string
     * @link http://oauth.net/core/1.0/#sig_base_example
     */
    protected function createBaseString(Request $request, array $params)
    {
        // Remove query params from URL. Ref: Spec: 9.1.2.
        //TODO - remove params properly, not this hack method
        $request = clone $request;
//        $request->setQueryFields([]);

        $uri = $request->getUri();
        $queryString = '';
        if ($questionMark = strpos($uri, '?')) {
            $uri = substr($uri, 0, $questionMark);
            $request->setUri($uri);
        }

//        $url = $request->getUri();
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return strtoupper($request->getMethod())
            . '&' . rawurlencode($uri)
            . '&' . rawurlencode($query);
    }

    /**
     * Convert booleans to strings, removed unset parameters, and sorts the array
     *
     * @param array $data Data array
     *
     * @return array
     */
    private function prepareParameters($data)
    {
        // Parameters are sorted by name, using lexicographical byte value
        // ordering. Ref: Spec: 9.1.1 (1).
        uksort($data, 'strcmp');

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    private function sign_HMAC_SHA1($baseString)
    {        
        $key = rawurlencode($this->oauthConfig->getConsumerSecret())
            . '&' . rawurlencode($this->token_secret);

        return hash_hmac('sha1', $baseString, $key, true);
    }

    private function sign_RSA_SHA1($baseString)
    {
        if (!function_exists('openssl_pkey_get_private')) {
            throw new \RuntimeException('RSA-SHA1 signature method '
                . 'requires the OpenSSL extension.');
        }

        $privateKey = openssl_pkey_get_private(
            file_get_contents($this->consumer_secret),
            $this->consumer_secret
        );

        $signature = false;
        openssl_sign($baseString, $signature, $privateKey);
        openssl_free_key($privateKey);

        return $signature;
    }

    private function sign_PLAINTEXT($baseString)
    {
        return $baseString;
    }

    /**
     * Builds the Authorization header for a request
     *
     * @param array $params Associative array of authorization parameters.
     *
     * @return array
     */
    private function buildAuthorizationHeader(array $params)
    {
        foreach ($params as $key => $value) {
            $params[$key] = $key . '="' . rawurlencode($value) . '"';
        }

        if ($this->realm) {
            array_unshift(
                $params,
                'realm="' . rawurlencode($this->realm) . '"'
            );
        }

        return ['Authorization', 'OAuth ' . implode(', ', $params)];
    }

    /**
     * Get the oauth parameters as named by the oauth spec
     *
     * @param string     $nonce  Unique nonce
     * @param array      $config Options of the plugin.
     *
     * @return array
     */
    private function getOauthParams($nonce)
    {
        $params = [
            'oauth_nonce'            => $nonce,
            'oauth_timestamp'        => time(),
        ];

        if (isset($this->oauth_token)) {
            $params['oauth_token'] = $this->oauth_token;
        }

        $params = $this->oauthConfig->toArray($params);

        return $params;
    }
}
