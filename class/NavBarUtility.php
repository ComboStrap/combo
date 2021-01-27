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


class NavBarUtility
{

    /**
     * @param $text
     * @return string - a text styled for inside a navbar
     */
    public static function text($text)
    {
        return '<span class="navbar-text active">'.$text.'</span>';
    }

    /**
     * Delete the dokuwiki class and add the bootstrap one
     * @param $html
     * @return bool|false|string
     */
    public static function switchDokuwiki2BootstrapClass($html)
    {
        $html = HtmlUtility::addAttributeValue($html,"class","nav-link");
        $html = HtmlUtility::addAttributeValue($html,"class","active"); // The color is darker
        $html = LinkUtility::deleteDokuWikiClass($html);
        return $html;
    }
}
