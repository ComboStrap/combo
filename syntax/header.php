<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\HeaderUtility;
use ComboStrap\TitleUtility;
use ComboStrap\PluginUtility;
use ComboStrap\StringUtility;
use ComboStrap\Tag;

require_once(__DIR__ . '/../class/HeaderUtility.php');

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_header extends DokuWiki_Syntax_Plugin
{


    function getType()
    {
        return 'formatting';
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
    function getPType()
    {
        return 'normal';
    }

    function getAllowedTypes()
    {
        return array('substition', 'formatting', 'disabled');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(HeaderUtility::HEADER), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . HeaderUtility::HEADER . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $htmlAttributes = $tagAttributes;
                $tag = new Tag(HeaderUtility::HEADER, $tagAttributes, $state, $handler->calls);
                $parent = $tag->getParent();
                $parentName = "";
                $html = "";
                if ($parent != null) {
                    $parentName = $parent->getName();
                    switch ($parentName) {
                        case syntax_plugin_combo_blockquote::TAG:
                        case syntax_plugin_combo_card::TAG:
                            PluginUtility::addClass2Attributes("card-header", $htmlAttributes);
                            $inlineAttributes = PluginUtility::array2HTMLAttributes($htmlAttributes);
                            $html = "<div {$inlineAttributes}>" . DOKU_LF;
                            break;
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::PAYLOAD => $html,
                    PluginUtility::PARENT_TAG => $parentName
                );

            case DOKU_LEXER_UNMATCHED :
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match);

            case DOKU_LEXER_EXIT :
                $html = "</div>";
                $tag = new Tag(HeaderUtility::HEADER, array(), $state, $handler->calls);
                $parent = $tag->getParent();
                if ($parent != null) {
                    switch ($parent->getName()) {
                        case syntax_plugin_combo_blockquote::TAG:
                            $html .= syntax_plugin_combo_blockquote::CARD_BODY_BLOCKQUOTE_OPEN_TAG;
                            break;
                        case syntax_plugin_combo_card::TAG:
                            $html .= syntax_plugin_combo_card::CARD_BODY;
                            break;
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $html
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
    function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    $parent = $data[PluginUtility::PARENT_TAG];
                    switch ($parent) {
                        case syntax_plugin_combo_blockquote::TAG:
                            StringUtility::rtrim($renderer->doc, syntax_plugin_combo_blockquote::CARD_BODY_BLOCKQUOTE_OPEN_TAG);
                            break;
                        case syntax_plugin_combo_card::TAG:
                            StringUtility::rtrim($renderer->doc, syntax_plugin_combo_card::CARD_BODY);
                            break;
                    }
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::escape($data[PluginUtility::PAYLOAD]);
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;


            }
        }
        // unsupported $mode
        return false;
    }


}

