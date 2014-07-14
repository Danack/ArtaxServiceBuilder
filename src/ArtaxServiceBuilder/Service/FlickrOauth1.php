<?php


namespace ArtaxApiBuilder\Service;

use Artax\Request;

class FlickrOauth1 extends \ArtaxApiBuilder\Service\Oauth1 {
    /**
     * Decide whether the post fields should be added to the Oauth BaseString.
     * Flickr incorrectly add the post fields when the content type is 'multipart/form-data'. They should only be added when the content type is 'application/x-www-form-urlencoded'
     *
     * @param $request
     * @return bool Whether the post fields should be signed or not
     */

    public function shouldPostFieldsBeSigned(Request $request) {
        $returnValue = false;

        if ($request->hasHeader('Content-Type')) {
            $contentType = $request->getHeader('Content-Type');
            //TODO - not safe
            if ($contentType === 'application/x-www-form-urlencoded' ||
                $contentType === 'multipart/form-data') {
                $returnValue = true;
            }
        }

        // Don't sign POST fields if the request uses POST fields and no files
        if ($request->getFileCount() == 0) {
            $returnValue = false;
        }

        return $returnValue;
    }
}


