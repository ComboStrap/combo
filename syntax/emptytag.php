<?php



require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

// must be run within Dokuwiki
use ComboStrap\HrTag;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * The empty pattern / void element
 */
class syntax_plugin_combo_emptytag extends DokuWiki_Syntax_Plugin
{


    function getType(): string
    {
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

        $pattern = PluginUtility::getEmptyTagPatternGeneral();
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $tag = TagAttributes::createFromTagMatch($match);
        return array(
            PluginUtility::TAG => $tag->getLogicalTag(),
            PluginUtility::ATTRIBUTES => $tag->toCallStackArray(),
            PluginUtility::STATE => $state
        );

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

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            $tag = $data[PluginUtility::TAG];
            $attributes = $data[PluginUtility::ATTRIBUTES];
            $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
            switch ($tag) {
                case HrTag::TAG:
                    $renderer->doc .= HrTag::render($tagAttributes);
                    break;
                default:
                    LogUtility::errorIfDevOrTest("The empty tag (" . $tag . ") was not process.");
            }


        }
        // unsupported $mode
        return false;
    }


}

