<?php


namespace ComboStrap;


use syntax_plugin_combo_frontmatter;

class PageDescription extends MetadataText
{
    /**
     * The description sub key in the dokuwiki meta
     * that has the description text
     */
    const ABSTRACT_KEY = "abstract";
    public const DESCRIPTION = "description";


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

    public static function createForPage($page): PageName
    {
        return (new PageName())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The description is a paragraph that describe your page. It's advertised to external application and used in templating.";
    }

    public function getLabel(): string
    {
        return "Description";
    }

    public function getName(): string
    {
        return self::DESCRIPTION_PROPERTY;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getDataType(): string
    {
        return DataType::PARAGRAPH_TYPE_VALUE;
    }


    public function getMutable(): bool
    {
        return true;
    }

    /**
     * @return string|null - the dokuwiki calculated description
     *
     */
    public function getDefaultValue(): ?string
    {

        /**
         * The default is the value generated from dokuwiki
         * (
         *  ie until the description is not set
         *  ie until the origin is not set
         * )
         */
        $this->buildCheck();
        if ($this->descriptionOrigin !== null &&
            $this->descriptionOrigin !== self::DESCRIPTION_DOKUWIKI_ORIGIN
        ) {
            return null;
        }

        /**
         * Dokuwiki description
         * With some trick
         */
        // suppress the carriage return
        $description = str_replace("\n", " ", $this->getValue());
        // suppress the h1
        $resourceCombo = $this->getResource();
        if ($resourceCombo instanceof Page) {
            $description = str_replace($resourceCombo->getH1OrDefault(), "", $description);
        }
        // Suppress the star, the tab, About
        $description = preg_replace('/(\*|\t|About)/im', "", $description);
        // Suppress all double space and trim
        return trim(preg_replace('/  /m', " ", $description));
    }

    public function getValue(): ?string
    {

        try {
            $this->buildFromStore();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Unable to build the description. " . $e->getMessage());
            return null;
        }

        $metaDataStore = $this->getStore();
        if (!($metaDataStore instanceof MetadataDokuWikiStore)) {
            return parent::getValue();
        }

        $descriptionArray = $metaDataStore->get($this);
        if (empty($descriptionArray)) {
            return null;
        }
        if (!array_key_exists(self::ABSTRACT_KEY, $descriptionArray)) {
            return null;
        }

        $description = $descriptionArray[self::ABSTRACT_KEY];
        $this->descriptionOrigin = self::DESCRIPTION_DOKUWIKI_ORIGIN;
        if (array_key_exists('origin', $descriptionArray)) {
            $this->descriptionOrigin = $descriptionArray['origin'];
            if ($this->descriptionOrigin !== self::DESCRIPTION_DOKUWIKI_ORIGIN) {
                return $description;
            }
        }


        /**
         * Description Plugin integration
         * https://github.com/lupo49/plugin-description/blob/master/syntax.php#L42
         */
        $pluginDescription = 'plugin_description';
        $description = $metaDataStore->getFromResourceAndName($this->getResource(), $pluginDescription);
        if ($description !== null && isset($description["keywords"])) {
            $description = $description["keywords"];
            $this->descriptionOrigin = $pluginDescription;
            return $description;
        }

        return null;

    }

    /**
     * @throws ExceptionCombo
     */
    public function buildFromStore(): MetadataText
    {
        $metaDataStore = $this->getStore();
        if (!($metaDataStore instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }

        if (!$this->wasBuild) {
            $this->wasBuild = true;
            $descriptionArray = $metaDataStore->get($this);
            if (empty($descriptionArray)) {
                return $this;
            }
            if (!array_key_exists(self::ABSTRACT_KEY, $descriptionArray)) {
                return $this;
            }

            /**
             * If there is an origin, it means that it was set
             * and therefore not derived from the content
             */
            if (array_key_exists('origin', $descriptionArray)) {
                $this->descriptionOrigin = $descriptionArray['origin'];
            }

            parent::setValue($descriptionArray[self::ABSTRACT_KEY]);
        }
        return $this;
    }


    public function setValue(?string $value): MetadataText
    {

        // we need to know the origin of the actual description
        $this->buildCheck();
        if ($value === "" || $value === null) {
            if ($this->descriptionOrigin === PageDescription::DESCRIPTION_COMBO_ORIGIN) {
                throw new ExceptionCombo("The description cannot be empty", PageDescription::DESCRIPTION_PROPERTY);
            } else {
                // The original description is from Dokuwiki, we don't send an error
                // otherwise all page without a first description would get an error
                // (What fucked up is fucked up)
                return $this;
            }
        }

        parent::setValue($value);
        return $this;
    }

    /**
     * @return string|array
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function toStoreValue()
    {
        $metaDataStore = $this->getStore();
        if (!($metaDataStore instanceof MetadataDokuWikiStore)) {
            parent::toStoreValue();
        }
        /**
         * For dokuwiki, this is an array
         */
        return array(
            self::ABSTRACT_KEY => $this->getValue(),
            "origin" => PageDescription::DESCRIPTION_COMBO_ORIGIN
        );
    }

    public function getCanonical(): string
    {
        return $this->getName();
    }


}