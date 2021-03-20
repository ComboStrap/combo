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

/**
 * Class PageProtection handles the protection of page against
 * public agent such as low quality page or late publication
 *
 * @package ComboStrap
 */
class PageProtection
{

    const NAME = "page-protection";

    /**
     * Conf that gets one of the two values
     */
    const CONF_PAGE_PROTECTION_MODE = "pageProtectionMode";

    /**
     * The possible values
     */
    const CONF_VALUE_ACL = "acl";
    const CONF_VALUE_HIDDEN = "hidden";


    /**
     * A javascript indicator
     * to know if the user is logged in or not
     * (ie public or not)
     */
    const JS_IS_PUBLIC_NAVIGATION_INDICATOR = "page_protection_is_public_navigation";

    /**
     * The class of the span
     * element created in place of the link
     * See {@link LinkUtility::renderOpenTag()}
     */
    const PROTECTED_LINK_CLASS = "combo-page-protection";

    /**
     * An html attribute to get the source of the protection
     */
    const HTML_DATA_ATTRIBUTES = "data-page-protection";

    /**
     * Add the HTML snippet
     * @param Doku_Renderer_xhtml $renderer
     */
    public static function addPageProtectionSnippet()
    {
        syntax_plugin_combo_tooltip::addToolTipSnippetIfNeeded();
        $protectedLinkClass = self::PROTECTED_LINK_CLASS;
        $jsIsPublicNavigationIndicator = self::JS_IS_PUBLIC_NAVIGATION_INDICATOR;
        $jsClass = self::PROTECTED_LINK_CLASS;
        $style = <<<EOF
.{$protectedLinkClass} {
    color:#a829dc
}
EOF;
        PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::NAME, $style);


        $js = <<<EOF
window.addEventListener('DOMContentLoaded', function () {

    if (JSINFO["{$jsIsPublicNavigationIndicator}"]===false){
        jQuery("span.{$jsClass}").each(function() {
                let actualClass = jQuery(this).attr("class");
                jQuery(this).replaceWith( "<a class=\""+actualClass+"\" href=\""+DOKU_BASE+jQuery(this).attr("data-wiki-id")+"\">"+jQuery(this).text()+"</a>" )
        })
    }

})
EOF;
        PluginUtility::getSnippetManager()->upsertJavascriptForBar(self::NAME, $js);


    }
}
