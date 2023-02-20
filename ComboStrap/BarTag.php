<?php

namespace ComboStrap;

use action_plugin_combo_headingpostprocessing;

/**
 * Separator: See: https://getwaves.io/
 *
 * * horizontal block are known as section in mjml
 */
class BarTag
{


    public const SIZE_ATTRIBUTE = "size";
    public const HTML_TAG_ATTRIBUTES = "html_tag";
    public const CANONICAL = BarTag::SLIDE_TAG;
    public const CONF_ENABLE_BAR_EDITING = "enableBarEditing";
    public const SLIDE_TAG = "slide";
    public const BAR_TAG = "bar";
    public const HTML_SECTION_TAG = "section";
    const LOGICAL_TAG = self::BAR_TAG;
    public static $tags = [BarTag::BAR_TAG, BarTag::SLIDE_TAG];

    public static function getHtmlTag(): string
    {
        $tag = "div";
        try {
            $isFragmentExecution = ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingMarkupHandler()
                ->isFragment();
            if (!$isFragmentExecution) {
                $tag = self::HTML_SECTION_TAG;
            }
            return $tag;
        } catch (ExceptionNotFound $e) {
            return $tag;
        }
    }

    public static function handleEnter(TagAttributes $tagAttributes): array
    {

        $htmlTag = BarTag::getHtmlTag();

        /**
         * Deprecation
         */
        $size = $tagAttributes->getValueAndRemoveIfPresent(BarTag::SIZE_ATTRIBUTE);
        if ($size !== null) {
            LogUtility::warning("The size attribute on bar/slide has been deprecated for the hero attribute");
            $tagAttributes->setComponentAttributeValue(Hero::ATTRIBUTE, $size);
        }

        return array(BarTag::HTML_TAG_ATTRIBUTES => $htmlTag);
    }

    public static function handleExit(\Doku_Handler $handler, int $pos, string $match): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

        /**
         * Section heading control
         */
        $htmlTag = $openingTag->getPluginData(BarTag::HTML_TAG_ATTRIBUTES);
        $message = null;
        if ($htmlTag === BarTag::HTML_SECTION_TAG) {
            $headingFound = false;
            while ($actualCall = $callStack->next()) {
                if (in_array($actualCall->getTagName(), action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                    $headingFound = true;
                    break;
                }
            }
            if (!$headingFound) {
                $id = $openingTag->getIdOrDefault();
                $message = "No heading was found in the section bar ($id). An heading is mandatory for navigation within device.";
            }
        }

        if (SiteConfig::getConfValue(BarTag::CONF_ENABLE_BAR_EDITING, 1)) {

            $position = $openingTag->getFirstMatchedCharacterPosition();
            try {
                $startPosition = DataType::toInteger($position);
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("The position of the slide is not an integer", BarTag::CANONICAL);
                $startPosition = null;
            }
            $id = $openingTag->getIdOrDefault();
            // +1 to go at the line
            $endPosition = $pos + strlen($match) + 1;
            $tag = BarTag::BAR_TAG;
            $editButtonCall = EditButton::create("Edit $tag $id")
                ->setStartPosition($startPosition)
                ->setEndPosition($endPosition)
                ->toComboCallComboFormat();
            $callStack->moveToEnd();
            $callStack->insertBefore($editButtonCall);
        }


        return array(
            BarTag::HTML_TAG_ATTRIBUTES => $htmlTag,
            PluginUtility::EXIT_MESSAGE => $message
        );
    }

    public static function renderEnterXhtml(TagAttributes $attributes, array $data): string
    {
        $barTag = BarTag::BAR_TAG;
        $attributes->addClassName($barTag);

        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($barTag);

        $htmlTag = $data[BarTag::HTML_TAG_ATTRIBUTES];
        $html = $attributes->toHtmlEnterTag($htmlTag);

        $layoutContainer = SiteConfig::getConfValue(ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF, ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
        $containerClass = ContainerTag::getClassName($layoutContainer);

        $html .= "<div class=\"$barTag-body position-relative $containerClass\">";
        return $html;
    }

    public static function renderExitXhtml(array $data): string
    {
        /**
         * End body
         */
        $html = '</div>';

        /**
         * End component
         */
        $htmlTag = $data[BarTag::HTML_TAG_ATTRIBUTES];
        $html .= "</$htmlTag>";
        return $html;
    }
}
