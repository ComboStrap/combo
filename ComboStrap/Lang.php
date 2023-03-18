<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
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

                if (PluginUtility::isDevOrTest()) {
                    // phpunit takes over and would catch and cache the error
                    $filePointer = fopen($downloadUrl, 'r');
                } else {
                    $filePointer = @fopen($downloadUrl, 'r');
                }
                if ($filePointer != false) {

                    $numberOfByte = @file_put_contents($languageDataCache->cache, $filePointer);
                    if ($numberOfByte != false) {
                        LogUtility::msg("The new language data ($langValue) was downloaded", LogUtility::LVL_MSG_INFO, self::PROPERTY_NAME);
                        $cacheDataUsable = true;
                    } else {
                        LogUtility::msg("Internal error: The language data ($langValue) could no be written to ($languageDataCache->cache)", LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);
                    }

                } else {

                    // fopen(): Unable to find the wrapper "https" - did you forget to enable it when you configured PHP?
                    $error_get_last = error_get_last();
                    $message = $error_get_last['message'];
                    LogUtility::msg("The data for the language ($langValue) could not be downloaded at (<a href=\"$downloadUrl\">$langValue</a>). Error: " . $message, LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);

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

    public static function createForMarkup(MarkupPath $markup): Lang
    {
        $lang = new Lang();
        $lang->setResource($markup);
        return $lang;
    }


    /**
     * @throws ExceptionNotFound
     */
    public static function createFromRequestedMarkup(): Lang
    {
        return self::createForMarkup(MarkupPath::createFromRequestedPage());
    }

    /**
     * Set the direction of the text
     * The global lang direction (not yet inside the Lang class)
     * @param string $string
     * @return void
     */
    public static function setDirection(string $string)
    {
        global $lang;
        $lang["direction"] = $string;
    }

    public static function createFromValue(string $langValue): Lang
    {
        $lang = new Lang();
        $lang->value = $langValue;
        return $lang;
    }

    public function getTab(): ?string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }


    /**
     * @throws ExceptionCompile
     */
    public function setFromStoreValue($value): Metadata
    {

        $this->validityCheck($value);
        return parent::setFromStoreValue($value);

    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCompile
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

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return Site::getLang();
    }

    /**
     * @throws ExceptionCompile
     */
    private function validityCheck($value)
    {
        if ($value === "" || $value === null) {
            return;
        }
        if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
            throw new ExceptionCompile("The lang value ($value) for the page ($this) does not have two letters", $this->getCanonical());
        }
    }

    public function getCanonical(): string
    {
        return "lang";
    }

    public function getDirection()
    {
        /**
         * TODO: should be base on the page value
         * Search PHP and CLDR
         * https://punic.github.io/
         * https://www.php.net/manual/en/book.intl.php
         *
         * Example:
         * https://github.com/salarmehr/cosmopolitan
         * use Salarmehr\Cosmopolitan\Cosmo;
         *
         * echo Cosmo::create('fa')->direction(); // rlt
         * echo Cosmo::create('en')->direction(); // ltr
         */
        return Site::getLangDirection();
    }


}
