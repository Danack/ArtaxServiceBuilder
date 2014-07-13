<?php


namespace AABTest\Github;

use AABTest\DataMapper;


class CommitInfo {

    use DataMapper;

    public $url;

    public $authorName;
    public $authorEmail;
    public $authorDate;//why the fuck is this under author?
    
    public $committerName;
    public $committerEmail;
    public $committerDate;
    
    public $message;
    public $treeURL;
    public $treeSHA;
    public $commentCount;

    static protected $dataMap = array(
        ['url', 'url'],
        ['authorName', ['author', 'name']],
        ['authorEmail', ['author', 'email']],
        ['authorDate', ['author', 'date']],
        ['committerName', ['committer', 'name']],
        ['committerEmail', ['committer', 'email']],
        ['committerDate', ['committer', 'date']],
        ['message', 'message'],
        ['treeURL', ['tree', 'url']],
        ['treeSHA', ['tree', 'sha']],
        ['commentCount', 'comment_count'],
    );
    
    
//"url": "https://api.github.com/repos/octocat/Hello-World/git/commits/6dcb09b5b57875f334f61aebed695e2e4193db5e",
//"author": {
//"name": "Monalisa Octocat",
//"email": "support@github.com",
//"date": "2011-04-14T16:00:49Z"
//},
//"committer": {
//    "name": "Monalisa Octocat",
//        "email": "support@github.com",
//        "date": "2011-04-14T16:00:49Z"
//      },
//      "message": "Fix all the bugs",
//      "tree": {
//    "url": "https://api.github.com/repos/octocat/Hello-World/tree/6dcb09b5b57875f334f61aebed695e2e4193db5e",
//        "sha": "6dcb09b5b57875f334f61aebed695e2e4193db5e"
//      },
//      "comment_count": 0
//    },
    
}

 