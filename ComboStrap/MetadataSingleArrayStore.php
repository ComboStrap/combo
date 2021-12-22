<?php


namespace ComboStrap;


/**
 * Class MetadataArrayStore
 * @package ComboStrap
 * A store based on a single array for a single resource
 */
abstract class MetadataSingleArrayStore extends MetadataStoreAbs
{


    protected $data;

    /**
     * MetadataSingleArrayStore constructor.
     * @param ResourceCombo $page
     * @param $data
     */
    public function __construct(ResourceCombo $page, $data = null)
    {
        foreach ($data as $key => $value) {
            $key = $this->toNormalizedKey($key);
            $this->data[$key] = $value;
        }
        parent::__construct($page);
    }


    public function set(Metadata $metadata)
    {
        $this->checkResource($metadata->getResource());
        $this->data[$metadata::getPersistentName()] = $metadata->toStoreValue();
    }

    public function get(Metadata $metadata, $default = null)
    {
        $this->checkResource($metadata->getResource());
        $value = $this->data[$metadata::getPersistentName()];
        if ($value !== null) {
            return $value;
        }
        foreach ($metadata::getOldPersistentNames() as $name) {
            $value = $this->data[$name];
            if ($value !== null) {
                $this->data[$metadata::getPersistentName()] = $value;
                unset($this->data[$name]);
                return $value;
            }
        }
        return $default;
    }

    public function persist()
    {
        if (PluginUtility::isDevOrTest()) {
            throw new ExceptionComboRuntime("Not yet implemented, use sendToStore");
        }
    }

    public function isHierarchicalTextBased(): bool
    {
        return true;
    }


    public function getData(): ?array
    {
        return $this->data;
    }


    public function reset()
    {
        $this->data = [];
    }

    public function getFromPersistentName(string $name, $default = null)
    {

        $value = $this->data[$name];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function setFromPersistentName(string $name, $value)
    {
        $name = $this->toNormalizedKey($name);
        if ($value === null || $value === "") {
            unset($this->data[$name]);
            return;
        }
        $this->data[$name] = $value;

    }


    public function hasProperty(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function remove(Metadata $metadata): MetadataSingleArrayStore
    {
        $this->checkResource($metadata->getResource());
        unset($this->data[$metadata->getName()]);
        return $this;
    }

    private function toNormalizedKey(string $key): string
    {
        return trim($key);
    }

}
