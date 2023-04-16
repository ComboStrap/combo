<?php
/**
 * DokuWiki Syntax Plugin Related.
 *
 */

use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\LinkMarkup;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\Tag\RelatedTag;
use ComboStrap\TagAttributes;


require_once(DOKU_INC . 'inc/parserutils.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 *
 * The index and the metadata key for backlinks is  called 'relation_references'
 * It's the key value that you need to pass in the {@link lookupKey} of the {@link \dokuwiki\Search\Indexer}
 *
 * Type of conf[index]/index:
 *   * page.idx (id of the page is the element number)
 *   * title
 *   * relation_references_w.idx - _w for words
 *   * relation_references_w.idx - _i for lines (index by lines)
 *
 * The index is a associative map by key
 *
 *
 */
class syntax_plugin_combo_related extends DokuWiki_Syntax_Plugin
{


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'block';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     */
    function getSort()
    {
        return 100;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {


        // To replace backlinks, you may add it in the configuration
        $extraPattern = $this->getConf(RelatedTag::EXTRA_PATTERN_CONF);
        if ($extraPattern != "") {
            $this->Lexer->addSpecialPattern($extraPattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());
        }

    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        // As there is only one call to connect to in order to a add a pattern,
        // there is only one state entering the function
        // but I leave it for better understanding of the process flow
        if ($state == DOKU_LEXER_SPECIAL) {
            $qualifiedMach = trim($match);
            $attributes = [];
            if ($qualifiedMach[0] === "<") {
                // not an extra pattern
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $attributes = $tagAttributes->toCallStackArray();
            }
            return array(
                PluginUtility::STATE => $state,
                PluginUtility::ATTRIBUTES => $attributes
            );
        }

        // Cache the values
        return array($state);
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

            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES])
                ->setLogicalTag(RelatedTag::TAG);
            $renderer->doc .= RelatedTag::render($tagAttributes);
            return true;
        }
        return false;
    }

    public static function getTag(): string
    {
        return RelatedTag::TAG;
    }


}
