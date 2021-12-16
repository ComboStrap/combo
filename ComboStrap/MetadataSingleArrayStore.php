<?php


namespace ComboStrap;


/**
 * Class MetadataArrayStore
 * @package ComboStrap
 * A front matter based on a single array for a single page
 */
abstract class MetadataSingleArrayStore implements MetadataStore
{


    private $page;
    protected $data;

    /**
     * MetadataFormStore constructor.
     * @param ResourceCombo $page
     * @param array $data
     */
    public function __construct(ResourceCombo $page, array $data = [])
    {
        $this->page = $page;
        $this->data = $data;
    }

    public function getResource(): ResourceCombo
    {
        return $this->page;
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


    public function getData(): array
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

        if ($value === null || $value === "") {
            unset($this->data[$name]);
            return;
        }
        $this->data[$name] = $value;

    }

    protected function checkResource(ResourceCombo $requestedResource)
    {
        if ($this->page !== $requestedResource) {
            throw new ExceptionComboRuntime("The page ($requestedResource) is unknown. We got data for the page ($this->page)", self::CANONICAL);
        }
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

}
