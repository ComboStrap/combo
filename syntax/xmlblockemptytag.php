<?php


require_once(__DIR__ . '/../vendor/autoload.php');

use ComboStrap\BackgroundTag;
use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\BrandListTag;
use ComboStrap\BrandTag;
use ComboStrap\Breadcrumb;
use ComboStrap\CacheTag;
use ComboStrap\CallStack;
use ComboStrap\DateTag;
use ComboStrap\HrTag;
use ComboStrap\IconTag;
use ComboStrap\LogUtility;
use ComboStrap\PageImageTag;
use ComboStrap\PermalinkTag;
use ComboStrap\PluginUtility;
use ComboStrap\SearchTag;
use ComboStrap\ShareTag;
use ComboStrap\TagAttributes;
use ComboStrap\XmlTagProcessing;


/**
 * The empty pattern / void element
 * (inline)
 */
class syntax_plugin_combo_xmlblockemptytag extends DokuWiki_Syntax_Plugin
{

    // should be the same than the last name of the class name
    const TAG = "xmlblockemptytag";

    private static function getEmptyBlockTag(): array
    {
        return [HrTag::TAG];
    }

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
         * Empty tag may be also block (ie Navigational {@link Breadcrumb} for instance
         */
        return 'block';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        // should be before all container tag
        return 200;
    }


    function connectTo($mode)
    {

        foreach(self::getEmptyBlockTag() as $tag) {
            $pattern = PluginUtility::getEmptyTagPattern($tag);
            $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

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

