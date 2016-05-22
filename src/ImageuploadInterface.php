<?php

namespace Matriphe\Imageupload;

interface ImageuploadInterface
{
    /**
     * @param mixed $filesource
     * @param mixed $newfilename (default: null)
     * @param mixed $dir         (default: null)
     */
    public function upload($filesource, $newfilename = null, $dir = null);
}
