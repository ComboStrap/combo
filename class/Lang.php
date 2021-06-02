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
     * Language supported: http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
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
        }

    }

}
