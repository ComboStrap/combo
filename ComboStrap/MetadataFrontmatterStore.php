<?php


namespace ComboStrap;


class MetadataFrontmatterStore implements MetadataStore
{

    const NAME = "frontmatter";


    /**
     * @var MetadataFrontmatterStore
     */
    private static $store;
    private $data;

    public function load(ResourceCombo $page, array $jsonArray): MetadataFrontmatterStore
    {
        $path = $this->getArrayKey($page);
        $this->data[$path] = $jsonArray;
        return $this;
    }

    public static function getOrCreate(): MetadataFrontmatterStore
    {
        if (self::$store === null) {
            self::$store = new MetadataFrontmatterStore();
        }
        return self::$store;
    }

    public function set(Metadata $metadata)
    {
        $key = $this->getArrayKey($metadata->getResource());
        $this->data[$key][$metadata->getName()] = $metadata->toStoreValue();
    }

    public function get(Metadata $metadata, $default = null)
    {
        $key = $this->getArrayKey($metadata->getResource());
        $value = $this->data[$key][$metadata->getName()];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function persist()
    {
        throw new ExceptionComboRuntime("Not yet implemented", self::NAME);
    }

    public function isTextBased(): bool
    {
        return true;
    }

    public function __toString()
    {
        return self::NAME;
    }

    /**
     * @param ResourceCombo $page
     * @return string the key of the storage array
     */
    private function getArrayKey(ResourceCombo $page): string
    {
        return $page->getPath()->toString();
    }


}
