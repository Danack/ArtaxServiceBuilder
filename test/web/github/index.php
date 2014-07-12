<?php

require "githubBootstrap.php";

$action = getVariable('action');

if ($action === 'delete') {
    unsetSessionVariable('githubAccess');
}
else if ($action == 'revoke') {
    echo "Not implemented yet";
}

echo <<< END

<html>
<body>
<h3><a href='/'>Oauth test home</a> </h3>
END;


/** @var \AABTest\Github\AccessResponse */
$accessResponse = getSessionVariable('githubAccess');

try {

    if ($accessResponse == null) {
        echo "<p>You are not github authorised.</p>";
        createOauthRequest();
    }
    else {
        
        if ($action == 'addEmail') {
            processAddEmail($accessResponse);
        }
        
        echo "<p>You are github authorised.</p>";
        showGithubStatus($accessResponse);
        showAddEmailForm();
        echo "<p><a href='/github/index.php?action=delete'>Delete authority</a></p>";
        echo "<p><a href='/github/index.php?action=revoke'>Revoke authority</a></p>";


        try {
            showRepoTags($accessResponse, 'Danack', 'Auryn');
        }
        catch(AABTest\GithubAPI\GithubAPIException $gae) {
            echo "Exception caught: ".$gae->getMessage();
            var_dump($gae->getResponse()->getBody());
        }
        
    }
}
catch(AABTest\GithubAPI\GithubAPIException $gae) {
    echo "Exception caught: ".$gae->getMessage();
    var_dump($gae->getResponse()->getBody());
}
    
    
echo <<< END

</body>
</html>

END;


function getAuthorisations() {
    
    //Check to see if there is an authorisations object in APCU
    // if so return it, otherwise
    
    //Get all of the authorisations.
    //Store them in APCU
    
    return [];
}


function processAddEmail($accessResponse) {

    $api = new \AABTest\GithubAPI\GithubAPI();

    $newEmail = getVariable('email');
    
    $emailCommand = $api->addUserEmails(
        'token '.$accessResponse->accessToken,
        [$newEmail]
    );
    
    $allowedScopes = getAuthorisations();
    $emailCommand->checkScopeRequirement($allowedScopes);

    $request = $emailCommand->createRequest();

    $request->setBody(json_encode(["Dantheman@example.com"]));

    echo "Request uri is ".$request->getUri()."<br/>";
    echo "Body is:";
    var_dump($request->getBody());
    echo "Request method is ".$request->getMethod()."<br/>";

    var_dump($request->getAllHeaders());

    $response = $emailCommand->dispatch($request);

    var_dump($response);
}



function showAddEmailForm() {
    $output = <<< END

    <form name='input' action='/github/index.php' method='get'>
        Add email address <br/><input type="text" size='80' name="email"/><br/>
        <input type='hidden' name='action' value='addEmail' />
        <input type="submit" value="Add"/>
    </form>
END;
    
    echo $output;
}

function showGithubStatus(AABTest\Github\AccessResponse $accessResponse) {
    $api = new \AABTest\GithubAPI\GithubAPI();
    $emailCommand = $api->getUserEmails('token '.$accessResponse->accessToken);
    $emailList = $emailCommand->execute();

    foreach ($emailList->emails as $email) {
        echo "Address ".$email->email." primary = ".$email->primary."<br/>";
    }
}


function showRepoTags(AABTest\Github\AccessResponse $accessResponse, $username, $repo) {
    $api = new \AABTest\GithubAPI\GithubAPI();
    $command = $api->listRepoTags('token '.$accessResponse->accessToken, $username, $repo);
    $repoTags = $command->execute();
    foreach ($repoTags->getIterator() as $repoTag) {
        echo "Tag name: ".$repoTag->name." sha ".$repoTag->commitSHA."<br/>";
    }

    $response = $command->getResponse();
    
    $headers = $response->getAllHeaders();
    var_dump($headers);
}


//'X-RateLimit-Limit' => 5000
//'X-RateLimit-Remaining' => 4989
//'X-RateLimit-Reset' => 1405170314

//'X-RateLimit-Limit'  => '60'
//'X-RateLimit-Remaining' => '59'
//'X-RateLimit-Reset' => '1405174023'



/**
 * @param $clientID
 * @param $scopes
 * @param $redirectURI
 * @param $secret
 * @return string
 */
function createAuthURL($clientID, $scopes, $redirectURI, $secret) {
    $url = "https://github.com/login/oauth/authorize";
    $url .= '?client_id='.urlencode($clientID);
    $url .= '&scope='.urlencode(implode(',', $scopes));
    $url .= '&redirect_uri='.urlencode($redirectURI);
    $url .= '&state='.urlencode($secret);

    return $url;
}


/**
 * 
 */
function createOauthRequest() {
    
    try {
        $scopes = [
            \ArtaxApiBuilder\Service\Github::SCOPE_USER_EMAIL,
            \ArtaxApiBuilder\Service\Github::SCOPE_ORG_READ,
            \ArtaxApiBuilder\Service\Github::SCOPE_USER
        ];

        $unguessable = openssl_random_pseudo_bytes(16);
        $unguessable = bin2hex($unguessable);

        $authURL = createAuthURL(
            GITHUB_CLIENT_ID,
            $scopes,
            "http://".SERVER_HOSTNAME."/github/return.php",
            $unguessable
        );

        setSessionVariable('oauthUnguessable', $unguessable);

        echo sprintf("Please click <a href='%s'>here to auth</a> with github. ", $authURL);
    }
    catch(\AABTest\FlickrAPI\FlickrAPIException $fae) {
        echo "FlickrAPIException response body.";
        var_dump($fae->getResponse()->getBody());
    }
}

 