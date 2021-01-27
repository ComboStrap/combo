<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\HeaderUtility;
use ComboStrap\TitleUtility;
use ComboStrap\ImgUtility;
use ComboStrap\PluginUtility;
use ComboStrap\StringUtility;
use ComboStrap\Tag;

require_once(__DIR__ . '/../class/ImgUtility.php');

if (!defined('DOKU_INC')) die();


/**
 * Card image
 * Title
 */
class syntax_plugin_combo_img extends DokuWiki_Syntax_Plugin
{

    // The > in the pattern below is to be able to handle pluggin
    // that uses a pattern such as {{changes>.}} from the change plugin
    // https://github.com/cosmocode/changes/blob/master/syntax.php


    const TAG = "img";

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
        // Only inside a card
        $modes = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
        ];
        if (in_array($mode, $modes)) {
            $this->Lexer->addSpecialPattern(ImgUtility::IMAGE_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            // As this is a container, this cannot happens but yeah, now, you know
            case DOKU_LEXER_SPECIAL :
                $attributes = ImgUtility::parse($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);

                $html = "";
                $parentTag = $tag->getParent()->getName();
                if ($parentTag == syntax_plugin_combo_card::TAG) {
                    $html = ImgUtility::render($attributes, "card-img-top");
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::PAYLOAD => $html,
                    PluginUtility::PARENT_TAG => $parentTag
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

                case DOKU_LEXER_SPECIAL :

                    if ($data[PluginUtility::PARENT_TAG]===syntax_plugin_combo_card::TAG ){
                        StringUtility::rtrim($renderer->doc,syntax_plugin_combo_card::CARD_BODY);
                        $renderer->doc .= $data[PluginUtility::PAYLOAD];
                        $renderer->doc .= syntax_plugin_combo_card::CARD_BODY;
                    } else {
                        $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    }

                    break;

            }
        }
        // unsupported $mode
        return false;
    }


}

