<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\ButtonTag;
use ComboStrap\ExecutionContext;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Canonical;
use ComboStrap\Display;
use ComboStrap\WikiPath;
use ComboStrap\DokuwikiId;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;
use ComboStrap\OutlineSection;
use ComboStrap\MarkupPath;
use ComboStrap\PageUrlPath;
use ComboStrap\PageUrlType;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;
use ComboStrap\UrlEndpoint;


/**
 */
class syntax_plugin_combo_permalink extends DokuWiki_Syntax_Plugin
{


    const TAG = "permalink";
    const CANONICAL = self::TAG;
    const GENERATED_TYPE = "generated";
    const NAMED_TYPE = "named";
    const FRAGMENT_ATTRIBUTE = "fragment";

    private static function handleError(string $errorMessage, bool $strict, array $returnArray, CallStack $callStack): array
    {
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

    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'normal';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {

        $entryPattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($entryPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

        /**
         * permalink
         */
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $returnArray = array(
            PluginUtility::STATE => $state,
        );
        switch ($state) {

            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_SPECIAL:

                $callStack = CallStack::createFromHandler($handler);
                $knownTypes = [self::NAMED_TYPE, self::GENERATED_TYPE];
                $attributes = TagAttributes::createFromTagMatch($match, [], $knownTypes);

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
                        ->getCacheDependencies()
                        ->addDependency(MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                } catch (ExceptionNotFound $e) {
                    // not a fetcher markup run
                }


                $requestedPage = MarkupPath::createFromRequestedPage();
                $fragment = $attributes->getValueAndRemoveIfPresent(self::FRAGMENT_ATTRIBUTE);
                switch ($type) {
                    case self::GENERATED_TYPE:
                        try {
                            $pageId = $requestedPage->getPageId();
                        } catch (ExceptionNotFound $e) {
                            return self::handleError(
                                "The page id has not yet been set",
                                $strict,
                                $returnArray,
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
                            $this->addLinkContentInCallStack($callStack, $url);
                            $this->closeLinkInCallStack($callStack);
                        }
                        return $returnArray;
                    case self::NAMED_TYPE:
                        try {
                            $requestedPage->getCanonical();
                        } catch (ExceptionNotFound $e) {
                            $documentationUrlForCanonical = PluginUtility::getDocumentationHyperLink(Canonical::PROPERTY_NAME, "canonical value");
                            $errorMessage = "The page ($requestedPage) does not have a $documentationUrlForCanonical. We can't create a named permalink";
                            return self::handleError($errorMessage, $strict, $returnArray, $callStack);
                        }

                        $urlPath = PageUrlPath::createForPage($requestedPage)
                            ->getUrlPathFromType(PageUrlType::CONF_VALUE_CANONICAL_PATH);
                        $urlId = WikiPath::toDokuWikiId($urlPath); // delete the root sep (ie :)
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
                            $this->addLinkContentInCallStack($callStack, $canonicalUrl);
                            $this->closeLinkInCallStack($callStack);
                        }
                        return $returnArray;
                    default:
                        return self::handleError(
                            "The permalink type ({$attributes->getType()} is unknown.",
                            $strict,
                            $returnArray,
                            $callStack
                        );

                }
            case DOKU_LEXER_EXIT:

                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                if ($openingCall->getExitCode() === 0) {
                    // no error
                    $this->closeLinkInCallStack($callStack);
                }
                return $returnArray;
            case DOKU_LEXER_UNMATCHED:
                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToParent();
                if ($openingCall->getExitCode() === 0) {
                    // no error
                    return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);
                }
                return $returnArray;


        }
        return $returnArray;

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                default:
                    $errorMessage = $data[PluginUtility::EXIT_MESSAGE];
                    if (!empty($errorMessage)) {
                        LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        $renderer->doc .= "<span class=\"text-warning\">{$errorMessage}</span>";
                    }
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


    private function addLinkContentInCallStack(CallStack $callStack, string $url)
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

    private function closeLinkInCallStack(CallStack $callStack)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_EXIT
            ));
    }


}

