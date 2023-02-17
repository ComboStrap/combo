<?php

namespace ComboStrap;

use syntax_plugin_combo_brand;

class ShareTag
{
    const MARKUP = "share";
    const CANONICAL = "share";


    public static function render(TagAttributes $shareAttributes, $state): string
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
                ->getCacheDependencies()
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
            $linkAttributes = $brandButton->getLinkAttributes($requestedPage)
                ->setType($type)
                ->setLogicalTag($shareAttributes->getLogicalTag());
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("The social channel creation returns an error when creating the link ({$e->getMessage()}");
        }

        /**
         * Add the link
         */
        $rendererHtml = $linkAttributes->toHtmlEnterTag("a");

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
            $rendererHtml .= "</a>";
        }

        return $rendererHtml;



    }

}
