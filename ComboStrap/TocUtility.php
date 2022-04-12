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
     * The class added to the container
     */
    public const TOC_ID_CLASS = "main-toc";
    const CANONICAL = syntax_plugin_combo_toc::TAG;

    public static function renderToc($toc, $renderer): string
    {
        global $TOC;
        if ($toc !== null) {
            $TOC = $toc;
            /**
             * The {@link tpl_toc()} uses the global variable
             */
        } else {

            $toc = $TOC;
            // If the TOC is null (The toc may be initialized by a plugin)
            if (!is_array($toc) or count($toc) == 0) {
                $toc = $renderer->toc;
            }

            if ($toc === null) {
                return LogUtility::wrapInRedForHtml("No Toc found");
            }

        }

        global $conf;
        if (count($toc) > $conf['tocminheads']) {
            \dokuwiki\Extension\Event::createAndTrigger('TPL_TOC_RENDER', $toc, null, false);
            global $lang;
            $tocList = html_buildlist($toc, 'toc', 'html_list_toc', 'html_li_default', true);
            $tocHeaderLang = $lang['toc'];
            $tocAreaId = self::TOC_ID_CLASS;
            return <<<EOF
<div id="$tocAreaId">
<p id="toc-header">$tocHeaderLang</p>
<nav id="toc">
$tocList
<nav>
</div>
EOF;

        } else {
            return "";
        }

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
    public static function showToc(Doku_Renderer $renderer): bool
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
    public static function setTocMinHeading(int $int)
    {
        global $conf;
        $conf['tocminheads'] = $int;
    }

}
