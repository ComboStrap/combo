<?php

namespace ComboStrap;


use syntax_plugin_combo_headingwiki;


class SectionTag
{


    public const CANONICAL = HeadingTag::CANONICAL;
    public const TAG = "section";

    public static function renderEnterXhtml(TagAttributes $tag): string
    {
        if(!self::doWeRenderSection()){
            return "";
        }

        $level = $tag->getComponentAttributeValueAndRemoveIfPresent(HeadingTag::LEVEL);
        if ($level !== null) {
            $tag->addClassName(StyleUtility::addComboStrapSuffix("outline-section"));
            $tag->addClassName(StyleUtility::addComboStrapSuffix("outline-level-$level"));
        }
        return $tag->toHtmlEnterTag("section");
    }

    public static function renderExitXhtml(): string
    {
        if(!self::doWeRenderSection()){
            return "";
        }

        return '</section>';
    }

    /**
     * The actual dokuwiki heading is to
     * add the edit section comment
     * when rendering the next heading
     * See {@link Doku_Renderer_xhtml::header()}
     * that calls {@link Doku_Renderer_xhtml::finishSectionEdit()}
     *
     * It means that the edit button of the previous section would be
     * between the next section and next heading
     */
    private static function doWeRenderSection(): bool
    {
        $wikiHeadingEnabled = syntax_plugin_combo_headingwiki::isEnabled();
        if (!$wikiHeadingEnabled) {
            return false;
        }
        return true;
    }
}

