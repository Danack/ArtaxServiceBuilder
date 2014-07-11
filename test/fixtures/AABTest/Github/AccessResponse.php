<?php


namespace AABTest\Github;

use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;

use AABTest\GithubAPI\GithubAPIException;


class AccessResponse {

    use DataMapper;

    public $accessToken;
    public $scopes;
    public $tokenType;

    static protected $dataMap = array(
        ['accessToken', 'access_token'],
        ['scopes', 'scope'],
        ['tokenType', 'token_type'],
    );

    static function createFromResponse(Response $response, Operation $operation) {
        $json = $response->getBody();
        $data = json_decode($json, true);

        if (isset($data['error']) == true) {
            $errorDescription = 'error_description not set, so cause unknown.';

            if (isset($data["error_description"]) == true) {
                $errorDescription = $data["error_description"];
            }
            
            throw new GithubAPIException($response, 'Github error: '.$errorDescription);
        }
        
        $instance =  self::createFromJson($data);
        $instance->scopes = explode(',', $instance->scopes);

        return $instance;
    }
}
