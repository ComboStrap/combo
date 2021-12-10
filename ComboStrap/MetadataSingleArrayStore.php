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
    private $data;

    /**
     * MetadataFormStore constructor.
     * @param ResourceCombo $page
     * @param array $data
     */
    public function __construct(ResourceCombo $page, array $data)
    {
        $this->page = $page;
        $this->data = $data;
    }



    public function set(Metadata $metadata)
    {
        $this->checkResource($metadata->getResource());
        $this->data[$metadata->getName()] = $metadata->toStoreValue();
    }

    public function get(Metadata $metadata, $default = null)
    {
        $this->checkResource($metadata->getResource());
        $value = $this->data[$metadata->getName()];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function persist()
    {
        throw new ExceptionComboRuntime("Not yet implemented, use sendToStore");
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

    public function getFromResourceAndName(ResourceCombo $resource, string $name, $default = null)
    {
        $this->checkResource($resource);
        $value = $this->data[$name];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function setFromResourceAndName(ResourceCombo $resource, string $name, $value)
    {
        $this->checkResource($resource);
        if ($value === null || $value === "") {
            unset($this->data[$name]);
            return;
        }
        $this->data[$name] = $value;

    }

    private function checkResource(ResourceCombo $requestedResource)
    {
        if ($this->page !== $requestedResource) {
            throw new ExceptionComboRuntime("The page ($requestedResource) is unknown. We got data for the page ($this->page)",self::CANONICAL);
        }
    }

}
