<?php


require_once(__DIR__ . '/../vendor/autoload.php');

use ComboStrap\BreadcrumbTag;
use ComboStrap\PluginUtility;
use ComboStrap\XmlTagProcessing;


/**
 * The empty pattern / void element
 * (inline)
 */
class syntax_plugin_combo_xmlinlineemptytag extends DokuWiki_Syntax_Plugin
{

    // should be the same than the last name of the class name
    const TAG = "xmlemptytag";

    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (dokuwiki does not create p inside)
     *  * 'stack' - Block (dokuwiki creates p inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        /**
         * Empty tag may be also block (ie Navigational {@link BreadcrumbTag} for instance
         */
        return 'normal';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        // should be before all container tag
        return 998;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getEmptyTagPatternGeneral();
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        return XmlTagProcessing::handleStaticEmptyTag($match, $state, $pos, $handler, $this);

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        return XmlTagProcessing::renderStaticEmptyTag($format, $renderer, $data, $this);

    }


}

