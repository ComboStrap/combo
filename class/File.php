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


class File
{

    private $path;


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

    protected function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }


}
