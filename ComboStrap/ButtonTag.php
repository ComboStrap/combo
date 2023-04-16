<?php

namespace ComboStrap;

use ComboStrap\TagAttribute\Shadow;
use DokuWiki_Syntax_Plugin;
use syntax_plugin_combo_link;
use syntax_plugin_combo_menubar;

/**
 * ===== For the Geek =====
 * This is not the [[https://www.w3.org/TR/wai-aria-practices/#button|Button as describe by the Web Specification]]
 * but a styling over a [[https://www.w3.org/TR/wai-aria-practices/#link|link]]
 *
 * ===== Documentation / Reference =====
 * https://material.io/components/buttons
 * https://getbootstrap.com/docs/4.5/components/buttons/
 */
class ButtonTag
{

    public const TYPES = [
        ColorRgb::PRIMARY_VALUE,
        ColorRgb::SECONDARY_VALUE,
        "success",
        "danger",
        "warning",
        "info",
        "light",
        "dark",
        "link"
    ];
    public const MARKUP_LONG = "button";
    public const MARKUP_SHORT = "btn";
    const LOGICAL_TAG = self::MARKUP_LONG;

    /**
     * @param TagAttributes $tagAttributes
     */
    public static function processButtonAttributesToHtmlAttributes(TagAttributes &$tagAttributes)
    {
        # A button
        $btn = "btn";
        $tagAttributes->addClassName($btn);

        $type = $tagAttributes->getValue(TagAttributes::TYPE_KEY, "primary");
        $skin = $tagAttributes->getValue(Skin::SKIN_ATTRIBUTE, Skin::FILLED_VALUE);
        switch ($skin) {
            case "contained":
            {
                $tagAttributes->addClassName("$btn-$type");
                $tagAttributes->addComponentAttributeValue(Shadow::CANONICAL, true);
                break;
            }
            case "filled":
            {
                $tagAttributes->addClassName("$btn-$type");
                break;
            }
            case "outline":
            {
                $tagAttributes->addClassName("$btn-outline-$type");
                break;
            }
            case "text":
            {
                $tagAttributes->addClassName("$btn-link");
                $tagAttributes->addComponentAttributeValue(TextColor::TEXT_COLOR_ATTRIBUTE, $type);
                break;
            }
        }


        $sizeAttribute = "size";
        if ($tagAttributes->hasComponentAttribute($sizeAttribute)) {
            $size = $tagAttributes->getValueAndRemove($sizeAttribute);
            switch ($size) {
                case "lg":
                case "large":
                    $tagAttributes->addClassName("btn-lg");
                    break;
                case "sm":
                case "small":
                    $tagAttributes->addClassName("btn-sm");
                    break;
            }
        }
    }

    public static function getTags(): array
    {
        $elements[] = ButtonTag::MARKUP_LONG;
        $elements[] = ButtonTag::MARKUP_SHORT;
        return $elements;
    }

    public static function handleEnter(TagAttributes $attributes, \Doku_Handler $handler): array
    {
        /**
         * Note: Branding color (primary and secondary)
         * are set with the {@link Skin}
         */

        /**
         * The parent
         * to apply automatically styling in a bar
         */
        $callStack = CallStack::createFromHandler($handler);
        $isInMenuBar = false;
        while ($parent = $callStack->moveToParent()) {
            if ($parent->getTagName() === syntax_plugin_combo_menubar::TAG) {
                $isInMenuBar = true;
                break;
            }
        }
        if ($isInMenuBar) {
            if (!$attributes->hasAttribute("class") && !$attributes->hasAttribute("spacing")) {
                $attributes->addComponentAttributeValue("spacing", "mr-2 mb-2 mt-2 mb-lg-0 mt-lg-0");
            }
        }

        /**
         * The context give set if this is a button
         * or a link button
         * The context is checked in the `exit` state
         * Default context: This is not a link button
         */
        $context = ButtonTag::MARKUP_LONG;


        return array(
            PluginUtility::CONTEXT => $context
        );
    }

    public static function handleExit(\Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        /**
         * Button or link button
         */
        $context = ButtonTag::MARKUP_LONG;
        $descendant = $callStack->moveToFirstChildTag();
        if ($descendant !== false) {
            if ($descendant->getTagName() === syntax_plugin_combo_link::TAG) {
                $context = syntax_plugin_combo_link::TAG;
            }
        }
        $openingTag->setContext($context);

        return array(
            PluginUtility::CONTEXT => $context
        );
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes, DokuWiki_Syntax_Plugin $plugin, array $data): string
    {
        /**
         * CSS if dokuwiki class name for link
         */
        if ($plugin->getConf(LinkMarkup::CONF_USE_DOKUWIKI_CLASS_NAME, false)) {
            PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(ButtonTag::MARKUP_LONG);
        }

        /**
         * If this not a link button
         * The context is set on the handle exit
         */
        $context = $data[PluginUtility::CONTEXT];
        if ($context == ButtonTag::LOGICAL_TAG) {
            $tagAttributes->setDefaultStyleClassShouldBeAdded(false);
            ButtonTag::processButtonAttributesToHtmlAttributes($tagAttributes);
            $tagAttributes->addOutputAttributeValue("type", "button");
            return $tagAttributes->toHtmlEnterTag('button');
        }
        return "";

    }

    public static function renderExitXhtml($data): string
    {
        $context = $data[PluginUtility::CONTEXT];
        /**
         * If this is a button and not a link button
         */
        if ($context === ButtonTag::MARKUP_LONG) {
            return '</button>';
        }
        return "";
    }
}
