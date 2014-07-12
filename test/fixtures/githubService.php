<?php


return array (

    "name" => "Github",
    "baseUrl" => "https://api.github.com",
    "description" => "Flickr API using Guzzle as a backend",
    "operations" => array(
        
        'defaultGetOperation' => array(
            "httpMethod" => "GET",
            "parameters" => array(
                'Accept' => array(
                    "location" => "header",
                    "description" => "",
                    'default' =>  'application/json',
                ),
            )
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
            'responseClass' => 'AABTest\Github\Emails',
            'httpMethod' =>  'GET',
            'parameters' => array(
                //No parameters - it works off the oauth bearer token
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


        //List your repositories
        'listUserRepos' => array(
            'uri' => '/user/repos',
            'extends' => 'defaultGetOauthOperation',
            'summary' => 'List repositories for the authenticated user. Note that this does not include repositories owned by organizations which the user can access. You can list user organizations and list organization repositories separately.',
            'parameters' => array(
                'type' => array(
                    "location" => "json",
                    "description" => 'Can be one of all, owner, public, private, member. Default: all',
                    'default' => 'all',
                    'optional' => true,
                ),

                'sort' => array(
                    "location" => "json",
                    'type' => 'string',
                    'description' => 'Can be one of created, updated, pushed, full_name. Default: full_name',
                    
                    'default' => 'full_name',
                    'optional' => true,
                ),
                'direction' => array(
                    "location" => "json",
                    'type' => 'string',
                    'description' => 'Can be one of asc or desc. Default: when using full_name: asc; otherwise desc',
                    //Don't set a default value, github chooses a sensible default based on the 
                    //sort type
                    'optional' => true,
                ),
            )
        ),

        
        //List user repositories
        //GET /users/:username/repos
        //        type	string	Can be one of all, owner, member. Default: owner
        //sort	string	Can be one of created, updated, pushed, full_name. Default: full_name
        //direction	string	Can be one of asc or desc. Default: when using full_name: asc, otherwise desc

        //List organization repositories
        //GET /orgs/:org/repos
        //type	string	Can be one of all, public, private, forks, sources, member. Default: all
        
        //List all public repositories
        //Note: Pagination is powered exclusively by the since parameter. Use the Link header to get the URL for the next page of repositories.
        //GET /repositories


        //Create
        //POST /user/repos
        
        
        //Get
        //GET /repos/:owner/:repo
        

        //Edit
        //PATCH /repos/:owner/:repo
//        name	string	Required. The name of the repository
//description	string	A short description of the repository
//homepage	string	A URL with more information about the repository
//private	boolean	Either true to make the repository private, or false to make it public. Creating private repositories requires a paid GitHub account. Default: false
//has_issues	boolean	Either true to enable issues for this repository, false to disable them. Default: true
//has_wiki	boolean	Either true to enable the wiki for this repository, false to disable it. Default: true
//has_downloads	boolean	Either true to enable downloads for this repository, false to disable them. Default: true
//default_branch	String	Updates the default branch for this repository.
        
        
        
        //List contributors
        //GET /repos/:owner/:repo/contributors

        //List languages
        //GET /repos/:owner/:repo/languages

        //List Teams
        //GET /repos/:owner/:repo/teams

        //List Tags
        //GET 
        'listRepoTags' => array(
            'uri' => '/repos/{owner}/{repo}/tags',  //'uri' => '/repos/:owner/:repo/tags',
            'extends' => 'defaultGetOauthOperation',
            'summary' => 'List tags for a repository. Response can be paged. This can be used either as a authed request (for private repos and higher rate limiting), or as unsigned, (public only, lower limit).',
            "responseClass" => 'AABTest\Github\RepoTags',

            'parameters' => array(
                'owner' => array(
                    "location" => "uri",
                ),
                'repo' => array(
                    "location" => "uri",
                )
            ),
        ),
        
        

        //List Branches
        //GET /repos/:owner/:repo/branches/:branch
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