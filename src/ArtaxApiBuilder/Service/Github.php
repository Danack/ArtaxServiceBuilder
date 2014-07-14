<?php


namespace ArtaxApiBuilder\Service;


// Fark RFC 5988
// http://tools.ietf.org/html/rfc5988

class Github {

    //(no scope) Grants read-only access to public information (includes public user profile info, public repository info, and gists)
    
    const SCOPE_USER = 'user'; //	Grants read/write access to profile info only. Note that this scope includes user:email and user:follow.
    
    const SCOPE_USER_EMAIL = 'user:email'; //	Grants read access to a user’s email addresses.
    
    const SCOPE_USER_FOLLOW = 'user:follow'; //	Grants access to follow or unfollow other users.
    
    const SCOPE_PUBLIC_REPO = 'public_repo'; //	Grants read/write access to code, commit statuses, and deployment statuses for public repositories and organizations.
    
    const SCOPE_REPO = 'repo'; //	Grants read/write access to code, commit statuses, and deployment statuses for public and private repositories and organizations.
    
    const SCOPE_REPO_DEPLOYMENT = 'repo_deployment'; //	Grants access to deployment statuses for public and private repositories. This scope is only necessary to grant other users or services access to deployment statuses, without granting access to the code.
    
    const SCOPE_REPO_STATUS = 'repo:status'; //	Grants read/write access to public and private repository commit statuses. This scope is only necessary to grant other users or services access to private repository commit statuses without granting access to the code.
    
    const SCOPE_DELETE_REPO = 'delete_repo'; //	Grants access to delete adminable repositories.

    const SCOPE_NOTIFICATIONS = 'notifications'; //	Grants read access to a user’s notifications. repo also provides this access.

    const SCOPE_GIST = 'gist'; //	Grants write access to gists.

    const SCOPE_REPO_HOOK_READ = 'read:repo_hook'; //	Grants read and ping access to hooks in public or private repositories.
    const SCOPE_REPO_HOOK_WRITE = 'write:repo_hook'; //	Grants read, write, and ping access to hooks in public or private repositories.
    const SCOPE_REPO_HOOK_ADMIN = 'admin:repo_hook'; //	Grants read, write, ping, and delete access to hooks in public or private repositories.

    const SCOPE_ORG_READ = 'read:org'; //	Read-only access to organization, teams, and membership.
    const SCOPE_ORG_WRITE = 'write:org'; //	Publicize and unpublicize organization membership.
                            //DJA - What the fuck does this ^^ mean?
    const SCOPE_ORG_ADMIN = 'admin:org'; //	Fully manage organization, teams, and memberships.

    const SCOPE_PUBLIC_KEY_READ = 'read:public_key'; //	List and view details for public keys.
    const SCOPE_PUBLIC_KEY_WRITE  = 'write:public_key'; //	Create, list, and view details for public keys.
    const SCOPE_PUBLIC_KEY_ADMIN = 'admin:public_key'; //	Fully manage public keys.
    
    static public $scopeList = [
        self::SCOPE_USER => "Grants read/write access to profile info only. Note that this scope includes user:email and user:follow.",
        self::SCOPE_USER_FOLLOW => "Grants access to follow or unfollow other users.",
        self::SCOPE_PUBLIC_REPO => "Grants read/write access to code, commit statuses, and deployment statuses for public repositories and organizations.",
        self::SCOPE_REPO => "Grants read/write access to code, commit statuses, and deployment statuses for public and private repositories and organizations.",
        self::SCOPE_REPO_DEPLOYMENT => "Grants access to deployment statuses for public and private repositories. This scope is only necessary to grant other users or services access to deployment statuses, without granting access to the code.",
        self::SCOPE_REPO_STATUS => "Grants read/write access to public and private repository commit statuses. This scope is only necessary to grant other users or services access to private repository commit statuses without granting access to the code.",
        self::SCOPE_DELETE_REPO => "Grants access to delete adminable repositories.",
        self::SCOPE_NOTIFICATIONS => "Grants read access to a user’s notifications. repo also provides this access.",
        self::SCOPE_GIST => "Grants write access to gists.",
        self::SCOPE_REPO_HOOK_READ => "Grants read and ping access to hooks in public or private repositories.",
        self::SCOPE_REPO_HOOK_WRITE => "Grants read, write, and ping access to hooks in public or private repositories.",
        self::SCOPE_REPO_HOOK_ADMIN => "Grants read, write, ping, and delete access to hooks in public or private repositories.",
        self::SCOPE_ORG_READ => "Read-only access to organization, teams, and membership.",
        self::SCOPE_ORG_WRITE => "Publicize and unpublicize organization membership.",
        self::SCOPE_ORG_ADMIN => "Fully manage organization, teams, and memberships.",
        self::SCOPE_PUBLIC_KEY_READ => "List and view details for public keys.",
        self::SCOPE_PUBLIC_KEY_WRITE  => "Create, list, and view details for public keys.",
        self::SCOPE_PUBLIC_KEY_ADMIN => "Fully manage public keys.",
    ];

    const PERMISSION_EMAIL_READ = 'PERMISSION_EMAIL_READ';
    const PERMISSION_EMAIL_WRITE = 'PERMISSION_EMAIL_WRITE';
    const PERMISSION_EMAIL_ADMIN = 'PERMISSION_EMAIL_WRITE';

    const PERMISSION_PROFILE_READ = 'PERMISSION_PROFILE_READ'; 
    const PERMISSION_PROFILE_WRITE = 'PERMISSION_PROFILE_WRITE';
    const PERMISSION_PROFILE_ADMIN = 'PERMISSION_PROFILE_ADMIN';

