<?php

namespace ComboStrap\Tag;

use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\BrandTag;
use ComboStrap\ExceptionCompile;
use ComboStrap\Icon;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use syntax_plugin_combo_link;

class FollowTag
{


    public const CANONICAL = FollowTag::MARKUP;
    public const HANDLE_ATTRIBUTE = "handle";
    public const MARKUP = "follow";

    public static function getKnownTypes(): array
    {
        return Brand::getBrandNamesForButtonType(BrandButton::TYPE_BUTTON_FOLLOW);
    }


    public static function renderExit(): string
    {
        return '</a>';
    }

    public static function renderSpecialEnterNode(TagAttributes $tagAttributes, $state): string
    {


        if (
            !$tagAttributes->hasAttribute(syntax_plugin_combo_link::MARKUP_REF_ATTRIBUTE)
            && !$tagAttributes->hasAttribute(FollowTag::HANDLE_ATTRIBUTE)
        ) {
            $handleAttribute = FollowTag::HANDLE_ATTRIBUTE;
            $urlAttribute = BrandTag::URL_ATTRIBUTE;
            $message = "The brand button does not have any follow url. You need to set at minimum the `$handleAttribute` or `$urlAttribute` attribute";
            return self::returnErrorString($message, $state);
        }

        /**
         * The channel
         */
        try {
            $brand = BrandTag::createButtonFromAttributes($tagAttributes, BrandButton::TYPE_BUTTON_FOLLOW);
        } catch (ExceptionCompile $e) {
            $message = "The brand button creation returns an error ({$e->getMessage()}";
            return self::returnErrorString($message, $state);
        }

        /**
         * Add the Icon / CSS / Javascript snippet
         * It should happen only in rendering
         */
        try {
            $style = $brand->getStyle();
        } catch (ExceptionCompile $e) {
            $message = "The style of the share button ($brand) could not be determined. Error: {$e->getMessage()}";
            return self::returnErrorString($message, $state);
        }
        $snippetId = $brand->getStyleScriptIdentifier();
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetId, $style);


        /**
         * Standard link attribute
         * and add the link
         */
        try {
            $tagAttributes = BrandTag::mixBrandButtonToTagAttributes($tagAttributes, $brand);
            $html = $tagAttributes->toHtmlEnterTag("a");
        } catch (ExceptionCompile $e) {
            $message = "The brand button creation returns an error when creating the link ({$e->getMessage()}";
            return self::returnErrorString($message, $state);
        }

        /**
         * Icon
         */
        try {
            $iconAttributes = TagAttributes::createFromCallStackArray($brand->getIconAttributes());
            $html .= Icon::createFromTagAttributes($iconAttributes)->toHtml();
        } catch (ExceptionCompile $e) {
            $message = "Getting the icon for the brand ($brand) returns an error ({$e->getMessage()}";
            return self::returnErrorString($message,$state);
        }

        if ($state === DOKU_LEXER_SPECIAL) {
            $html .= "</a>";
        }
        return $html;

    }

    private static function returnErrorString($message, $state): string
    {

        $message = LogUtility::wrapInRedForHtml($message);
        if ($state === DOKU_LEXER_SPECIAL) {
            return $message;
        }
        /**
         * An empty anchor to return in case of errors
         * to have a valid document with the exit
         */
        return "<a>$message";
    }

}
