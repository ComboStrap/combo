<?php


namespace ComboStrap;


abstract class MetadataStoreAbs implements MetadataStore
{

    private $page;


    /**
     * MetadataFormStore constructor.
     * @param ResourceCombo $page
     * @param array $data
     */
    public function __construct(ResourceCombo $page)
    {
        $this->page = $page;
    }

    protected function checkResource(ResourceCombo $requestedResource)
    {
        if ($this->page !== $requestedResource) {
            throw new ExceptionComboRuntime("The page ($requestedResource) is unknown. We got data for the page ($this->page)", self::CANONICAL);
        }
    }

    /**
     * @param MetadataStore|string $readStore
     * @param $resource
     * @return MetadataStore
     */
    public static function toMetadataStore($readStore, $resource): MetadataStore
    {
        if ($readStore instanceof MetadataStore) {
            return $readStore;
        }
        if (!is_string($readStore)) {
            throw new ExceptionComboRuntime("The class value is not a string", MetadataStore::CANONICAL);
        }
        if (!is_subclass_of($readStore, MetadataStore::class)) {
            throw new ExceptionComboRuntime("The value ($readStore) is not a subclass of a store.");
        }
        if ($resource === null) {
            throw new ExceptionComboRuntime("The resource is null. You can't implement a store without a resource.");
        }
        return $readStore::createFromResource($resource);

    }

    public function getResource(): ResourceCombo
    {
        return $this->page;
    }

    public function getCanonical(): string
    {
        return "store";
    }


}
