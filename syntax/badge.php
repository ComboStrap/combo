<?php


// must be run within Dokuwiki
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_badge
 * Implementation of a badge
 * called an alert in <a href="https://getbootstrap.com/docs/4.0/components/badge/">bootstrap</a>
 */
class syntax_plugin_combo_badge extends DokuWiki_Syntax_Plugin
{

    const TAG = "badge";

    const CONF_DEFAULT_ATTRIBUTES_KEY = 'defaultBadgeAttributes';

    const ATTRIBUTE_TYPE = "type";
    const ATTRIBUTE_ROUNDED = "rounded";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline or inside)
     *  * 'block'  - Open paragraphs need to be closed before plugin output (box) - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     * @see https://www.dokuwiki.org/devel:syntax_plugins#ptype
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
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultConfValue = PluginUtility::parse2HTMLAttributes($this->getConf(self::CONF_DEFAULT_ATTRIBUTES_KEY));
                $originalAttributes = PluginUtility::getTagAttributes($match);
                $originalAttributes = PluginUtility::mergeAttributes($originalAttributes,$defaultConfValue);

                /**
                 * Context Rendering attributes
                 */
                $attributesToRender = $originalAttributes;
                $tag = new Tag(self::TAG,$originalAttributes,$state,$handler->calls);

                if($tag->isDescendantOf(syntax_plugin_combo_list::TAG)){
                    PluginUtility::addStyleProperty("margin-left","auto",$attributesToRender);
                }


                /**
                 * Type attributes
                 */
                $classValue = "badge";
                $type = $attributesToRender[self::ATTRIBUTE_TYPE];
                if (empty($type)) {
                    $type = "info";
                }
                if ($type != "tip") {
                    $classValue .= " alert-" . $type;
                } else {
                    if (!array_key_exists("background-color", $attributesToRender)) {
                        $attributesToRender["background-color"] = "#fff79f"; // lum - 195
                    }
                }

                PluginUtility::addClass2Attributes($classValue,$attributesToRender);

                $rounded = $attributesToRender[self::ATTRIBUTE_ROUNDED];
                if (!empty($rounded)){
                    $attributesToRender["class"] .= " badge-pill";
                    unset($attributesToRender[self::ATTRIBUTE_ROUNDED]);
                }

                $html = '<span ' . PluginUtility::array2HTMLAttributes($attributesToRender) . '>';

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $originalAttributes,
                    PluginUtility::PAYLOAD => $html);

            case DOKU_LEXER_UNMATCHED :
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=> PluginUtility::escape($match)
                );

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=> '</span>'
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
                case DOKU_LEXER_EXIT :
                case DOKU_LEXER_ENTER :

                    $renderer->doc .= $data[PluginUtility::PAYLOAD].DOKU_LF;
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

