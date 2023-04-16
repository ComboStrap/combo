<?php

namespace ComboStrap\Tag;

use ComboStrap\ExceptionCompile;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\SiteConfig;
use ComboStrap\TagAttributes;
use syntax_plugin_combo_iterator;
use syntax_plugin_combo_related;

class RelatedTag
{


    public const TAG = "related";
    /**
     * For when you come from another plugin (such as backlinks) and that you don't want to change the pattern on each page
     */
    public const MORE_PAGE_ID = 'related_more';
    public const MAX_LINKS_CONF = 'maxLinks';
    public const EXTRA_PATTERN_CONF = 'extra_pattern';
    public const MAX_LINKS_CONF_DEFAULT = 10;
    /**
     * The array key of an array of related page
     */
    public const RELATED_BACKLINKS_COUNT_PROP = 'backlinks';
    /**
     * This is a fake page ID that is added
     * to the related page array when the number of backlinks is bigger than the max
     */
    public const RELATED_PAGE_ID_PROP = 'id';

    /**
     * @param TagAttributes $tagAttributes
     * @param MarkupPath $page
     * @param int|null $max
     * @return string
     */
    public static function render(TagAttributes $tagAttributes): string
    {
        $path = syntax_plugin_combo_iterator::getContextPathForComponentThatMayBeInFragment($tagAttributes);
        $contextPage = MarkupPath::createPageFromPathObject($path);
        return self::renderForPage($contextPage, $tagAttributes);
    }

    public static function renderForPage(MarkupPath $page, TagAttributes $tagAttributes = null): string
    {
        global $lang;

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty()
                ->setLogicalTag(self::TAG);
        }

        $max = $tagAttributes->getValue(RelatedTag::MAX_LINKS_CONF);
        if ($max === NULL) {
            $max = SiteConfig::getConfValue(RelatedTag::MAX_LINKS_CONF, RelatedTag::MAX_LINKS_CONF_DEFAULT);
        }

        $tagAttributes->addClassName("d-print-none");
        $html = $tagAttributes->toHtmlEnterTag("div");

        $relatedPages = self::getRelatedPagesOrderedByBacklinkCount($page, $max);
        if (empty($relatedPages)) {

            $html .= "<strong>Plugin " . PluginUtility::PLUGIN_BASE_NAME . " - Component " . syntax_plugin_combo_related::getTag() . ": " . $lang['nothingfound'] . "</strong>";

        } else {

            // Dokuwiki debug

            $html .= '<ul>';

            foreach ($relatedPages as $backlink) {
                $backlinkId = $backlink[self::RELATED_PAGE_ID_PROP];
                $html .= '<li>';
                if ($backlinkId != self::MORE_PAGE_ID) {
                    $linkUtility = LinkMarkup::createFromPageIdOrPath($backlinkId);
                    try {
                        $html .= $linkUtility->toAttributes(self::TAG)->toHtmlEnterTag("a");
                        $html .= $linkUtility->getDefaultLabel();
                        $html .= "</a>";
                    } catch (ExceptionCompile $e) {
                        $html = "Error while trying to create the link for the page ($backlinkId). Error: {$e->getMessage()}";
                        LogUtility::msg($html);
                    }

                } else {
                    $html .=
                        tpl_link(
                            wl($page->getWikiId()) . '?do=backlink',
                            "More ...",
                            'class="" rel="nofollow" title="More..."',
                            true
                        );
                }
                $html .= '</li>';
            }

            $html .= '</ul>';

        }

        return $html . '</div>';
    }

    /**
     * @param MarkupPath $page
     * @param int|null $max
     * @return array
     */
    public static function getRelatedPagesOrderedByBacklinkCount(MarkupPath $page, ?int $max = null): array
    {

        // Call the dokuwiki backlinks function
        // @require_once(DOKU_INC . 'inc/fulltext.php');
        // Backlinks called the indexer, for more info
        // See: https://www.dokuwiki.org/devel:metadata#metadata_index
        $backlinks = ft_backlinks($page->getWikiId(), $ignore_perms = false);

        $related = array();
        foreach ($backlinks as $backlink) {
            $page = array();
            $page[RelatedTag::RELATED_PAGE_ID_PROP] = $backlink;
            $page[RelatedTag::RELATED_BACKLINKS_COUNT_PROP] = sizeof(ft_backlinks($backlink, $ignore_perms = false));
            $related[] = $page;
        }

        usort($related, function ($a, $b) {
            return $b[RelatedTag::RELATED_BACKLINKS_COUNT_PROP] - $a[RelatedTag::RELATED_BACKLINKS_COUNT_PROP];
        });

        if ($max !== null) {
            if (sizeof($related) > $max) {
                $related = array_slice($related, 0, $max);
                $page = array();
                $page[RelatedTag::RELATED_PAGE_ID_PROP] = RelatedTag::MORE_PAGE_ID;
                $related[] = $page;
            }
        }

        return $related;

    }
}
