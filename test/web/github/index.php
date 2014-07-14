<?php

require "githubBootstrap.php";

use AABTest\GithubAPI\GithubAPI;
use AABTest\Github\AccessResponse;

use ArtaxApiBuilder\Service\StoredLink;


echo <<< END

<html>
<body>
<h3><a href='/'>Oauth test home</a> </h3>
END;


/** @var \AABTest\Github\AccessResponse */
$accessResponse = getSessionVariable('githubAccess');

//These actions need to be done before the rest of the page.
$action = getVariable('action');
switch ($action) {

    case('delete') : {
        unsetSessionVariable('githubAccess');
        break;
    }
    case('revoke') : {
        revokeAuthority($accessResponse);
        break;
    }
}




try {

    if ($accessResponse == null) {
        echo "<p>You are not github authorised.</p>";

//        $scopes = [
//            \ArtaxApiBuilder\Service\Github::SCOPE_USER_EMAIL,
//            \ArtaxApiBuilder\Service\Github::SCOPE_ORG_READ,
//            \ArtaxApiBuilder\Service\Github::SCOPE_USER
//        ];
        //createOauthRequest();
        
        processUnauthorizedActions();
        
    }
    else {
      
        echo "<p>You are github authorised.</p>";

        try {
            processAction($accessResponse);
        }
        catch(AABTest\GithubAPI\GithubAPIException $gae) {
            echo "Exception caught: ".$gae->getMessage();
            var_dump($gae->getResponse()->getBody());
        }

        showActionLinks();
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


function processUnauthorizedActions() {

    $action = getVariable('action');

    switch($action) {

        case('makeOauthRequest'): {
            makeOauthRequest();
            break;
        }
        
        
        default: {
            showScopesForm();
            break;
        }
    }
}


function processAction(AccessResponse $accessResponse) {

    $action = getVariable('action');

    if ($action === 'delete') {
        
    }
//    else if ($action == 'revoke') {
//        echo "Not implemented yet";
//    }


    switch($action) {
        case('addEmail'): {
            processAddEmail($accessResponse);
            break;
        }
            
        case ('showMoreResults'): {
            showMoreResults($accessResponse);
            break;
        }

        case('showAddEmailForm'): {
            showAddEmailForm();
            break;
        }

        case('showAuthorizations'): {
            showAuthorizations($accessResponse);
            break;
        }

        case('showEmails'): {
            showGithubEmails($accessResponse);
            break;
        }

        case('showRepoCommits'): {
            showRepoCommits($accessResponse, 'Danack', 'Auryn');
            break;
        }

        case('showRepoTags'): {
            showRepoTags($accessResponse, 'Danack', 'Auryn');
            break;
        }
    }
}



function showActionLinks() {

    $actions = [
        'showEmails' => 'Show emails',
        'showAddEmailForm' => 'Add email',
        'showRepoTags' => 'List repo tags',
        'showRepoCommits' => 'List repo commits',
        'showAuthorizations' => 'Show authorizations',
        'delete' => 'Forget authority',
        'revoke' => 'Revoke authority',
    ];
    
    foreach ($actions as $action => $description) {
        printf(
            "<a href='/github/index.php?action=%s'>%s</a> ",
            $action,
            $description
        );
    }
}


function getAuthorisations() {
    
    //Check to see if there is an authorisations object in APCU
    // if so return it, otherwise
    
    //Get all of the authorisations.
    //Store them in APCU
    
    return [];
}


function processAddEmail($accessResponse) {

    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);

    $newEmail = getVariable('email');
    
    $emailCommand = $api->addUserEmails(
        'token '.$accessResponse->accessToken,
        [$newEmail]
    );
    
    $allowedScopes = getAuthorisations();
    $emailCommand->checkScopeRequirement($allowedScopes);

    $emailCommand->execute();

    $request = $emailCommand->createRequest();

    $request->setBody(json_encode(["Dantheman@example.com"]));

    $response = $emailCommand->dispatch($request);
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

function showGithubEmails(AABTest\Github\AccessResponse $accessResponse) {
    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);
    $emailCommand = $api->getUserEmails('token '.$accessResponse->accessToken);
    $emailList = $emailCommand->execute();

    foreach ($emailList->emails as $email) {
        echo "Address ".$email->email." primary = ".$email->primary."<br/>";
    }
}


function showAuthorizations(AABTest\Github\AccessResponse $accessResponse) {
    $api = new GithubAPI(GITHUB_USER_AGENT);
    $authCommand = $api->getAuthorizations('token '.$accessResponse->accessToken);
    $authorisations = $authCommand->execute();

    foreach($authorisations->getIterator() as $authorisation) {
        echo "Application: ".$authorisation->application."<br/>";
        echo "Scopes:".implode($authorisation->scopes)."<br/>";
        echo "<br/>";
    }
}


function displayCommits(\AABTest\Github\Commits $commits) {

    echo "<table style='font-size: 12px'>";
    echo "<tr><th>Author</th><th style='width: 500px'>Message</th><th>Date</th></tr>";

    foreach ($commits->getIterator() as $commit) {

        echo "<tr><td>";
        if ($commit->author) {
            printf(
                "<a href='%s'>%s</a>",
                $commit->author->url,
                $commit->author->login
            );
        }
        else {
            echo "Unknown";
        }
        echo "</td><td style='width: 500px'>";
        echo $commit->commitInfo->message;
        echo "</td><td>";
        echo $commit->commitInfo->committerDate;
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";

}



function displayAndSaveLinks(\Artax\Response $response) {
    $pager = new \ArtaxApiBuilder\Service\GithubLinkParser($response);
    $links = $pager->parseResponse();


    foreach ($links as $link) {
        $storedLink = new StoredLink($link);
        printf(
            "<a href='/github/index.php?action=showMoreResults&resultKey=%s'>%s</a><br/>",
            $storedLink->getKey(),
            $link->description
        );
    }
}

function showRepoCommits(AABTest\Github\AccessResponse $accessResponse, $username, $repo) {
    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);
    $command = $api->listRepoCommits('token '.$accessResponse->accessToken, $username, $repo);
    $command->setAuthor('Danack');
    $commits = $command->execute();
    
    displayCommits($commits);
    $response = $command->getResponse();
    displayAndSaveLinks($response);
}


function showMoreResults(AccessResponse $accessResponse) {

    $resultKey = getVariable('resultKey');
    
    if (!$resultKey) {
        echo "Couldn't read resultKey, can't show more results.";
        return;
    }
    
    $storedLink = StoredLink::createFromKey($resultKey);
    if (!$storedLink) {
        echo "Couldn't find storedLink from key $resultKey, can't show more results.";
        return;
    }

    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);
    $command = $api->listRepoCommitsPaginate(
        'token '.$accessResponse->accessToken,
        $storedLink->link->url
    );

    $commits = $command->execute();

    displayCommits($commits);
    $response = $command->getResponse();
    displayAndSaveLinks($response);
}


