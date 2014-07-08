<?php


namespace AABTest;


interface FlickrAPI {

    /**
     * @param $user_id
     * @return \AABTest\PhotoList
     */
    public function flickr_people_getPublicPhotos($user_id);
} 