<?php

namespace ArtaxServiceBuilder\ResponseCache;


class FileCachePath
{
    private $path;

    public function __construct($path)
    {
        if ($path === null) {
            throw new \LogicException(
                "Path cannot be null for FileCachePath."
            );
        }
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
