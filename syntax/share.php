<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\ArrayUtility;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dictionary;
use ComboStrap\DokuwikiUrl;
use ComboStrap\ExceptionCombo;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageScope;
use ComboStrap\PluginUtility;
use ComboStrap\SocialChannel;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;


/**
 */
class syntax_plugin_combo_share extends DokuWiki_Syntax_Plugin
{


    const TAG = "share";
    const CANONICAL = self::TAG;


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
                $defaultAttributes = [
                    TagAttributes::TYPE_KEY => "twitter"
                ];
                $shareAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes)
                    ->setLogicalTag(self::TAG);
                $linkAttributes = TagAttributes::createEmpty(self::TAG);

                /**
                 * The channel
                 */
                $channelName = $shareAttributes->getValue(TagAttributes::TYPE_KEY);
                try {
                    $socialChannel = SocialChannel::create($channelName);
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "The social channel creation ($channelName) returns an error ({$e->getMessage()}";
                    return $returnArray;
                }
                $requestedPage = Page::createPageFromRequestedPage();
                try {
                    $sharedUrl = $socialChannel->getUrlForPage($requestedPage);
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "Getting the url for the social channel ($channelName) returns an error ({$e->getMessage()}";
                    return $returnArray;
                }

                $strict = $linkAttributes->getBooleanValueAndRemoveIfPresent(TagAttributes::STRICT, true);

                /**
                 * Scope if in slot
                 */
                $renderedPage = Page::createPageFromGlobalDokuwikiId();
                if ($renderedPage->isSlot()) {
                    // The output is dependent on the rendered page
                    $renderedPage->setScope(PageScope::SCOPE_CURRENT_REQUESTED_PAGE_VALUE);
                }


                $linkAttributes->addComponentAttributeValue(TagAttributes::CLASS_KEY, "btn {$socialChannel->getClass()}");
                $linkAttributes->addComponentAttributeValue(LinkUtility::ATTRIBUTE_REF, $sharedUrl);
                $linkAttributes->addComponentAttributeValue("target", "_blank");
                $linkAttributes->addComponentAttributeValue("rel", "noopener");
                $linkTitle = $socialChannel->getLinkTitle();
                $linkAttributes->addComponentAttributeValue("title", $linkTitle);

                /**
                 * Label
                 */
                $size = "small";
                switch ($size) {
                    case "large":
                        $label = "Share on " . ucfirst($channelName);
                        break;
                    case "medium":
                        $label = ucfirst($channelName);
                        break;
                    default:
                        $label = "";
                        break;
                }
                $ariaLabel = "Share on " . ucfirst($channelName);
                $linkAttributes->addComponentAttributeValue("aria-label", $ariaLabel);


                $this->openLinkInCallStack($callStack, $linkAttributes);
                try {
                    $this->addIconInCallStack($callStack, $socialChannel->getIconName());
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "Getting the icon for the social channel ($channelName) returns an error ({$e->getMessage()}";
                    return $returnArray;
                }
                if ($state === DOKU_LEXER_SPECIAL) {
                    $this->addLinkContentInCallStack($callStack, $label);
                    $this->closeLinkInCallStack($callStack);
                }

                /**
                 * Return the data to add the snippet style in rendering
                 */
                $returnArray[PluginUtility::ATTRIBUTES] = $shareAttributes->toCallStackArray();
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
                case DOKU_LEXER_SPECIAL:
                case DOKU_LEXER_ENTER:

                    /**
                     * Any error
                     */
                    $errorMessage = $data[PluginUtility::EXIT_MESSAGE];
                    if (!empty($errorMessage)) {
                        LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        $renderer->doc .= "<span class=\"text-warning\">{$errorMessage}</span>";
                        return false;
                    }

                    /**
                     * Add the CSS / Javascript snippet
                     * It should happen only in rendering
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $channelName = $tagAttributes->getValue(TagAttributes::TYPE_KEY);
                    try {
                        $socialChannel = SocialChannel::create($channelName);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("Unable to construct the social channel ($channelName). {$e->getMessage()}");
                        return false;
                    }
                    try {
                        $style = $socialChannel->getStyle();
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The style of the share button ($socialChannel) could not be determined. Error: {$e->getMessage()}");
                        return false;
                    }
                    $snippetId = "share-{$socialChannel->getName()}";
                    PluginUtility::getSnippetManager()->attachCssSnippetForSlot($snippetId, $style);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                default:

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

    private function addIconInCallStack(CallStack $callStack, string $iconName)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_icon::TAG,
                DOKU_LEXER_SPECIAL,
                [syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE => $iconName]
            ));
    }


}

