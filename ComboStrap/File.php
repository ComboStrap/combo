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


use DateTime;

class File
{

    protected $path;


    /**
     * File constructor.
     * @param $absolutePath
     */
    protected function __construct($absolutePath)
    {
        $this->path = $absolutePath;
    }

    /**
     * @return mixed
     */
    public function getAbsoluteFileSystemPath()
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

    /**
     * @return bool
     * @deprecated uses {@link FileSystems::exists()} instead
     */
    public function exists()
    {
        return file_exists($this->path);
    }

    public function __toString()
    {
        return $this->path;
    }

    /**
     * @return null|DateTime - The date time
     * @deprecated use {@link FileSystems::getModifiedTime()} instead
     */
    public function getModifiedTime(): ?DateTime
    {
        if(!$this->exists()){
            return null;
        }
        return Iso8601Date::createFromTimestamp(filemtime($this->path))->getDateTime();
    }

    /**
     * @return string the last part of the path without the extension
     * @deprecated use {@link LocalPath::getLastName()} instead
     */
    public function getBaseNameWithoutExtension()
    {

        return pathinfo($this->path, PATHINFO_FILENAME);
    }





    public function isImage(): bool
    {
        return substr($this->getMime(), 0, 5) == 'image';
    }



    /**
     * @return false|string
     */
    public function getTextContent()
    {
        return file_get_contents($this->getAbsoluteFileSystemPath());
    }

    /**
     * @deprecated use {@link FileSystems::delete()} instead
     */
    public function remove()
    {
        unlink($this->getAbsoluteFileSystemPath());
    }

    /**
     * @return File|null
     * @deprecated use {@link LocalPath::getParent()} instead
     */
    public function getParent(): ?File
    {
        $absolutePath = pathinfo($this->path, PATHINFO_DIRNAME);
        if(empty($absolutePath)){
            return null;
        }
        return new File($absolutePath);
    }

    public function createAsDirectory(): bool
    {

        return mkdir($this->getAbsoluteFileSystemPath(), $mode = 0770, $recursive = true);
    }

    public static function createFromPath($path): File
    {
        return new File($path);

    }


    /**
     * A buster value used in URL
     * to avoid cache (cache bursting)
     *
     * It is unique for each version of the path
     *
     * @return string
     */
    public function getBuster(): string
    {
        return strval($this->getModifiedTime()->getTimestamp());
    }

    public function getCreationTime()
    {
        return Iso8601Date::createFromTimestamp(filectime($this->path))->getDateTime();
    }

    /**
     * @deprecated use {@link FileSystems::deleteIfExists()} instead
     */
    public function removeIfExists(): File
    {
        if($this->exists()){
            $this->remove();
        }
        return $this;
    }


}
