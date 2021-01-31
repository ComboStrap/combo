<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use Doku_Renderer_xhtml;
use syntax_plugin_combo_tooltip;

require_once(__DIR__ . '/../class/Auth.php');

/**
 * Class LowQualityPage
 * @package ComboStrap
 *
 */
class LowQualityPage
{

    const ACRONYM = "LQPP"; // low quality page protection
    const CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE = "lowQualityPageProtectionEnable";
    const CONF_LOW_QUALITY_PAGE_PROTECTION_MODE = "lowQualityPageProtectionMode";
    const ACL = "acl";
    const HIDDEN = "hidden";

    /**
     * The class of the span
     * element created in place of the link
     * See {@link LowQualityPage::renderLowQualityLink()}
     */
    const LOW_QUALITY_LINK_CLASS = "lqpp";

    /**
     * A javascript indicator
     * to know if the user is logged in or not
     * (ie public or not)
     */
    const JS_INDICATOR = "lqpp_public";


    /**
     * If low page rank and not logged in,
     * no authorization
     * @param $id
     * @param $user
     * @return bool
     */
    public static function isPageToExclude($id, $user = '')
    {
        if (!Auth::isLoggedIn($user)) {
            $page = new Page($id);
            if ($page->isLowQualityPage()) {
                /**
                 * Low quality page should not
                 * be public and readable for the search engine
                 */
                return true;
            } else {
                /**
                 * Do not cache high quality page
                 */
                return false;
            }
        } else {
            /**
             * Logged in, no exclusion
             */
            return false;
        }

    }

    /**
     * Add the HTML snippet
     * @param Doku_Renderer_xhtml $renderer
     */
    static function addLowQualityPageHtmlSnippet(Doku_Renderer_xhtml $renderer)
    {
        syntax_plugin_combo_tooltip::addToolTipSnippetIfNeeded($renderer);
        $lowQualityPageClass = self::LOW_QUALITY_LINK_CLASS;
        $jsIndicator = self::JS_INDICATOR;
        $jsClass = self::LOW_QUALITY_LINK_CLASS;
        if (!PluginUtility::htmlSnippetAlreadyAdded($renderer->info, "lqpp")) {
            $renderer->doc .= <<<EOF
<style>
.{$lowQualityPageClass} {
    color:#a829dc
}
</style>
<script type="text/javascript">
window.addEventListener('DOMContentLoaded', function () {

    jQuery("span.{$jsClass}").each(function() {
        if (JSINFO["{$jsIndicator}"]==false){
            jQuery(this).replaceWith( "<a class=\"{$lowQualityPageClass}\" href=\""+DOKU_BASE+jQuery(this).attr("data-wiki-id").replace(":","/")+"\">"+jQuery(this).text()+"</a>" )
        }
    })

})
</script>
EOF;
        }

    }

    /**
     * Render a link as a span element
     * This is used when a public page links to a low quality page
     * to render a span element
     * The span element is then modified as link by javascript if the user is not anonymous
     * @param LinkUtility $link
     * @return string the html
     */
    public static function renderLowQualityLink($link)
    {

        $lowQualityPageClass = self::LOW_QUALITY_LINK_CLASS;
        $qualifiedLink = $link->getAbsoluteId();

        $name = $link->getName();
        if (empty($name)) {
            $name = $link->getRef();
            if (useHeading('content')) {
                $page = $link->getInternalPage();
                $h1 = $page->getH1();
                if (!empty($h1)){
                    $name = $h1;
                } else {
                    /**
                     * In dokuwiki by default, title = h1
                     * If there is no h1, we take title
                     * for backward compatibility
                     */
                    $title = $page->getTitle();
                    if (!empty($title)) {
                        $name = $title;
                    }
                }
            }
        }
        return "<span class=\"{$lowQualityPageClass}\" data-wiki-id=\"{$qualifiedLink}\" data-toggle=\"tooltip\" title=\"To follow this link ({$qualifiedLink}), you need to log in (" . LowQualityPage::ACRONYM . ")\">{$name}</span>";
    }


}