function revokeAuthority(AccessResponse $accessResponse) {

    
    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);
    $command = $api->revokeAllAuthority('token '.$accessResponse->accessToken, GITHUB_CLIENT_ID);

    $blah = $command->execute();
    
    var_dump($blah);
    
    
    echo "Diplomatic immunity, has been revoked?";

}


function showRepoTags(AABTest\Github\AccessResponse $accessResponse, $username, $repo) {
    $api = new \AABTest\GithubAPI\GithubAPI(GITHUB_USER_AGENT);
    $command = $api->listRepoTags('token '.$accessResponse->accessToken, $username, $repo);
    $repoTags = $command->execute();
    foreach ($repoTags->getIterator() as $repoTag) {
        echo "Tag name: ".$repoTag->name." sha ".$repoTag->commitSHA."<br/>";
    }
}



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

function showScopesForm() {

    echo "<form action='/github/index.php' method='get'>";


    echo "<table width='750px'>";
    foreach (\ArtaxApiBuilder\Service\Github::$scopeList as $scope => $description) {

        echo "<tr>";

        echo "<td valign='top'>";
        echo "<input type='checkbox' name='scopes[]' value='$scope'/>";
        echo "</td>";

        echo "<td valign='top'>";
        echo "$scope";
        echo "</td>";

        echo "<td valign='top'>";
        echo $description;
        echo "</td>";
        
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td valign='top' colspan='2' align='right'>";
    echo "</td><td>";
    echo "<input type='submit' value='Make auth request'/>";
    echo "</td>";    
    echo "</tr>";
    
    echo "</table>";


    echo "
        <input type='hidden' name='action' value='makeOauthRequest' />
        
    </form>";
    

}


function makeOauthRequest() {
    $scopes = getVariable('scopes');
    echo "<p>Requesting scopes: ".implode(', ', $scopes).".</p>";
    showOauthRequest($scopes);
}


/**
 * 
 */
function showOauthRequest($scopes) {
    
    try {
        
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

 