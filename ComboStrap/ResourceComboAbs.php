<?php


namespace ComboStrap;


abstract class ResourceComboAbs implements ResourceCombo
{


    /**
     * @var Metadata
     */
    private $uidObject;

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
     * into the default {@link ResourceCombo::getReadStoreOrDefault()}
     * @return $this
     */
    public function persist(): ResourceComboAbs
    {
        $this->getReadStoreOrDefault()->persist();
        return $this;
    }

    public function getUidObject()
    {
        if ($this->uidObject === null) {
            $this->uidObject = Metadata::toMetadataObject($this->getUid())
                ->setResource($this);
        }

        return $this->uidObject;

    }


}
