<?php


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

// must be run within Dokuwiki
use ComboStrap\HrTag;
use ComboStrap\IconTag;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\SearchTag;
use ComboStrap\TagAttributes;


/**
 * The xml tag (non-empty) pattern
 */
class syntax_plugin_combo_tag extends DokuWiki_Syntax_Plugin
{


    /**
     * The Syntax Type determines which syntax may be nested
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     * See https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType(): string
    {
        /**
         * Choice between container, formatting and substition
         *
         * Icon had 'substition' and can still have other mpde inside (ie tooltip)
         * We choose substition then
         */
        return 'substition';
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
    function getPType(): string
    {
        return 'normal';
    }

    /**
     * @return array the kind of plugin that are allowed inside (ie an array of
     * <a href="https://www.dokuwiki.org/devel:syntax_plugins#syntax_types">mode type</a>
     * ie
     * * array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * * array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    public function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {


    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        return [];
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

        return false;
    }


}

