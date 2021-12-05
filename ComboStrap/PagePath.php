<?php


namespace ComboStrap;


use DateTime;

/**

 * @package ComboStrap
 * Represents the wiki path of the page resource
 */
class PagePath extends MetadataWikiPath
{


    public const DATE_CREATED = 'date_created';


    public static function createForPage(Page $page): CacheExpirationDate
    {
        return (new CacheExpirationDate())
            ->setResource($page);
    }

    public function getDefaultValue(): ?DateTime
    {
        return null;
    }

    public function buildFromStore()
    {

        $store = $this->getStore();

        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }

        $createdMeta = $store->getFromResourceAndName($this->getResource(), 'date')['created'];
        if (empty($createdMeta)) {
            return $this;
        }
        // the data in dokuwiki is saved as timestamp
        $datetime = new DateTime();
        $datetime->setTimestamp($createdMeta);
        $this->setValue($datetime);
        return $this;
    }


    public function getValue(): ?string
    {
        return $this->getResource()->getPath()->toString();
    }


    public function getName(): string
    {
        return Path::PATH_ATTRIBUTE;
    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The path of the page on the file system (in wiki format with the colon `:` as path separator)";
    }

    public function getLabel(): string
    {
        return "Page Path";
    }

    public function getMutable(): bool
    {
        return false;
    }
}
