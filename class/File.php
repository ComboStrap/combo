<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use dokuwiki\Cache\Cache;

class File
{

    private $path;
    /**
     * @var Cache
     */
    private $fileCache;


    /**
     * File constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return false|int
     */
    public function getSize()
    {
        return filesize($this->path);
    }

    public function exists()
    {
        return file_exists($this->path);
    }

    public function __toString()
    {
        return $this->path;
    }

    /**
     * @return false|int - unix time stamp
     */
    public function getModifiedTime()
    {
        return filemtime($this->path);
    }

    public function getFileNameWithoutExtension()
    {
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);
        return basename($this->path, $ext);
    }

    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }


    public function getFileName()
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function isImage()
    {
        return substr($this->getMime(), 0, 5) == 'image';
    }

    public function getMime()
    {
        return mimetype($this->getFileName(), false)[1];
    }

    public function getKnownMime()
    {
        return mimetype($this->getFileName(), true)[1];
    }

    public function getContent()
    {
        return file_get_contents($this->getPath());
    }


}
