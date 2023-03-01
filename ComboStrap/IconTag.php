<?php

namespace ComboStrap;

use Doku_Handler;
use Doku_Renderer_metadata;
use Exception;
use syntax_plugin_combo_xmlinlinetag;
use syntax_plugin_combo_icon;
use syntax_plugin_combo_link;
use syntax_plugin_combo_media;
use syntax_plugin_combo_note;

class IconTag
{

    const CANONICAL = "icon";
    public const TAG = "icon";

    public static function handleSpecial(TagAttributes $tagAttributes, Doku_Handler $handler): array
    {
        // Get the parameters

        $callStack = CallStack::createFromHandler($handler);
        $parent = $callStack->moveToParent();
        $context = "";
        if ($parent !== false) {
            $context = $parent->getTagName();
            if ($context === syntax_plugin_combo_link::TAG) {
                $context = $parent->getTagName();
            }
        }
        /**
         * Color setting should know the color of its parent
         * For now, we don't set any color if the parent is a button, note, link
         * As a header is not a parent, we may say that if the icon is contained, the default
         * branding color is not set ?
         */
        $requestedColor = $tagAttributes->getValue(ColorRgb::COLOR);
        if (
            $requestedColor === null &&
            Site::isBrandingColorInheritanceEnabled() &&
            !in_array($context, [
                ButtonTag::MARKUP_LONG,
                syntax_plugin_combo_note::TAG,
                syntax_plugin_combo_link::TAG
            ])
        ) {

            $requestedWidth = $tagAttributes->getValue(Dimension::WIDTH_KEY, FetcherSvg::DEFAULT_ICON_WIDTH);

            // By default, a character icon
            $color = Site::getSecondaryColor();
            try {
                $requestedWidthInPx = ConditionalLength::createFromString($requestedWidth)->toPixelNumber();
                if ($requestedWidthInPx > 36) {
                    // Illustrative icon
                    $color = Site::getPrimaryColor();
                }
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("The requested icon width ($requestedWidth) is not a conform width. Error: " . $e->getMessage(), self::CANONICAL, $e);
            }

            if ($color !== null) {
                $tagAttributes->setComponentAttributeValue(ColorRgb::COLOR, $color);
            }
        }
        return array(PluginUtility::CONTEXT => $context);
    }

    public static function exceptionHandling(Exception $e, $tagAttribute): string
    {
        $errorClass = syntax_plugin_combo_media::SVG_RENDERING_ERROR_CLASS;
        $message = "Icon ({$tagAttribute->getValue("name")}). Error while rendering: {$e->getMessage()}";
        $html = "<span class=\"text-danger $errorClass\">" . hsc(trim($message)) . "</span>";
        LogUtility::warning($message, syntax_plugin_combo_icon::CANONICAL, $e);
        return $html;
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function render(TagAttributes $tagAttributes): string
    {

        try {
            return Icon::createFromTagAttributes($tagAttributes)
                ->toHtml();
        } catch (\Exception $e) {
            // catch all
            return IconTag::exceptionHandling($e, $tagAttributes);
        }

    }

    /**
     * @param Doku_Renderer_metadata $renderer
     * @param $tagAttribute
     * @return void
     */
    public static function metadata(Doku_Renderer_metadata $renderer, $tagAttribute)
    {

        try {
            $mediaPath = Icon::createFromTagAttributes($tagAttribute)->getFetchSvg()->getSourcePath();
        } catch (ExceptionCompile $e) {
            // error is already fired in the renderer
            return;
        }
        if (FileSystems::exists($mediaPath)) {
            syntax_plugin_combo_media::registerFirstImage($renderer, $mediaPath);
        }
    }

    public static function handleEnter(TagAttributes $tagAttributes, Doku_Handler $handler): array
    {
        return self::handleSpecial($tagAttributes, $handler);
    }

    public static function handleExit(Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
        return array(
            PluginUtility::ATTRIBUTES => $openingCall->getAttributes(),
            PluginUtility::CONTEXT => $openingCall->getContext()
        );
    }
}
