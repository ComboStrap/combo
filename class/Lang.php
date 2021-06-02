<?php


namespace ComboStrap;


class Lang
{

    const CANONICAL = "lang";
    const LANG_ATTRIBUTES = "lang";

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
        if ($attributes->hasComponentAttribute(self::LANG_ATTRIBUTES)) {
            $langValue = $attributes->getValueAndRemove(self::LANG_ATTRIBUTES);
            $attributes->addHtmlAttributeValue("lang",$langValue);

            // Language about the data
            $layoutUrl = "https://github.com/unicode-org/cldr-json/blob/master/cldr-json/cldr-misc-modern/main/$langValue/layout.json";

        }

    }

}
