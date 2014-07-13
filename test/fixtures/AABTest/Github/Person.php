<?php


namespace AABTest\Github;


use ArtaxApiBuilder\Operation;
use Artax\Response;
use AABTest\DataMapper;



class Person {

    use DataMapper;

    public $login;
    public $id;
    public $avatarURL;
    public $gravatarID;
    public $url;
    public $followersURL;
    public $followingURL;
    public $gistsURL;
    public $starredURL;
    public $organizationsURL;
    public $reposURL;
    public $eventsURL;
    public $receivedEventsURL;
    public $type;
    public $siteAdmin;
    
    static protected $dataMap = array(
        ['login', 'login'],
        ['id', 'id'],
        ['avatarURL', 'avatar_url'],
        ['gravatarID', 'gravatar_id'],
        ['url', 'url'],
        ['followersURL', 'followers_url'],
        ['followingURL', 'following_url'],
        ['gistsURL', 'gists_url'],
        ['starredURL', 'starred_url'],
        ['organizationsURL', 'organizations_url'],
        ['reposURL', 'repos_url'],
        ['eventsURL', 'events_url'],
        ['receivedEventsURL', 'received_events_url'],
        ['type', 'type'],
        ['siteAdmin', 'site_admin'],
    );



//"author": {
//"login": "octocat",
//"id": 1,
//"avatar_url": "https://github.com/images/error/octocat_happy.gif",
//"gravatar_id": "somehexcode",
//"url": "https://api.github.com/users/octocat",
//"html_url": "https://github.com/octocat",
//"followers_url": "https://api.github.com/users/octocat/followers",
//"following_url": "https://api.github.com/users/octocat/following{/other_user}",
//"gists_url": "https://api.github.com/users/octocat/gists{/gist_id}",
//"starred_url": "https://api.github.com/users/octocat/starred{/owner}{/repo}",
//"subscriptions_url": "https://api.github.com/users/octocat/subscriptions",
//"organizations_url": "https://api.github.com/users/octocat/orgs",
//"repos_url": "https://api.github.com/users/octocat/repos",
//"events_url": "https://api.github.com/users/octocat/events{/privacy}",
//"received_events_url": "https://api.github.com/users/octocat/received_events",
//"type": "User",
//"site_admin": false
//},
    
}

 