<?php

require "githubBootstrap.php";

echo <<< END

<html>

<body>

<h3><a href='/'>Oauth test home</a> </h3>

<p>Checking oauth result</p>

<p>
END;

$currentOauthRequest = getSessionVariable('oauthRequest');

checkAuthResult();

echo <<< END

    </p>

    <p>
        Back to <a href='/github/index.php'>github start page</a>.
    </p>

</body>
</html>

END;


function checkAuthResult() {
    $code = getVariable('code', FALSE);
    $state = getVariable('state', FALSE);

    $oauthUnguessable = getSessionVariable('oauthUnguessable', null);

    if (!$code ||
        !$state ||
        !$oauthUnguessable) {
        return;
    }

    if ($state != $oauthUnguessable) {
        //Miss-match on what we're tring to validated.
        echo "Miss-match on secret'";
        return;
    }

    try {
        $api = new \AABTest\GithubAPI\GithubAPI();

        $command = $api->accessToken(
            GITHUB_CLIENT_ID,
            GITHUB_CLIENT_SECRET,
            $code,
            "http://".SERVER_HOSTNAME."/github/return.php"
        );

        $response = $command->execute();
        setSessionVariable('githubAccess', $response);
        
        echo "You are now authed for the following scopes:<br/>";
        
        foreach ($response->scopes as $scope) {
            echo $scope."<br/>";
        }
    }
    catch(\AABTest\GithubAPI\GithubAPIException $fae) {
        echo "Exception processing response: ".$fae->getMessage();
    }
}


 



 




 