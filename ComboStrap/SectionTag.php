<?php

namespace ComboStrap;


class SectionTag
{


    public const CANONICAL = HeadingTag::CANONICAL;
    public const TAG = "section";

    public static function renderEnterXhtml(TagAttributes $tag): string
    {
        $level = $tag->getComponentAttributeValueAndRemoveIfPresent(HeadingTag::LEVEL);
        if ($level !== null) {
            $tag->addClassName(StyleUtility::addComboStrapSuffix("outline-section"));
            $tag->addClassName(StyleUtility::addComboStrapSuffix("outline-level-$level"));
        }
        return $tag->toHtmlEnterTag("section");
    }

    public static function renderExitXhtml(): string
    {
        return '</section>';
    }


}

