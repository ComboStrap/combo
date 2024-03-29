<?php


use ComboStrap\PluginUtility;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Class syntax_plugin_combo_analytics
 * This class was just created to add the syntax analytics
 * to the metadata.
 */
class syntax_plugin_combo_analytics extends DokuWiki_Syntax_Plugin
{

    const TAG = "analytics";
    public const CONF_SYNTAX_ANALYTICS_ENABLE = "syntaxAnalyticsEnable";

    /**
     * Syntax Type.
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (p are not created inside)
     *  * 'stack' - Block (p can be created inside)
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
     * array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    function getAllowedTypes()
    {
        return array();
    }

    function getSort()
    {
        return 201;
    }


    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {
        /**
         * The instruction `calls` are not created via syntax
         * but dynamically in the Outline {@link \ComboStrap\Outline::buildOutline()}
         */

    }

    function postConnect()
    {

        /**
         * The instruction `calls` are not created via syntax
         * but dynamically in the Outline {@link \ComboStrap\Outline::buildOutline()}
         */

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        /**
         * The instruction `calls` are not created via syntax
         * but dynamically via {@link action_plugin_combo_syntaxanalyticsTest}
         */

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

        if ($format == renderer_plugin_combo_analytics::RENDERER_FORMAT) {

            /** @var renderer_plugin_combo_analytics $renderer */
            $state = $data[PluginUtility::STATE];
            if ($state == DOKU_LEXER_SPECIAL) {
                $attributes = $data[PluginUtility::ATTRIBUTES];
                $renderer->stats[renderer_plugin_combo_analytics::SYNTAX_COUNT] = $attributes;
                return true;
            }

        }

        return false;
    }


}

