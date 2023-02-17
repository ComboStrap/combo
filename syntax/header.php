<?php


use ComboStrap\BlockquoteTag;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * An header may be:
 *   * A outline section header
 *   * The header of a card ...
 */
class syntax_plugin_combo_header extends DokuWiki_Syntax_Plugin
{


    const TAG = "header";

    function getType(): string
    {
        return 'container';
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
        // block because we don't want any `p` created inside by dokuwiki, otherwise the card-header is not happy
        return 'block';
    }

    function getAllowedTypes(): array
    {
        return array('substition', 'formatting', 'disabled');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $parentName = "";
                if ($parent !== false) {
                    $parentName = $parent->getTagName();
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::CONTEXT => $parentName
                );

            case DOKU_LEXER_UNMATCHED :
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match);

            case DOKU_LEXER_EXIT :
                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $openingCall->getContext()
                );


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

        if ($format === 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    $parent = $data[PluginUtility::CONTEXT];
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    switch ($parent) {
                        case BlockquoteTag::TAG:
                        case syntax_plugin_combo_card::TAG:
                            $tagAttributes->addClassName("card-header");
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag("div");
                            break;
                        default:
                            $renderer->doc .= $tagAttributes
                                ->setLogicalTag(self::TAG)
                                ->toHtmlEnterTag("header");
                            break;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT:
                    $parent = $data[PluginUtility::CONTEXT];
                    switch ($parent) {
                        case BlockquoteTag::TAG:
                        case syntax_plugin_combo_card::TAG:
                            $renderer->doc .= "</div>";
                            break;
                        default:
                            $renderer->doc .= "</header>";
                            break;
                    }
                    break;


            }
        }
        // unsupported $mode
        return false;
    }


}

