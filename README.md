## ArtaxApiBuilder

Creates a library to consume an API from a Guzzle-like service description.

## Why?

Writing a service to consume an HTTP API is not a fun task. Either there will be a lot of tedious boilerplate code that need to be written for each operation in the API, or you write the operations as generic methods that lose all type information.


Not only is writing the library in the first place not a fun task, but maintaining it when the HTTP API provider adds methods or subtly changes existing methods is also tedious.

Wouldn't it be nicer to be able to just define an operation like:

```
    "addUserEmails" => array(
        "uri" => "https://api.github.com/user/emails",
        'extends' => 'defaultOauthOperation',
        'summary' => 'Get users email addresses',
        'scopes' => [
            [\ArtaxApiBuilder\Service\Github::SCOPE_USER],
        ],
        'responseClass' => 'Github\Emails',
        'httpMethod' =>  'POST',
        'parameters' => array(
            'emails' => array(
                "location" => "json",
                "description" => "Array of the emails to add",
            ),
        ),
    ),
```

And then have all the service code generated and usable like this:


```
    $api = new GithubAPI(GITHUB_USER_AGENT);
    $emailCommand = $api->addUserEmails(
        $accessToken,
        [$newEmail1, $newEmail2]
    );
    
    $currentEmails = $emailCommand->execute();
    
    //$currentEmails is of class \Github\Emails
    foreach ($currentEmails as $email) {
        printf("Address %s verified %s primary %s ",
            $email->address.
            $email->verified
            $email->primary
        );
    ]
```
    
    


## Why not Guzzle service

* Underlying HTTP implementation in Curl/PHP streams isn't that great.

* Still a massive code base that doesn't really do that much.

* Untestable / unmockable.
 
* A service generated as code allows you to debug, extend or generally tart around with the code as necessary. Once the code is generated it's just there and completely modifiable (if you wish), rather than requiring everything being piped through Guzzle.

* Smaller memory requirement (and memory is PHP's Achilles heel) for using the generated service.

* Type-hinting all the things for great Justice.

* Better exposure of underlying mechanisms. Although Guzzle is nice doing everything for you, having direct access to the request and/or responses is a pretty powerful technique for when people fuck up their API, and you need to hack around their stupidity (Github, I'm looking at you).


## TODO

* errorResponses - there is pretty much no error handling at the moment. This would be a good thing to be able to define at the API level rather than having to write code to support it.

* Unexpected responses as errors or exceptions - tbh this library is going to have to support both, as some cases exceptions are correct, and in others checking the response class type would be correct.

* filters - yep, going to need filters for variables in. Currently there is just a translate filter.

* includes - not the most vital thing in the world, but it would be nice to be able to split a service description up into manageable chunks.

* apiVersion - yep. Need some api versioning.


## Not implemented

* additionalParameters - Having explicit parameters (`$foo->setBar($bar)`) is far better than having faith based parameters (`$foo->setParams(['bar' => $bar]);`) and so they aren't supported. 

* responseModel - I haven't used this and don't particularly like the idea behind it, as there seem to be better (or at least looser coupled) ways of turning the responses into objects.  

* responseBody - this is not necessary when you have control of the request.


## Danack/Code vs Zend/Code


Please note, the fork Danack/Code is almost exactly the same as Zend/Code, except that it has some bug fixes which will only be released in the next major version of Zend/Code. As this won't happen for the foreseeable future, this library currently uses my fork with the fixes applied. The plan is to go back to the mainstream version of Zend/Code at the next major release..