    const PERMISSION_USER_FOLLOW_READ = 'PERMISSION_USER_FOLLOW_READ';
    const PERMISSION_USER_FOLLOW_WRITE = 'PERMISSION_USER_FOLLOW_WRITE';
    const PERMISSION_USER_FOLLOW_ADMIN = 'PERMISSION_USER_FOLLOW_ADMIN';
    
    //code, commit statuses, and deployment statuses
    
    const PERMISSION_CODE_READ = 'PERMISSION_CODE_READ';
    const PERMISSION_CODE_WRITE = 'PERMISSION_CODE_WRITE';
    const PERMISSION_CODE_ADMIN = 'PERMISSION_CODE_ADMIN';

    const PERMISSION_COMMIT_STATUS_READ = 'PERMISSION_COMMIT_STATUS_READ';
    const PERMISSION_COMMIT_STATUS_WRITE = 'PERMISSION_COMMIT_STATUS_WRITE';
    const PERMISSION_COMMIT_STATUS_ADMIN = 'PERMISSION_COMMIT_STATUS_ADMIN';

    const PERMISSION_DEPLOYMENT_STATUS_READ = 'PERMISSION_DEPLOYMENT_STATUS_READ';
    const PERMISSION_DEPLOYMENT_STATUS_WRITE = 'PERMISSION_DEPLOYMENT_STATUS_WRITE';
    const PERMISSION_DEPLOYMENT_STATUS_ADMIN = 'PERMISSION_DEPLOYMENT_STATUS_ADMIN';
    
    //shitfuck - this is public repos only.
    const PERMISSION_REPO_READ = 'PERMISSION_REPO_READ';
    const PERMISSION_REPO_WRITE = 'PERMISSION_REPO_WRITE';
    const PERMISSION_REPO_ADMIN = 'PERMISSION_REPO_ADMIN';

    const PERMISSION_ORG_READ = 'PERMISSION_ORG_READ';
    const PERMISSION_ORG_WRITE = 'PERMISSION_ORG_WRITE';
    const PERMISSION_ORG_ADMIN = 'PERMISSION_ORG_ADMIN';
    
    
    /**
     * A list of which permissions each scope grants.
     * @var array 
     */
    static  public $SCOPE_PERMISSIONS = [

        //	Grants read/write access to profile info only. Note that this scope includes user:email and user:follow.
        self::SCOPE_USER => [
            self::PERMISSION_PROFILE_READ, self::PERMISSION_PROFILE_WRITE,
            self::PERMISSION_EMAIL_ADMIN,
            self::PERMISSION_USER_FOLLOW_ADMIN
        ],
        
        //	Grants read access to a user’s email addresses.
        self::SCOPE_USER_EMAIL => [self::PERMISSION_EMAIL_READ],

        //	Grants access to follow or unfollow other users.
        self::SCOPE_USER_FOLLOW => [
            self::PERMISSION_USER_FOLLOW_ADMIN
        ],

        // Grants read/write access to code, commit statuses, and deployment statuses for public repositories and organizations.
        self::SCOPE_PUBLIC_REPO => [
            self::PERMISSION_CODE_ADMIN,
            self::PERMISSION_DEPLOYMENT_STATUS_ADMIN,
            self::PERMISSION_REPO_ADMIN,
            self::PERMISSION_ORG_ADMIN,
        ],


        // Read-only access to organization, teams, and membership.
        self::SCOPE_ORG_READ => [
            self::PERMISSION_ORG_READ
        ],

        // Publicize and unpublicize organization membership.
        self::SCOPE_ORG_WRITE => [
            self::PERMISSION_ORG_WRITE
        ],
    
        // Fully manage organization, teams, and memberships.
        self::SCOPE_ORG_ADMIN => [
            self::PERMISSION_ORG_ADMIN
        ],
        
        
        
//    const SCOPE_REPO = 'repo'; //	Grants read/write access to code, commit statuses, and deployment statuses for public and private repositories and organizations.
//    
//    const SCOPE_REPO_DEPLOYMENT = 'repo_deployment'; //	Grants access to deployment statuses for public and private repositories. This scope is only necessary to grant other users or services access to deployment statuses, without granting access to the code.
//    
//    const SCOPE_REPO_STATUS = 'repo:status'; //	Grants read/write access to public and private repository commit statuses. This scope is only necessary to grant other users or services access to private repository commit statuses without granting access to the code.
//    
//    const SCOPE_DELETE_REPO = 'delete_repo'; //	Grants access to delete adminable repositories.
//
//    const SCOPE_NOTIFICATIONS = 'notifications'; //	Grants read access to a user’s notifications. repo also provides this access.
//
//    const SCOPE_GIST = 'gist'; //	Grants write access to gists.
//
//    const SCOPE_REPO_HOOK_READ = 'read:repo_hook'; //	Grants read and ping access to hooks in public or private repositories.
//    const SCOPE_REPO_HOOK_WRITE = 'write:repo_hook'; //	Grants read, write, and ping access to hooks in public or private repositories.
//    const SCOPE_REPO_HOOK_ADMIN = 'admin:repo_hook'; //	Grants read, write, ping, and delete access to hooks in public or private repositories.
//

//
//    const SCOPE_PUBLIC_KEY_READ = 'read:public_key'; //	List and view details for public keys.
//    const SCOPE_PUBLIC_KEY_WRITE  = 'write:public_key'; //	Create, list, and view details for public keys.
//    const SCOPE_PUBLIC_KEY_ADMIN = 'admin:public_key'; //	Fully manage public keys.
//    
//        
        
        
        
        
        
        
        
    ]; 

    
    
    
    
    
}

