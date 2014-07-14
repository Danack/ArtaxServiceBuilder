<?php


namespace ArtaxApiBuilder\Service;


class Link {

    public $url;
    public $description;

    public function __construct($url, $description) {
        $this->url = $url;
        $this->description = $description;
    }
}

 