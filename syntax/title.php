<?php


use ComboStrap\StringUtility;
use ComboStrap\Tag;
use ComboStrap\TitleUtility;
use ComboStrap\PluginUtility;

require_once(__DIR__ . '/../class/TitleUtility.php');

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_title extends DokuWiki_Syntax_Plugin
{


    const TAG = "title";

    /**
     * @param $parentTag
     * @param $attributes
     * @return string
     */
    private static function renderClosingTag($parentTag, $attributes)
    {
        $level = $attributes[TitleUtility::LEVEL];
        $html = "</h$level>" . DOKU_LF;
        if ($parentTag == syntax_plugin_combo_blockquote::TAG) {
            $html .= syntax_plugin_combo_blockquote::BLOCKQUOTE_OPEN_TAG;
        }
        return $html;
    }

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

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {
        // Only inside this component
        $modes = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_blockquote::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_note::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_jumbotron::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_tabpanel::TAG),
        ];
        if (in_array($mode, $modes)) {
            $this->Lexer->addSpecialPattern(TitleUtility::HEADING_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(self::TAG), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern("</".self::TAG.">", PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_ENTER :

                $defaultAttributes = array(
                    "level"=>1
                );
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($tagAttributes,$defaultAttributes);
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
                $parentTag = $tag->getParent();
                $parentTagName = "";
                if ($parentTag!=null){
                    $parentTagName = $parentTag->getName();
                }
                $html = self::renderOpeningTag($parentTagName, $attributes);

                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::ATTRIBUTES=> $attributes,
                    PluginUtility::PAYLOAD=> $html,
                    PluginUtility::PARENT_TAG => $parentTagName
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=> PluginUtility::escape($match),
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $parent = $tag->getParent();
                $parentTagName = "";
                /**
                 * Title may lived outside a component
                 */
                if ($parent!=null){
                    $parentTagName = $parent->getName();
                }
                $html = self::renderClosingTag($parentTagName,$tag->getOpeningTag()->getAttributes());
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=> $html,
                );

            case DOKU_LEXER_SPECIAL :

                $attributes = TitleUtility::parseHeading($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
                $parentTag = $tag->getParent();
                if ($parentTag!=null) {
                    $parentTag = $parentTag->getName();
                }
                $html = self::renderOpeningTag($parentTag, $attributes);
                $title = $attributes[TitleUtility::TITLE];
                $html .= PluginUtility::escape($title);
                $html .= self::renderClosingTag($parentTag, $attributes);
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::ATTRIBUTES=> $attributes,
                    PluginUtility::PAYLOAD=> $html,
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
            $state= $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_SPECIAL:
                case DOKU_LEXER_ENTER:
                    if($data[PluginUtility::PARENT_TAG]== syntax_plugin_combo_blockquote::TAG){
                        StringUtility::rtrim($renderer->doc,syntax_plugin_combo_blockquote::BLOCKQUOTE_OPEN_TAG);
                    }
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD].DOKU_LF;
                    break;

            }
        }
        // unsupported $mode
        return false;
    }

    /**
     * @param $parentTag
     * @param $attributes
     * @return string
     */
    static function renderOpeningTag($parentTag, $attributes)
    {
        if (in_array($parentTag, [syntax_plugin_combo_blockquote::TAG, syntax_plugin_combo_card::TAG])) {
            PluginUtility::addClass2Attributes("card-title",$attributes);
        }
        $type =  $attributes["type"];
        if ($type != 0){
            PluginUtility::addClass2Attributes("display-".$type,$attributes);
        }
        if (isset($attributes[TitleUtility::TITLE])){
            unset($attributes[TitleUtility::TITLE]);
        }
        $level = $attributes[TitleUtility::LEVEL];
        unset($attributes[TitleUtility::LEVEL]);
        $html = '<h' . $level;
        if (sizeof($attributes)>0) {
            $html .= ' '.PluginUtility::array2HTMLAttributes($attributes);
        }
        //$html .= ' >';
        return $html. '>';
    }


}

