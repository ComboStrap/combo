<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\ArrayUtility;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Canonical;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


/**
 */
class syntax_plugin_combo_permalink extends DokuWiki_Syntax_Plugin
{


    const TAG = "permalink";
    const CANONICAL = self::TAG;
    const GENERATED_TYPE = "generated";
    const NAMED_TYPE = "named";
    const FRAGMENT_ATTRIBUTE = "fragment";

    function getType()
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
        /**
         * permalink
         */
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {


            case DOKU_LEXER_SPECIAL :

                $callStack = CallStack::createFromHandler($handler);
                $attributes = TagAttributes::createFromTagMatch($match);

                $type = $attributes->getValueAndRemoveIfPresent(TagAttributes::TYPE_KEY);
                if ($type == null) {
                    $type = self::GENERATED_TYPE;
                } else {
                    $type = strtolower($type);
                }

                $returnArray = array(
                    PluginUtility::STATE => $state,
                );

                $page = Page::createPageFromGlobalDokuwikiId();
                $fragment = $attributes->getValueAndRemoveIfPresent(self::FRAGMENT_ATTRIBUTE);
                switch ($type) {
                    case self::GENERATED_TYPE:
                        $pageId = $page->getPageId();
                        if ($pageId === null) {
                            $errorMessage = "The page id has not yet been set";
                            $returnArray[PluginUtility::PAYLOAD] = $errorMessage;
                            return $returnArray;
                        }
                        $permanentValue = Page::encodePageId($pageId);
                        $url = Site::getBaseUrl() . "$permanentValue";
                        if ($fragment != null) {
                            $url .= "#$fragment";
                        }
                        $attributes->addComponentAttributeValue(LinkUtility::ATTRIBUTE_REF, $url);
                        $this->createLink($callStack, $attributes, $url);
                        return $returnArray;
                    case self::NAMED_TYPE:
                        $canonical = $page->getCanonical();
                        if ($canonical === null) {
                            $documentationUrlForCanonical = PluginUtility::getDocumentationHyperLink(Canonical::CANONICAL, "canonical value");
                            $errorMessage = "The page ($page) does not have a $documentationUrlForCanonical. We can't create a named permalink";
                            $returnArray[PluginUtility::PAYLOAD] = $errorMessage;
                        } else {
                            $canonicalUrl = $page->getCanonicalUrl();
                            if ($fragment != null) {
                                $canonicalUrl .= "#$fragment";
                            }
                            $attributes->addComponentAttributeValue(LinkUtility::ATTRIBUTE_REF, $canonicalUrl);
                            $this->createLink($callStack, $attributes, $canonicalUrl);
                        }
                        return $returnArray;
                    default:
                        $errorMessage = "The permalink type ({$attributes->getType()} is unknown.";
                        $returnArray[PluginUtility::PAYLOAD] = $errorMessage;
                        return $returnArray;

                }


        }
        return array();

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
            $errorMessage = $data[PluginUtility::PAYLOAD];
            if (!empty($errorMessage)) {
                LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                $renderer->doc .= "<span class=\"text-warning\">{$errorMessage}</span>";
            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    /**
     * @param CallStack $callStack
     * @param TagAttributes $tagAttributes
     * @param string $url
     */
    private function createLink(CallStack $callStack, TagAttributes $tagAttributes, string $url)
    {
        $parent = $callStack->moveToParent();
        $context = "";
        $attributes = $tagAttributes->toCallStackArray();
        if ($parent != null) {
            $context = $parent->getTagName();
            $attributes = ArrayUtility::mergeByValue($parent->getAttributes(), $attributes);
        }
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_ENTER,
                $attributes,
                $context
            ));
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_UNMATCHED,
                [],
                null,
                null,
                $url
            ));
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_EXIT
            ));
    }


}

