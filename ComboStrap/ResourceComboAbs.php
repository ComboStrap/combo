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
     * @throws ExceptionNotFound
     */
    public function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getPath());

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

    /**
     *
     */
    public function getUidObject(): Metadata
    {
        if ($this->uidObject === null) {
            $this->uidObject = Metadata::toMetadataObject($this->getUid())
                ->setResource($this);
        }

        return $this->uidObject;

    }


}
