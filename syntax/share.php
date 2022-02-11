<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\BrandButton;
use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\CacheRuntimeDependencies2;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 *
 * See also:
 * Mobile sharing: https://web.dev/web-share/
 * https://twitter.com/addyosmani/status/1490055796704497665?s=21
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

        /**
         * The empty tag pattern should be before the container pattern
         */
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

        /**
         * Container
         */
        $entryPattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($entryPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


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
                $defaultAttributes = [];
                $types = null;
                $shareAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $types)
                    ->setLogicalTag(self::TAG);

                /**
                 * The channel
                 */
                try {
                    $brandButton = syntax_plugin_combo_brand::createButtonFromAttributes($shareAttributes, BrandButton::TYPE_BUTTON_SHARE);
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "The brand creation returns an error ({$e->getMessage()}";
                    return $returnArray;
                }


                /**
                 * Cache key dependencies
                 */
                try {
                    CacheManager::getOrCreate()->addDependency(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("We were unable to add the requested page runtime dependency. Cache errors may occurs. Error: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }


                /**
                 * Standard link attribute
                 */
                $requestedPage = Page::createPageFromRequestedPage();
                try {
                    $linkAttributes = $brandButton->getLinkAttributes($requestedPage)
                        ->setLogicalTag(self::TAG);
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "The social channel creation returns an error when creating the link ({$e->getMessage()}";
                    return $returnArray;
                }

                /**
                 * Add the link
                 */
                syntax_plugin_combo_brand::addOpenLinkTagInCallStack($callStack, $linkAttributes);

                /**
                 * Icon
                 */
                try {
                    $this->addIconInCallStack($callStack, $brandButton);
                } catch (ExceptionCombo $e) {
                    $returnArray[PluginUtility::EXIT_CODE] = 1;
                    $returnArray[PluginUtility::EXIT_MESSAGE] = "Getting the icon for the social channel ($brandButton) returns an error ({$e->getMessage()}";
                    return $returnArray;
                }

                if ($state === DOKU_LEXER_SPECIAL) {
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
                     * Add the Icon / CSS / Javascript snippet
                     * It should happen only in rendering
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    try {
                        $socialChannel = syntax_plugin_combo_brand::createButtonFromAttributes($tagAttributes, BrandButton::TYPE_BUTTON_SHARE);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The brand could not be build. Error: {$e->getMessage()}");
                        return false;
                    }


                    try {
                        $style = $socialChannel->getStyle();
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The style of the share button ($socialChannel) could not be determined. Error: {$e->getMessage()}");
                        return false;
                    }
                    $snippetId = $socialChannel->getStyleScriptIdentifier();
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


    private function closeLinkInCallStack(CallStack $callStack)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_EXIT
            ));
    }

    /**
     * @throws ExceptionCombo
     */
    private function addIconInCallStack(CallStack $callStack, BrandButton $socialChannel)
    {

        if (!$socialChannel->hasIcon()) {
            return;
        }
        $iconAttributes = $socialChannel->getIconAttributes();
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_icon::TAG,
                DOKU_LEXER_SPECIAL,
                $iconAttributes
            ));
    }


}

