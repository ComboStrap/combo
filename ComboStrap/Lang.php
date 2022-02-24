<?php


namespace ComboStrap;


use dokuwiki\Cache\Cache;

class Lang extends MetadataText
{

    public const PROPERTY_NAME = "lang";


    /**
     * Process the lang attribute
     * https://www.w3.org/International/questions/qa-html-language-declarations
     * @param TagAttributes $attributes
     *
     * Language supported:
     * http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
     *
     * Common Locale Data Repository
     * http://cldr.unicode.org/
     * Data:
     *   * http://www.unicode.org/Public/cldr/
     *   * https://github.com/unicode-cldr/
     *   * https://github.com/unicode-org/cldr-json
     * The ''dir'' data is known as the ''characterOrder''
     *
     */
    public static function processLangAttribute(&$attributes)
    {


        /**
         * Adding the lang attribute
         * if set
         */
        if ($attributes->hasComponentAttribute(self::PROPERTY_NAME)) {
            $langValue = $attributes->getValueAndRemove(self::PROPERTY_NAME);
            $attributes->addOutputAttributeValue("lang", $langValue);

            $languageDataCache = new Cache("combo_" . $langValue, ".json");
            $cacheDataUsable = $languageDataCache->useCache();
            if (!$cacheDataUsable) {

                // Language about the data
                $downloadUrl = "https://raw.githubusercontent.com/unicode-org/cldr-json/master/cldr-json/cldr-misc-modern/main/$langValue/layout.json";

                $filePointer = @fopen($downloadUrl, 'r');
                if ($filePointer != false) {

                    $numberOfByte = @file_put_contents($languageDataCache->cache, $filePointer);
                    if ($numberOfByte != false) {
                        LogUtility::msg("The new language data ($langValue) was downloaded", LogUtility::LVL_MSG_INFO, self::PROPERTY_NAME);
                        $cacheDataUsable = true;
                    } else {
                        LogUtility::msg("Internal error: The language data ($langValue) could no be written to ($languageDataCache->cache)", LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);
                    }

                } else {

                    LogUtility::msg("The data for the language ($langValue) could not be found at ($downloadUrl).", LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);

                }
            }

            if ($cacheDataUsable) {
                $jsonAsArray = true;
                $languageData = json_decode(file_get_contents($languageDataCache->cache), $jsonAsArray);
                if ($languageData == null) {
                    LogUtility::msg("We could not read the data from the language ($langValue). No direction was set.", LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);
                    return;
                }
                $characterOrder = $languageData["main"][$langValue]["layout"]["orientation"]["characterOrder"];
                if ($characterOrder == "right-to-left") {
                    $attributes->addOutputAttributeValue("dir", "rtl");
                } else {
                    $attributes->addOutputAttributeValue("dir", "ltr");
                }
            } else {
                LogUtility::msg("The language direction cannot be set because no language data was found for the language ($langValue)", LogUtility::LVL_MSG_WARNING, self::PROPERTY_NAME);
            }

        }

    }

    public static function createForPage(Page $page)
    {
        return (new Lang())
            ->setResource($page);
    }

    public function getTab(): ?string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): Metadata
    {

        $this->validityCheck($value);
        return parent::setFromStoreValue($value);

    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {
        $this->validityCheck($value);
        return parent::setValue($value);
    }


    public function getDescription(): string
    {
        return "The language of the page";
    }

    public function getLabel(): string
    {
        return "Language";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return Site::getLang();
    }

    /**
     * @throws ExceptionCombo
     */
    private function validityCheck($value)
    {
        if ($value === "" || $value === null) {
            return;
        }
        if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
            throw new ExceptionCombo("The lang value ($value) for the page ($this) does not have two letters", $this->getCanonical());
        }
    }

    public function getCanonical(): string
    {
        return "lang";
    }


}
