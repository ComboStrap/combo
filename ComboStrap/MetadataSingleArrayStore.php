<?php


namespace ComboStrap;


/**
 * Class MetadataArrayStore
 * @package ComboStrap
 * A store based on a single array for a single resource
 */
abstract class MetadataSingleArrayStore extends MetadataStoreAbs
{


    /**
     * @var bool
     */
    protected $hasChanged = false;

    protected $data;

    /**
     * MetadataSingleArrayStore constructor.
     * @param ResourceCombo $page
     * @param $data
     */
    public function __construct(ResourceCombo $page, $data = null)
    {
        if($data!==null) {
            foreach ($data as $key => $value) {
                $key = $this->toNormalizedKey($key);
                $this->data[$key] = $value;
            }
        }
        parent::__construct($page);
    }


    public function set(Metadata $metadata)
    {
        $this->checkResource($metadata->getResource());
        $this->setFromPersistentName($metadata::getPersistentName(), $metadata->toStoreValue());
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
        $this->data = null;
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
        $actualValue = $this->data[$name];
        if ($actualValue !== $value) {
            $this->hasChanged = true;
        }

        $name = $this->toNormalizedKey($name);
        if ($value === null || $value === "") {
            // remove
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
        $this->setFromPersistentName($metadata->getName(), null);
        return $this;
    }

    private function toNormalizedKey(string $key): string
    {
        return trim($key);
    }

    /**
     * Used to update the data from an other external process
     * (ie
     *    {@link MetadataDokuWikiStore::renderAndPersist() metadata renderer}
     *    or {@link \action_plugin_combo_metamanager::handleViewerPost() metadata manager
     * )
     * @param $data
     */
    public function setData($data)
    {
        if ($data !== $this->data) {
            $this->hasChanged = true;
        }
        $this->data = $data;
    }

    /**
     * @return bool - true if the data has changed
     */
    public function hasStateChanged(): bool
    {
        return $this->hasChanged;
    }

}
