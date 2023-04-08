<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataStore;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use syntax_plugin_combo_frontmatter;

class PageDescription extends MetadataText
{

    /**
     * The description sub key in the dokuwiki meta
     * that has the description text
     */
    const ABSTRACT_KEY = "abstract";
    public const PROPERTY_NAME = "description";
    const DESCRIPTION_ORIGIN = "origin";
    const PLUGIN_DESCRIPTION_META = "plugin_description";


    /**
     * @var string - the origin of the description
     */
    private $descriptionOrigin;


    public const DESCRIPTION_PROPERTY = "description";
    /**
     * To indicate from where the description comes
     * This is when it's the original dokuwiki description
     */
    public const DESCRIPTION_DOKUWIKI_ORIGIN = "dokuwiki";
    /**
     * The origin of the description was set to frontmatter
     * due to historic reason to say to it comes from combo
     * (You may set it via the metadata manager and get this origin)
     */
    public const DESCRIPTION_COMBO_ORIGIN = syntax_plugin_combo_frontmatter::CANONICAL;
    private ?string $defaultValue = null;

    public static function createForPage($page): PageDescription
    {
        return (new PageDescription())
            ->setResource($page);
    }

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public static function getDescription(): string
    {
        return "The description is a paragraph that describe your page. It's advertised to external application and used in templating.";
    }

    public static function getLabel(): string
    {
        return "Description";
    }

    static public function getName(): string
    {
        return self::DESCRIPTION_PROPERTY;
    }

    public static function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    public static function getDataType(): string
    {
        return DataType::PARAGRAPH_TYPE_VALUE;
    }


    public static function isMutable(): bool
    {
        return true;
    }


    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {

        try {
            $value = $this->getValue();
            if (empty($value)) {
                return $this->getDefaultValue();
            }
            return $value;
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }


    }


    /**
     * @return string - the dokuwiki calculated description
     * or the resource name if none (case when there is no text at all for instance with only a icon)
     */
    public function getDefaultValue(): string
    {

        $this->buildCheck();
        if ($this->defaultValue === "" || $this->defaultValue === null) {
            return PageTitle::createForMarkup($this->getResource())
                ->getValueOrDefault();
        }
        return $this->defaultValue;

    }


    public function setFromStoreValueWithoutException($value): Metadata
    {

        $metaDataStore = $this->getReadStore();
        if (!$metaDataStore->isDokuWikiStore()) {
            parent::setFromStoreValueWithoutException($value);
            return $this;
        }

        if (is_array($value)) {
            $description = $value[self::ABSTRACT_KEY];
            if ($description !== null) {
                $descriptionOrigin = $value[self::DESCRIPTION_ORIGIN];
                if ($descriptionOrigin !== null) {
                    $this->descriptionOrigin = $descriptionOrigin;
                    parent::setFromStoreValueWithoutException($description);
                    return $this;
                }
            }
        }
        /**
         * Plugin Plugin Description Integration
         */
        $value = $metaDataStore->getFromName(self::PLUGIN_DESCRIPTION_META);
        if ($value !== null) {
            $keywords = $value["keywords"];
            if ($keywords !== null) {
                parent::setFromStoreValueWithoutException($keywords);
                $this->descriptionOrigin = self::PLUGIN_DESCRIPTION_META;
                return $this;
            }
        }

        /**
         * No description set, null
         */
        parent::setFromStoreValueWithoutException(null);

        /**
         * Default value is derived from the meta store
         * We need to set it at build time because the store may change
         * after the build
         */
        $this->defaultValue = $this->getGeneratedValueFromDokuWikiStore($metaDataStore);

        return $this;
    }


    public function setValue($value): Metadata
    {

        if ($value === "" || $value === null) {

            if ($this->getReadStore() instanceof MetadataDokuWikiStore) {
                // we need to know the origin of the actual description
                if ($this->descriptionOrigin === null) {
                    /**
                     * we don't do {@link Metadata::buildCheck() build check} otherwise we get a loop
                     * because it will use back this method {@link Metadata::setValue()}
                     */
                    $this->buildFromReadStore();
                }
                if ($this->descriptionOrigin === PageDescription::DESCRIPTION_COMBO_ORIGIN) {
                    throw new ExceptionCompile("The description cannot be empty", PageDescription::DESCRIPTION_PROPERTY);
                } else {
                    // The original description is from Dokuwiki, we don't send an error
                    // otherwise all page without a first description would get an error
                    // (What fucked up is fucked up)
                    return $this;
                }
            }

        }

        parent::setValue($value);
        return $this;
    }

    /**
     * @return ?string|array
     */
    public function toStoreValue()
    {
        $metaDataStore = $this->getWriteStore();
        if (!($metaDataStore instanceof MetadataDokuWikiStore)) {
            // A string
            return parent::toStoreValue();
        }
        /**
         * For dokuwiki, this is an array
         */
        try {
            return array(
                self::ABSTRACT_KEY => $this->getValue(),
                self::DESCRIPTION_ORIGIN => PageDescription::DESCRIPTION_COMBO_ORIGIN
            );
        } catch (ExceptionNotFound $e) {
            return null;
        }
    }



    public function getDescriptionOrigin(): string
    {
        return $this->descriptionOrigin;
    }

    private function getGeneratedValueFromDokuWikiStore(MetadataStore $metaDataStore): ?string
    {

        /**
         * The generated is in the current metadata
         */
        $descriptionArray = $metaDataStore->getFromName(self::PROPERTY_NAME);
        if (empty($descriptionArray)) {
            return null;
        }
        if (!array_key_exists(self::ABSTRACT_KEY, $descriptionArray)) {
            return null;
        }
        $value = $descriptionArray[self::ABSTRACT_KEY];


        /**
         * Dokuwiki description
         * With some trick
         * TODO: Generate our own description ?
         */
        // suppress the carriage return
        $description = str_replace("\n", " ", $value);
        // suppress the h1
        $resourceCombo = $this->getResource();
        if ($resourceCombo instanceof MarkupPath) {
            $description = str_replace($resourceCombo->getH1OrDefault(), "", $description);
        }
        // Suppress the star, the tab, About
        $description = preg_replace('/(\*|\t|About)/im', "", $description);
        // Suppress all double space and trim
        return trim(preg_replace('/ /m', " ", $description));
    }


    public static function isOnForm(): bool
    {
        return true;
    }
}
