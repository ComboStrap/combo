<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\BrandButton;
use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\Icon;
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

                $defaultAttributes = [];
                $types = null;
                $shareAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $types)
                    ->setLogicalTag(self::TAG);


                /**
                 * Return the data to add the snippet style in rendering
                 */
                $returnArray[PluginUtility::ATTRIBUTES] = $shareAttributes->toCallStackArray();
                return $returnArray;


            case DOKU_LEXER_EXIT:

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

                    $shareAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                    /**
                     * The channel
                     */
                    try {
                        $brandButton = syntax_plugin_combo_brand::createButtonFromAttributes($shareAttributes, BrandButton::TYPE_BUTTON_SHARE);
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("The brand creation returns an error ({$e->getMessage()}");
                        return false;
                    }


                    /**
                     * Standard link attribute
                     * and Runtime Cache key dependencies
                     */
                    CacheManager::getOrCreate()->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                    try {
                        $requestedPage = Page::createPageFromRequestedPage();
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("The requested page could not be determined. Error: ({$e->getMessage()}");
                        return false;
                    }
                    try {
                        $linkAttributes = $brandButton->getLinkAttributes($requestedPage)
                            ->setType($shareAttributes->getType())
                            ->setLogicalTag(self::TAG);
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("The social channel creation returns an error when creating the link ({$e->getMessage()}");
                        return false;
                    }

                    /**
                     * Add the link
                     */
                    $renderer->doc .= $linkAttributes->toHtmlEnterTag("a");

                    /**
                     * Icon
                     */
                    if ($brandButton->hasIcon()) {
                        try {
                            $iconAttributes = $brandButton->getIconAttributes();
                            $name = $iconAttributes[\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE];
                            unset($iconAttributes[\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE]);
                            $iconAttributes = TagAttributes::createFromCallStackArray($iconAttributes);
                            $renderer->doc .= Icon::create($name, $iconAttributes)
                                ->render();
                        } catch (ExceptionCombo $e) {
                            $renderer->doc .= LogUtility::wrapInRedForHtml("Getting the icon for the social channel ($brandButton) returns an error ({$e->getMessage()}");
                            // don't return because the anchor link is open
                        }
                    }


                    if ($state === DOKU_LEXER_SPECIAL) {
                        $renderer->doc .= "</a>";
                    }


                    try {
                        $style = $brandButton->getStyle();
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("The style of the share button ($brandButton) could not be determined. Error: {$e->getMessage()}");
                        return false;
                    }
                    $snippetId = $brandButton->getStyleScriptIdentifier();
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($snippetId, $style);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</a>";
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


}

