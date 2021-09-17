<?php


use ComboStrap\CacheManager;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Set the cache of the bar
 * Ie add the possibility to add a time
 * over {@link \dokuwiki\Parsing\ParserMode\Nocache}
 */
class syntax_plugin_combo_pageimage extends DokuWiki_Syntax_Plugin
{


    const TAG = "pageimage";


    const CANONICAL = self::TAG;


    function getType()
    {
        return 'protected';
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
        return array();
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addSpecialPattern(PluginUtility::getVoidElementTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_SPECIAL :

                $attributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
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

        switch ($format) {

            case 'xhtml':
                $mediaLink =  MediaLink::createMediaLinkFromAbsolutePath(":image.png");
                $renderer->doc .= $mediaLink->renderMediaTag();
                break;


        }
        // unsupported $mode
        return false;
    }


}

