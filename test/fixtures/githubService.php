<?php


return array (

    "name" => "Github",
    "baseUrl" => "https://api.github.com/",
    "description" => "Flickr API using Guzzle as a backend",
    "operations" => array(


        //Accept: application/json
        
        'defaultGetOperation' => array(
            "httpMethod" => "GET",
            "parameters" => array(
                'Accept' => array(
                    "location" => "header",
                    "description" => "",
                    'default' =>  'application/json',
                ),
                
                //Accept: application/vnd.github.v3+json
            )
                ///user/emails
        ),

        'defaultGetOauthOperation' => array(
            "httpMethod" => "GET",
            "parameters" => array(
                'Accept' => array(
                    "location" => "header",
                    "description" => "",
                    'default' =>  'application/json',
                ),
                'Authorization' => array(
                    "location" => "header",
                    "description" => "The shitty oauth2 bearer token", ////: token OAUTH-TOKEN
                ),
            )
        ),
        
        //Accept: application/json
        'accessToken' => [
            'extends' => 'defaultGetOperation',
            'uri' => 'https://github.com/login/oauth/access_token',
            'httpMethod' => 'POST',
            "responseClass" => 'AABTest\Github\AccessResponse',
            'parameters' => [
                'client_id' => [
                    'description' => 'string Required. The client ID you received from GitHub when you registered.'
                ],
                'client_secret' => [
                    'description' => 'string Required. The client secret you received from GitHub when you registered.'
                ],
                'code' => [
                    'description' =>  'string Required. The code you received as a response to Step 1.'
                ],
                'redirect_uri' => [
                    'description' =>  'string The URL in your app where users will be sent after authorization. See details below about redirect urls.'
                ]
            ]
        ],

        "getUserEmails" => array(
            "uri" => "https://api.github.com/user/emails",
            'extends' => 'defaultGetOauthOperation',
            'summary' => 'Get users email addresses',
            //'needsSigning' => true,
            'responseClass' => 'AABTest\Github\Emails',
            'httpMethod' =>  'GET',
            'parameters' => array(
                //No parameters? It works of the oauth?
            ),
        ),

        "addUserEmails" => array(
            "uri" => "https://api.github.com/user/emails",
            'extends' => 'defaultGetOauthOperation',
            'summary' => 'Get users email addresses',
            //'needsSigning' => true,
            
//            'permissions' => [
//                [\ArtaxApiBuilder\Service\Github::PERMISSION_EMAIL_WRITE],
//                [\ArtaxApiBuilder\Service\Github::PERMISSION_EMAIL_ADMIN]
//            ],

            //TODO - It would be better to have scopes and permissions combined?
            'scopes' => [
                [\ArtaxApiBuilder\Service\Github::SCOPE_USER],
            ],
            
            'responseClass' => 'AABTest\Github\Emails',
            'httpMethod' =>  'POST',
            'parameters' => array(
                'emails' => array(
                    "location" => "json",
                    "description" => "Array of the emails to add",
                ),
            ),
        ),
    ),
);





// Github uses web base authentication for Oauth2... which means you don't need to sign the
// Authorisation request.
//        'authorize' => [
//            'uri' => 'https://github.com/login/oauth/authorize',
//            "responseClass" => 'AABTest\Github\AuthResponse',
//            "parameters" => [
//                'client_id' => [
//                    'description' => 'string The client ID you received from GitHub when you registered.',
//                ],
//                'redirect_uri' => [
//                    'description' => 'string The URL in your app where users will be sent after authorization. See details below about redirect urls.'
//                ],
//                'scope' => [
//                    'description' =>  'string A comma separated list of scopes. If not provided, scope defaults to an empty list of scopes for users that don’t have a valid token for the app. For users who do already have a valid token for the app, the user won’t be shown the OAuth authorization page with the list of scopes. Instead, this step of the flow will automatically complete with the same scopes that were used last time the user completed the flow.'],
//                'state' => [
//                    'description' => 'string An unguessable random string. It is used to protect against cross-site request forgery attacks.'
//                ],
//            ],
//        ],
//
// 