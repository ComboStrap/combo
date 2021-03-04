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
     * @param $parentTag
     * @param $attributes
     * @return string
     */
    private static function renderClosingTag($parentTag, $attributes)
    {
        $level = $attributes[self::LEVEL];
        switch ($parentTag) {
            case syntax_plugin_combo_accordion::TAG:
                // https://getbootstrap.com/docs/4.6/components/collapse/#accordion-example
                $html = "</button>" . DOKU_LF
                    . "</h$level>" . DOKU_LF
                    . "</div>" . DOKU_LF;
                $collapseAttributes = array(
                    "id" => "collapse" . $attributes["id"],
                    "class" => "collapse show",
                    "aria-labelledby" => "headingOne",
                    "data-parent" => "#accordionExample"
                );
                $html .= "<div " . PluginUtility::array2HTMLAttributes($collapseAttributes) . ">" . DOKU_LF;
                $html .= '<div class="card-body">';
                break;
            default:
                $html = "</h$level>" . DOKU_LF;
                if ($parentTag == syntax_plugin_combo_blockquote::TAG) {
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
            PluginUtility::getModeForComponent(syntax_plugin_combo_tabpanel::TAG)
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
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
                $parentTagName = self::getParent($tag);

                $html = self::renderOpeningTag($parentTagName, $attributes);


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::PAYLOAD => $html,
                    PluginUtility::CONTEXT => $parentTagName
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::escape($match),
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $parent = $tag->getParent();
                $parentTagName = "";
                /**
                 * Heading may lived outside a component
                 */
                if ($parent != null) {
                    $parentTagName = $parent->getName();
                }
                $html = self::renderClosingTag($parentTagName, $tag->getOpeningTag()->getAttributes());
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $html,
                );

            /**
             * Title regexp
             */
            case DOKU_LEXER_SPECIAL :

                $attributes = self::parseHeading($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
                $parentTag = self::getParent($tag);

                $html = self::renderOpeningTag($parentTag, $attributes);
                $title = $attributes[self::TITLE];
                $html .= PluginUtility::escape($title);
                $html .= self::renderClosingTag($parentTag, $attributes);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::PAYLOAD => $html,
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
                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    if ($parentTag == syntax_plugin_combo_blockquote::TAG) {
                        StringUtility::rtrim($renderer->doc, syntax_plugin_combo_blockquote::BLOCKQUOTE_OPEN_TAG);
                    }
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD] . DOKU_LF;
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

        switch ($parentTag) {
            case syntax_plugin_combo_accordion::TAG:
                /**
                 * The id of the target collapsable element
                 * https://getbootstrap.com/docs/4.6/components/collapse/#accordion-example
                 */
                $targetId = "collapse" . $attributes["id"];
                $buttonAttributes = array(
                    "type" => "button",
                    "class" => "btn btn-link btn-block text-left",
                    "data-toggle" => "collapse",
                    "data-target" => "#" . $targetId,
                    "aria-expanded" => "true",
                    "aria-controls" => $targetId
                );
                $html = "<div class=\"card-header\" id=\"" . $attributes["id"] . "\">" . DOKU_LF .
                    "<h" . $attributes[self::LEVEL] . " class=\"mb-0\">" . DOKU_LF .
                    "<button " . PluginUtility::array2HTMLAttributes($buttonAttributes) . " >";
                break;
            default:

                if (in_array($parentTag, [syntax_plugin_combo_blockquote::TAG, syntax_plugin_combo_card::TAG])) {
                    PluginUtility::addClass2Attributes("card-title", $attributes);
                }
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
        }
        return $html;
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

