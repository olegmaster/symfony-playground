<?php

namespace App\Service;

class FileService
{
    /**
     * @param string $path
     * @return string
     */
    public function getKeyByS3ObjectUrl(string $s3ObjectURL): string
    {
        $urlChunks = explode('/', $s3ObjectURL);
        $key = end($urlChunks);
        return $key;
    }
}
