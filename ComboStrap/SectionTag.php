<?php

namespace ComboStrap;


use ComboStrap\TagAttribute\StyleAttribute;

class SectionTag
{


    public const CANONICAL = HeadingTag::CANONICAL;
    public const TAG = "section";

    public static function renderEnterXhtml(TagAttributes $tag): string
    {
        $level = $tag->getComponentAttributeValueAndRemoveIfPresent(HeadingTag::LEVEL);
        if ($level !== null) {
            $tag->addClassName(StyleAttribute::addComboStrapSuffix("outline-section"));
            $tag->addClassName(StyleAttribute::addComboStrapSuffix("outline-level-$level"));
        }
        return $tag->toHtmlEnterTag("section");
    }

    public static function renderExitXhtml(): string
    {
        return '</section>';
    }


}

