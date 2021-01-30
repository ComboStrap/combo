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


}
