<?php


use ComboStrap\StringUtility;
use ComboStrap\Tag;
use ComboStrap\PluginUtility;


if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_title
 * Title in container component
 */
class syntax_plugin_combo_title extends DokuWiki_Syntax_Plugin
{


    const TAG = "title";

    /**
     * Header pattern that we expect ie  ==== Hello =====
     * Found in {@link \dokuwiki\Parsing\ParserMode\Header}
     */
    const HEADING_PATTERN = '[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)';

    const TITLE = 'title';
    const LEVEL = 'level';


    private static function getParent(Tag $tag)
    {
        $parentTag = $tag->getParent();
        $parentTagName = "";
        if ($parentTag != null) {
            $parentTagName = $parentTag->getName();
        }
        return $parentTagName;
    }

    /**
     * @param $context
     * @param $attributes
     * @return string
     */
    private static function renderClosingTag($context, $attributes)
    {
        $level = $attributes[self::LEVEL];
        switch ($context) {
            default:
                $html = "</h$level>" . DOKU_LF;
                if ($context == syntax_plugin_combo_blockquote::TAG) {
                    $html .= syntax_plugin_combo_blockquote::BLOCKQUOTE_OPEN_TAG;
                }
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
        /**
         * Title regexp
         */
        $modes = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_blockquote::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_note::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_jumbotron::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_panel::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_panel::OLD_TAB_PANEL_TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_slice::TAG),
        ];
        if (in_array($mode, $modes)) {
            $this->Lexer->addSpecialPattern(self::HEADING_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

        /**
         * Title tag
         */
        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(self::TAG), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern("</" . self::TAG . ">", PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_ENTER :

                $defaultAttributes = array(
                    "level" => 1
                );
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($tagAttributes, $defaultAttributes);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parentTagName = self::getParent($tag);


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTagName
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::escape($match),
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler);
                $parent = $tag->getParent();
                $parentTagName = "";
                /**
                 * Heading may lived outside a component
                 */
                if ($parent != null) {
                    $parentTagName = $parent->getName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $parentTagName,
                    PluginUtility::ATTRIBUTES => $tag->getOpeningTag()->getAttributes()

                );

            /**
             * Title regexp
             */
            case DOKU_LEXER_SPECIAL :

                $attributes = self::parseHeading($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parentTag = self::getParent($tag);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTag
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

                case DOKU_LEXER_SPECIAL:
                    /**
                     * The short title ie ( === title === )
                     */
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $context = $data[PluginUtility::CONTEXT];
                    $title = $attributes[self::TITLE];
                    $renderer->doc .=  self::renderOpeningTag($context, $attributes,$renderer);
                    $renderer->doc .= PluginUtility::escape($title);
                    $renderer->doc .= self::renderClosingTag($context, $attributes);
                    break;
                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $renderer->doc .= self::renderOpeningTag($parentTag, $attributes, $renderer);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $context = $data[PluginUtility::CONTEXT];
                    $renderer->doc .= self::renderClosingTag($context, $attributes);
                    break;

            }
        }
        // unsupported $mode
        return false;
    }

    /**
     * @param $context
     * @param $attributes
     * @param Doku_Renderer_xhtml $renderer
     */
    static function renderOpeningTag($context, $attributes, $renderer)
    {

        /**
         * TODO: This switch should be handled in the instruction (ie handle function)
         */
        switch ($context) {

            case syntax_plugin_combo_blockquote::TAG:
                StringUtility::rtrim($renderer->doc, syntax_plugin_combo_blockquote::BLOCKQUOTE_OPEN_TAG);
                PluginUtility::addClass2Attributes("card-title", $attributes);
                break;
            case syntax_plugin_combo_card::TAG:
                PluginUtility::addClass2Attributes("card-title", $attributes);

        }

        /**
         * Printing
         */
        $type = $attributes["type"];
        if ($type != 0) {
            PluginUtility::addClass2Attributes("display-" . $type, $attributes);
        }
        if (isset($attributes[self::TITLE])) {
            unset($attributes[self::TITLE]);
        }
        $level = $attributes[self::LEVEL];
        unset($attributes[self::LEVEL]);
        $html = '<h' . $level;
        if (sizeof($attributes) > 0) {
            $html .= ' ' . PluginUtility::array2HTMLAttributes($attributes);
        }
        $html .= ' >';
        $renderer->doc .= $html;
    }

    public
    static function parseHeading($match)
    {
        $title = trim($match);
        $level = 7 - strspn($title, '=');
        if ($level < 1) $level = 1;
        $title = trim($title, '=');
        $title = trim($title);
        $parameters[self::TITLE] = $title;
        $parameters[self::LEVEL] = $level;
        return $parameters;
    }

}

