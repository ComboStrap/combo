<?php


namespace ComboStrap;


abstract class ResourceComboAbs implements ResourceCombo
{


    public function exists(): bool
    {
        return FileSystems::exists($this->getPath());
    }

    /**
     * A buster cache
     * @return string
     */
    public function getBuster(): string
    {
        $time = FileSystems::getModifiedTime($this->getPath());
        return strval($time->getTimestamp());
    }


}
