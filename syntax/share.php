<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\ArrayUtility;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dictionary;
use ComboStrap\ExceptionCombo;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageScope;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;


/**
 */
class syntax_plugin_combo_share extends DokuWiki_Syntax_Plugin
{


    const TAG = "share";
    const CANONICAL = self::TAG;
    const GENERATED_TYPE = "generated";
    const NAMED_TYPE = "named";
    const FRAGMENT_ATTRIBUTE = "fragment";


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

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    function getAllowedTypes(): array
    {
        return array('substition', 'formatting', 'disabled');
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
                $attributes = TagAttributes::createFromTagMatch($match);

                /**
                 * The channel
                 */
                $channelName = $attributes->getValueAndRemoveIfPresent(TagAttributes::TYPE_KEY);
                if ($channelName == null) {
                    $channelName = "twitter";
                }
                $channelName = strtolower($channelName);

                /**
                 * Get the channels
                 */
                try {
                    $channels = Dictionary::getFrom("social-channels");
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "The channel dictionary returns an error ({$e->getMessage()}";
                    return $returnArray;
                }
                /**
                 * Get the data for the channel
                 */
                $channel = $channels[$channelName];
                if ($channel === null) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "The channel ($channelName} is unknown.";
                    return $returnArray;
                }
                /**
                 * Shared Url
                 */
                $shareUrlTemplate = $channel["endpoint"];
                $requestedPage = Page::createPageFromRequestedPage();
                $sharedUrl = TemplateUtility::renderStringTemplateForDataPage($shareUrlTemplate, $requestedPage);

                $strict = $attributes->getBooleanValueAndRemoveIfPresent(TagAttributes::STRICT, true);

                /**
                 * Scope if in slot
                 */
                $renderedPage = Page::createPageFromGlobalDokuwikiId();
                if ($renderedPage->isSlot()) {
                    // The output is dependent on the rendered page
                    $renderedPage->setScope(PageScope::SCOPE_CURRENT_REQUESTED_PAGE_VALUE);
                }


                $attributes->addComponentAttributeValue(LinkUtility::ATTRIBUTE_REF, $sharedUrl);
                $this->openLinkInCallStack($callStack, $attributes);
                if ($state === DOKU_LEXER_SPECIAL) {
                    $this->addLinkContentInCallStack($callStack, $sharedUrl);
                    $this->closeLinkInCallStack($callStack);
                }
                return $returnArray;


            case DOKU_LEXER_EXIT:

                $callStack = CallStack::createFromHandler($handler);
                $this->closeLinkInCallStack($callStack);
                return $returnArray;

            case DOKU_LEXER_UNMATCHED:

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


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

    /**
     * @param CallStack $callStack
     * @param TagAttributes $tagAttributes
     */
    private function openLinkInCallStack(CallStack $callStack, TagAttributes $tagAttributes)
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
    }

    private function addLinkContentInCallStack(CallStack $callStack, string $payload)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_UNMATCHED,
                [],
                null,
                null,
                $payload
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

