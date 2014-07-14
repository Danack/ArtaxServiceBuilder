<?php

use ArtaxApiBuilder\Service\OauthConfig;


class GithubResponseTest extends \PHPUnit_Framework_TestCase { 
  //  extends \ArtaxApiBuilder\TestBase {


    //TODO Add checks on the data.
    public function additionProvider() {
        return array(
            ['getSingleCommit.txt', 'AABTest\Github\Commit'],
            ['listCommitsOnARepository.txt', 'AABTest\Github\Commits'],
            ['listCommits.txt', 'AABTest\Github\Commits'],
            
            ['listEmailAddressesForUser.txt', 'AABTest\Github\Emails'],
            ['addEmailAddresses.txt', 'AABTest\Github\Emails'],
            ['listRepoTags.txt', 'AABTest\Github\RepoTags'],
            ['listAuthorizations.txt', 'AABTest\Github\Authorizations']
        );
    }

    /**
     * @dataProvider additionProvider
     */
    function testDataParsing($dataFile, $expectedClassname) {
        $jsonData = file_get_contents(__DIR__.'/../fixtures/data/github/'.$dataFile);
        $data = json_decode($jsonData, true);
        $instance = $expectedClassname::createFromJson($data);
        
        $this->assertInstanceOf(
            $expectedClassname,
            $instance
        );
    }
    
    
   

}

 