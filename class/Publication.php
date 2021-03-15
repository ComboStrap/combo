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
 *
 * A class with all functions about publication
 * @package ComboStrap
 *
 */
class Publication
{

    /**
     * The key that contains the published date
     */
    const META_KEY_PUBLISHED = "published";

    /**
     * Late publication protection
     */
    const LATE_PUBLICATION_PROTECTION_ACRONYM = "lpp";
    const CONF_FUTURE_PUBLICATION_PROTECTION = "futurePublicationProtectionMode";
    const CONF_FUTURE_PUBLICATION_PROTECTION_ENABLE = "futurePublicationProtectionEnabled";


    /**
     * If the page
     * no authorization
     * @param $id
     * @param $user
     * @return bool
     */
    public static function isPageProtected($id, $user = '')
    {
        if (!Auth::isLoggedIn($user)) {
            $page = new Page($id);
            if ($page->getPublishedTimestamp()) {
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



}
