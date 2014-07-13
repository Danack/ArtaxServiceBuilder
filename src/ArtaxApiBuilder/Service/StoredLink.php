<?php


namespace ArtaxApiBuilder\Service;


class StoredLink {

    private $key;
    
    function __construct(Link $link) {
        $this->key = uniqid("StoredLink");
        $this->link = $link;
        $this->storeLink();
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param $key
     * @return StoredLink|null
     */
    static function createFromKey($key) {
        $success = false;
        $link = apc_fetch($key, $success);
        if (!$link) {
            //TODO - or throw exception.
            return null;
        }

        return unserialize($link);
    }

    /**
     * 
     */
    public function storeLink() {
        apc_store($this->key, serialize($this));
    }
    
    
}

 