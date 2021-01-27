<?php


// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_inote
 * Implementation of a inline note
 * called an alert in <a href="https://getbootstrap.com/docs/4.0/components/badge/">bootstrap</a>
 *
 * Quickly created with a copy of a badge
 */
class syntax_plugin_combo_inote extends DokuWiki_Syntax_Plugin
{

    const TAG = "inote";

    const CONF_DEFAULT_ATTRIBUTES_KEY = 'defaultInoteAttributes';

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
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

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

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $attributes = PluginUtility::getTagAttributes($match);
                return array($state, $attributes);

            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
                return array($state, '');


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
            list($state, $payload) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :

                    $defaultConfValue = $this->getConf(self::CONF_DEFAULT_ATTRIBUTES_KEY);
                    $defaultAttributes = PluginUtility::parse2HTMLAttributes($defaultConfValue);
                    $attributes = PluginUtility::mergeAttributes($payload,$defaultAttributes);

                    $classValue = "badge";
                    $type = $attributes[self::ATTRIBUTE_TYPE];
                    if (empty($type)) {
                        $type = "info";
                    }
                    if (array_key_exists("type", $attributes)) {
                        $type = $attributes["type"];
                        // Switch for the color
                        switch ($type) {
                            case "important":
                                $type = "warning";
                                break;
                            case "warning":
                                $type = "danger";
                                break;
                        }
                    }
                    if ($type != "tip") {
                        $classValue .= " badge-" . $type;
                    } else {
                        if (!array_key_exists("background-color", $attributes)) {
                            $attributes["background-color"] = "#fff79f"; // lum - 195
                        }
                    }

                    if (array_key_exists("class", $attributes)) {
                        $attributes["class"] .= " {$classValue}";
                    } else {
                        $attributes["class"] = "{$classValue}";
                    }

                    $rounded = $attributes[self::ATTRIBUTE_ROUNDED];
                    if (!empty($rounded)){
                        $attributes["class"] .= " badge-pill";
                        unset($attributes[self::ATTRIBUTE_ROUNDED]);
                    }

                    $renderer->doc .= '<span ' . PluginUtility::array2HTMLAttributes($attributes) . '>';
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $renderer->_xmlEntities($payload);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</span>';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

