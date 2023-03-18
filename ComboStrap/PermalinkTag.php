<?php

namespace ComboStrap;


use ComboStrap\Web\UrlEndpoint;
use Doku_Handler;
use syntax_plugin_combo_link;


class PermalinkTag
{


    public const GENERATED_TYPE = "generated";
    public const CANONICAL = PermalinkTag::TAG;
    public const NAMED_TYPE = "named";
    public const TAG = "permalink";
    public const FRAGMENT_ATTRIBUTE = "fragment";

    public static function handleEnterSpecial(TagAttributes $attributes, int $state, Doku_Handler $handler): array
    {

        $callStack = CallStack::createFromHandler($handler);
        $type = $attributes->getValueAndRemoveIfPresent(TagAttributes::TYPE_KEY);
        if ($type == null) {
            $type = self::GENERATED_TYPE;
        } else {
            $type = strtolower($type);
        }

        $strict = $attributes->getBooleanValueAndRemoveIfPresent(TagAttributes::STRICT, true);

        /**
         * Cache key dependencies
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
            return self::handleError(
                "No requested page was found",
                $strict,
                $callStack
            );
        }
        $fragment = $attributes->getValueAndRemoveIfPresent(self::FRAGMENT_ATTRIBUTE);
        switch ($type) {
            case self::GENERATED_TYPE:
                try {
                    $pageId = $requestedPage->getPageId();
                } catch (ExceptionNotFound $e) {
                    return self::handleError(
                        "The page id has not yet been set",
                        $strict,
                        $callStack
                    );
                }

                $permanentValue = PageUrlPath::encodePageId($pageId);

                $url = UrlEndpoint::createBaseUrl()
                    ->setPath("/$permanentValue")
                    ->toAbsoluteUrl();

                /** @noinspection DuplicatedCode */
                if ($fragment !== null) {
                    $fragment = OutlineSection::textToHtmlSectionId($fragment);
                    $url->setFragment($fragment);
                }
                $attributes->addComponentAttributeValue(syntax_plugin_combo_link::MARKUP_REF_ATTRIBUTE, $url);
                $attributes->addOutputAttributeValue("rel", "nofollow");
                syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $attributes);
                if ($state === DOKU_LEXER_SPECIAL) {
                    self::addLinkContentInCallStack($callStack, $url);
                    self::closeLinkInCallStack($callStack);
                }
                return [];
            case self::NAMED_TYPE:
                try {
                    $requestedPage->getCanonical();
                } catch (ExceptionNotFound $e) {
                    $documentationUrlForCanonical = PluginUtility::getDocumentationHyperLink(Canonical::PROPERTY_NAME, "canonical value");
                    $errorMessage = "The page ($requestedPage) does not have a $documentationUrlForCanonical. We can't create a named permalink";
                    return self::handleError($errorMessage, $strict, $callStack);
                }

                $urlPath = PageUrlPath::createForPage($requestedPage)
                    ->getUrlPathFromType(PageUrlType::CONF_VALUE_CANONICAL_PATH);
                $urlId = WikiPath::removeRootSepIfPresent($urlPath); // delete the root sep (ie :)
                $canonicalUrl = UrlEndpoint::createDokuUrl()
                    ->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $urlId)
                    ->toAbsoluteUrl();
                /** @noinspection DuplicatedCode */
                if ($fragment !== null) {
                    $fragment = OutlineSection::textToHtmlSectionId($fragment);
                    $canonicalUrl->setFragment($fragment);
                }
                $attributes->addComponentAttributeValue(syntax_plugin_combo_link::MARKUP_REF_ATTRIBUTE, $canonicalUrl);
                $attributes->addOutputAttributeValue("rel", "nofollow");
                syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $attributes);
                if ($state === DOKU_LEXER_SPECIAL) {
                    self::addLinkContentInCallStack($callStack, $canonicalUrl);
                    self::closeLinkInCallStack($callStack);
                }
                return [];
            default:
                return self::handleError(
                    "The permalink type ({$attributes->getType()} is unknown.",
                    $strict,
                    $callStack
                );

        }
    }

    public static function handleError(string $errorMessage, bool $strict, CallStack $callStack): array
    {

        $returnArray = [];
        if ($strict) {
            $returnArray[PluginUtility::EXIT_MESSAGE] = $errorMessage;
        }
        $returnArray[PluginUtility::EXIT_CODE] = 1;

        /**
         * If this is a button, we cache it
         */
        $parent = $callStack->moveToParent();
        if ($parent !== false && $parent->getTagName() === ButtonTag::MARKUP_LONG) {
            $parent->addAttribute(Display::DISPLAY, Display::DISPLAY_NONE_VALUE);
        }

        return $returnArray;
    }

    public static function getKnownTypes(): array
    {
        return [PermalinkTag::NAMED_TYPE, PermalinkTag::GENERATED_TYPE];
    }

    public static function addLinkContentInCallStack(CallStack $callStack, string $url)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_UNMATCHED,
                [],
                null,
                null,
                $url
            ));
    }

    public static function handeExit(Doku_Handler $handler)
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
        if ($openingCall->getExitCode() === 0) {
            // no error
            self::closeLinkInCallStack($callStack);
        }
    }

    public static function closeLinkInCallStack(CallStack $callStack)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_EXIT
            ));
    }

    public static function renderEnterSpecialXhtml(array $data): string
    {
        $errorMessage = $data[PluginUtility::EXIT_MESSAGE];
        if (!empty($errorMessage)) {
            LogUtility::warning($errorMessage, PermalinkTag::CANONICAL);
            return "<span class=\"text-warning\">{$errorMessage}</span>";
        }
        return "";
    }
}

