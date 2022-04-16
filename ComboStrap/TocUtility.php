<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use Doku_Renderer;
use DokuWiki_Admin_Plugin;
use syntax_plugin_combo_toc;

class TocUtility
{


    /**
     * The id of the container area
     */
    public const TOC_AREA_ID = "main-toc";
    const CANONICAL = syntax_plugin_combo_toc::TAG;


    public static function renderToc($toc): string
    {

        global $conf;
        $tocMinHeads = $conf['tocminheads'];
        if ($tocMinHeads === null) {
            $tocMinHeads = 0;
        }
        if (count($toc) < $tocMinHeads) {
            return "";
        }

        /**
         * Adding toc number style
         */
        try {
            $css = \action_plugin_combo_outlinenumbering::getCssOutlineNumberingRuleFor(\action_plugin_combo_outlinenumbering::TOC_NUMBERING);
            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(\action_plugin_combo_outlinenumbering::TOC_NUMBERING, $css);
        } catch (ExceptionNotEnabled $e) {
            // not enabled
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The toc numbering type was unknown", self::CANONICAL);
        }

        /**
         * Creating the html
         */
        \dokuwiki\Extension\Event::createAndTrigger('TPL_TOC_RENDER', $toc, null, false);
        global $lang;

        $previousLevel = 0;
        $rootLevel = 1;
        $ulMarkup = "";
        foreach ($toc as $tocItem) {

            $actualLevel = $tocItem["level"];
            if ($actualLevel <= $rootLevel) {
                continue;
            }

            /**
             * Closing
             */

            if ($previousLevel !== $rootLevel) {
                /**
                 * Same level
                 */
                if ($actualLevel === $previousLevel) {
                    $ulMarkup .= "</li>";
                }
                /**
                 * One level down
                 */
                if ($actualLevel < $previousLevel) {
                    $ulMarkup .= "</li></ul>";
                }
            }
            /**
             * One level up
             */
            if ($actualLevel > $previousLevel) {
                $ulMarkup .= "<ul>";
            }
            $href = $tocItem['link'];
            $label = $tocItem['title'];
            $ulMarkup .= "<li><a href=\"$href\">$label</a>";
            /**
             * Close
             */
            $previousLevel = $actualLevel;
        }
        // closing
        $ulMarkup .= str_repeat("</li></ul>", $previousLevel - $rootLevel);
        $tocHeaderLang = $lang['toc'];
        $tocAreaId = self::TOC_AREA_ID;
        return <<<EOF
<nav id="$tocAreaId">
<p id="toc-header">$tocHeaderLang</p>
$ulMarkup
</nav>
EOF;


    }


    /**
     * @param Doku_Renderer $renderer
     * @return bool if the toc need to be shown
     *
     * From {@link Doku_Renderer::notoc()}
     * $this->info['toc'] = false;
     * when
     * ~~NOTOC~~
     */
    public
    static function showToc(Doku_Renderer $renderer): bool
    {

        global $ACT;


        /**
         * Search page, no toc
         */
        if ($ACT == 'search') {

            return false;

        }


        /**
         * If this is another template such as Dokuwiki, we get two TOC.
         */
        if (!Site::isStrapTemplate()) {
            return false;
        }

        /**
         * On the admin page
         */
        if ($ACT == 'admin') {

            global $INPUT;
            $plugin = null;
            $class = $INPUT->str('page');
            if (!empty($class)) {

                $pluginList = plugin_list('admin');

                if (in_array($class, $pluginList)) {
                    // attempt to load the plugin
                    /** @var $plugin DokuWiki_Admin_Plugin */
                    $plugin = plugin_load('admin', $class);
                }

                if ($plugin !== null) {
                    global $TOC;
                    if (!is_array($TOC)) $TOC = $plugin->getTOC(); //if TOC wasn't requested yet
                    if (!is_array($TOC)) {
                        return false;
                    } else {
                        return true;
                    }

                }

            }

        }

        // return it if set otherwise return true
        return $renderer->info['toc'] ?? true;

    }

    /**
     * @param int $int
     */
    public
    static function setTocMinHeading(int $int)
    {
        global $conf;
        $conf['tocminheads'] = $int;
    }


}
