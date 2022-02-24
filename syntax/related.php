<?php
/**
 * DokuWiki Syntax Plugin Related.
 *
 */

use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
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


    // Conf property key
    const MAX_LINKS_CONF = 'maxLinks';
    const MAX_LINKS_CONF_DEFAULT = 10;
    // For when you come from another plugin (such as backlinks) and that you don't want to change the pattern on each page
    const EXTRA_PATTERN_CONF = 'extra_pattern';

    // This is a fake page ID that is added
    // to the related page array when the number of backlinks is bigger than the max
    // Poisoning object strategy
    const MORE_PAGE_ID = 'related_more';

    // The array key of an array of related page
    const RELATED_PAGE_ID_PROP = 'id';
    const RELATED_BACKLINKS_COUNT_PROP = 'backlinks';
    const TAG = "related";


    /**
     * @param Page $page
     * @param int|null $max
     * @return string
     */
    public static function getHtmlRelated(Page $page, ?int $max = null): string
    {
        global $lang;

        $tagAttributes = TagAttributes::createEmpty(self::getTag());
        $tagAttributes->addClassName("d-print-none");
        $html = $tagAttributes->toHtmlEnterTag("div");

        $relatedPages = self::getRelatedPagesOrderedByBacklinkCount($page, $max);
        if (empty($relatedPages)) {

            $html .= "<strong>Plugin " . PluginUtility::PLUGIN_BASE_NAME . " - Component " . self::getTag() . ": " . $lang['nothingfound'] . "</strong>" . DOKU_LF;

        } else {

            // Dokuwiki debug

            $html .= '<ul>' . DOKU_LF;

            foreach ($relatedPages as $backlink) {
                $backlinkId = $backlink[self::RELATED_PAGE_ID_PROP];
                $html .= '<li>';
                if ($backlinkId != self::MORE_PAGE_ID) {
                    $linkUtility = MarkupRef::createFromPageId($backlinkId);
                    try {
                        $html .= $linkUtility->toAttributes(self::TAG)->toHtmlEnterTag("a");
                        $html .= $linkUtility->getLabel();
                        $html .= "</a>";
                    } catch (ExceptionCombo $e) {
                        $html = "Error while trying to create the link for the page ($backlinkId). Error: {$e->getMessage()}";
                        LogUtility::msg($html);
                    }

                } else {
                    $html .=
                        tpl_link(
                            wl($page->getDokuwikiId()) . '?do=backlink',
                            "More ...",
                            'class="" rel="nofollow" title="More..."',
                            true
                        );
                }
                $html .= '</li>' . DOKU_LF;
            }

            $html .= '</ul>' . DOKU_LF;

        }

        return $html . '</div>' . DOKU_LF;
    }


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
        // The basic
        $this->Lexer->addSpecialPattern(PluginUtility::getVoidElementTagPattern(self::getTag()), $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

        // To replace backlinks, you may add it in the configuration
        $extraPattern = $this->getConf(self::EXTRA_PATTERN_CONF);
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

        switch ($state) {

            // As there is only one call to connect to in order to a add a pattern,
            // there is only one state entering the function
            // but I leave it for better understanding of the process flow
            case DOKU_LEXER_SPECIAL :

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
    function render($format, Doku_Renderer $renderer, $data)
    {


        if ($format == 'xhtml') {

            $page = Page::createPageFromRequestedPage();
            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
            $max = $tagAttributes->getValue(self::MAX_LINKS_CONF);
            if ($max === NULL) {
                $max = PluginUtility::getConfValue(self::MAX_LINKS_CONF, self::MAX_LINKS_CONF_DEFAULT);
            }
            $renderer->doc .= self::getHtmlRelated($page, $max);
            return true;
        }
        return false;
    }

    /**
     * @param Page $page
     * @param int|null $max
     * @return array
     */
    public static function getRelatedPagesOrderedByBacklinkCount(Page $page, ?int $max = null): array
    {

        // Call the dokuwiki backlinks function
        // @require_once(DOKU_INC . 'inc/fulltext.php');
        // Backlinks called the indexer, for more info
        // See: https://www.dokuwiki.org/devel:metadata#metadata_index
        $backlinks = ft_backlinks($page->getDokuwikiId(), $ignore_perms = false);

        $related = array();
        foreach ($backlinks as $backlink) {
            $page = array();
            $page[self::RELATED_PAGE_ID_PROP] = $backlink;
            $page[self::RELATED_BACKLINKS_COUNT_PROP] = sizeof(ft_backlinks($backlink, $ignore_perms = false));
            $related[] = $page;
        }

        usort($related, function ($a, $b) {
            return $b[self::RELATED_BACKLINKS_COUNT_PROP] - $a[self::RELATED_BACKLINKS_COUNT_PROP];
        });

        if ($max !== null) {
            if (sizeof($related) > $max) {
                $related = array_slice($related, 0, $max);
                $page = array();
                $page[self::RELATED_PAGE_ID_PROP] = self::MORE_PAGE_ID;
                $related[] = $page;
            }
        }

        return $related;

    }

    public static function getTag(): string
    {
        return self::TAG;
    }


}
