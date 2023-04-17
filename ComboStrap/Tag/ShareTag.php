<?php

namespace ComboStrap\Tag;

use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\BrandTag;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExecutionContext;
use ComboStrap\Icon;
use ComboStrap\LogUtility;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

class ShareTag
{
    const MARKUP = "share";
    const CANONICAL = "share";


    /**
     * @param TagAttributes $shareAttributes
     * @param $state
     * @return string
     */
    public static function renderSpecialEnter(TagAttributes $shareAttributes, $state): string
    {

        /**
         * The channel
         */
        try {
            $brandButton = BrandTag::createButtonFromAttributes($shareAttributes, BrandButton::TYPE_BUTTON_SHARE);
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("The brand creation returns an error ({$e->getMessage()}");
        }

        $rendererHtml = "";

        /**
         * Snippet
         */
        try {
            $style = $brandButton->getStyle();
        } catch (ExceptionCompile $e) {
            $rendererHtml .= LogUtility::wrapInRedForHtml("The style of the share button ($brandButton) could not be determined. Error: {$e->getMessage()}");
            return $rendererHtml;
        }
        $snippetId = $brandButton->getStyleScriptIdentifier();
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetId, $style);

        /**
         * Standard link attribute
         * and Runtime Cache key dependencies
         */
        try {
            ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingMarkupHandler()
                ->getOutputCacheDependencies()
                ->addDependency(MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY);
        } catch (ExceptionNotFound $e) {
            // not a fetcher markup run
        }

        try {
            $requestedPage = MarkupPath::createFromRequestedPage();
        } catch (ExceptionNotFound $e) {
            return LogUtility::wrapInRedForHtml("Share Error: Requested Page Not Found: ({$e->getMessage()}");
        }
        try {
            $type = $shareAttributes->getType();
            $buttonAttributes = $brandButton->getHtmlAttributes($requestedPage)
                ->setType($type)
                ->setLogicalTag($shareAttributes->getLogicalTag());
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("The social channel creation returns an error when creating the link ({$e->getMessage()}");
        }

        /**
         * Add the link
         */
        $element = $brandButton->getHtmlElement($buttonAttributes);
        $rendererHtml = $buttonAttributes->toHtmlEnterTag($element);

        /**
         * Icon
         */
        if ($brandButton->hasIcon()) {
            try {
                $iconAttributes = $brandButton->getIconAttributes();
                $tagIconAttributes = TagAttributes::createFromCallStackArray($iconAttributes);
                $rendererHtml .= Icon::createFromTagAttributes($tagIconAttributes)
                    ->toHtml();
            } catch (ExceptionCompile $e) {
                $message = "Getting the icon for the social channel ($brandButton) returns an error ({$e->getMessage()}";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionRuntime($message, self::CANONICAL, 1, $e);
                }
                $rendererHtml .= LogUtility::wrapInRedForHtml($message);
                // don't return because the anchor link is open
            }
        }

        /**
         * When empty tag, close the link
         */
        if ($state === DOKU_LEXER_SPECIAL) {
            $rendererHtml .= "</$element>";
        }

        return $rendererHtml;


    }

    public static function getKnownTypes(): array
    {
        return Brand::getBrandNamesForButtonType(BrandButton::TYPE_BUTTON_SHARE);
    }

    public static function renderExit(TagAttributes $tagAttributes): string
    {
        try {
            $requestedPage = MarkupPath::createFromRequestedPage();
        } catch (ExceptionNotFound $e) {
            return LogUtility::wrapInRedForHtml("Share Error: Requested Page Not Found: ({$e->getMessage()}");
        }
        try {

            $brandButton = BrandTag::createButtonFromAttributes($tagAttributes, BrandButton::TYPE_BUTTON_SHARE);
            $element = $brandButton->getHtmlElement($brandButton->getHtmlAttributes($requestedPage));
        } catch (ExceptionCompile $e) {
            LogUtility::internalError($e->getMessage(), self::CANONICAL, $e);
            $element = 'button';
        }
        return "</$element>";
    }

    public static function handleExit(\Doku_Handler $handler): array
    {

        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        return [PluginUtility::ATTRIBUTES => $openingTag->getAttributes()];

    }

}
