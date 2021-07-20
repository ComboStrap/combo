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
     * The possible values
     */
    const CONF_VALUE_ACL = "acl";
    const CONF_VALUE_HIDDEN = "hidden";
    const CONF_VALUE_NO_ROBOT = "no-robot";


    /**
     * Add the HTML snippet
     * @deprecated
     */
    public static function addPageProtectionSnippet()
    {
        syntax_plugin_combo_tooltip::addToolTipSnippetIfNeeded();
        $protectedLinkClass = self::PROTECTED_LINK_CLASS;
        $jsIsPublicNavigationIndicator = Identity::JS_NAVIGATION_INDICATOR;
        $jsClass = self::PROTECTED_LINK_CLASS;
        $style = <<<EOF
.{$protectedLinkClass} {
    color:#a829dc
}
EOF;
        PluginUtility::getSnippetManager()->attachCssSnippetForBar(self::NAME, $style);


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
        PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar(self::NAME, $js);


    }
}
