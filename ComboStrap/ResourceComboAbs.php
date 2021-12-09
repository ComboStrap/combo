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

    /**
     * An utility function that {@link MetadataStore::persist() persists} the value
     * into the default {@link ResourceCombo::getStoreOrDefault()}
     * @return $this
     */
    public function persist(): ResourceComboAbs
    {
        $this->getStoreOrDefault()->persist();
        return $this;
    }
}
