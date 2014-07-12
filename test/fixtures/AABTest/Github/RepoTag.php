<?php


namespace AABTest\Github;

use AABTest\DataMapper;


class RepoTag {

    use DataMapper;
    
    public $name;
    public $zipballURL;
    public $tarballURL;
    public $commitSHA;
    public $commitURL;


    static protected $dataMap = array(
        ['name', 'name'],
        ['zipballURL', 'zipball_url'],
        ['tarballURL', 'tarball_url'],
        
        ['commitSHA', ['commit', 'sha']],
        ['commitURL', ['commit', 'url']],
    );
}

 