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

/**
 * Class File
 * @package ComboStrap
 * @deprecated for {@link LocalPath} and {@link FileSystems}
 */
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
     * @deprecated uses {@link Path::toAbsolutePath()} instead
     * @return mixed
     */
    public function getAbsoluteFileSystemPath()
    {
        return $this->path;
    }

    /**
     * @deprecated use {@link FileSystems::getSize()} instead
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
     * @deprecated use {@link FileSystems::getContent()} instead
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
     * @deprecated for {@link ResourceCombo::getBuster()}
     * @return string
     */
    public function getBuster(): string
    {
        return strval($this->getModifiedTime()->getTimestamp());
    }

    /**
     * @deprecated uses {@link FileSystems::getCreationTime()} instead
     * @return DateTime|false|mixed
     */
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
