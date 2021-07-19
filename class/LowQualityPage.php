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


use dokuwiki\Action\Plugin;

require_once(__DIR__ . '/../class/Identity.php');

/**
 * Class LowQualityPage
 * @package ComboStrap
 *
 */
class LowQualityPage
{

    const LOW_QUALITY_PROTECTION_ACRONYM = "LQPP"; // low quality page protection
    const CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE = "lowQualityPageProtectionEnable";

    /**
     *
     */
    const CONF_LOW_QUALITY_PAGE_PROTECTION_MODE = "lowQualityPageProtectionMode";

    const CONF_LOW_QUALITY_PAGE_LINK_TYPE = "lowQualityPageLinkType";
    const LOW_QUALITY_PAGE_LINK_NORMAL = "normal";
    const LOW_QUALITY_PAGE_LINK_WARNING = "warning";
    const LOW_QUALITY_PAGE_LINK_LOGIN = "login";
    const CLASS_NAME = "low-quality-page";

    public static function getLowQualityProtectionMode()
    {
        if (PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE, true)) {
            return PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE, PageProtection::CONF_VALUE_ACL);
        } else {
            return false;
        }
    }

    public static function isProtected(Page $linkedPage)
    {
        if (!Identity::isLoggedIn()) {
            if (PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE, true)) {
                if ($linkedPage->isLowQualityPage()) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getLowQualityLinkType()
    {

        return PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_LINK_TYPE, LowQualityPage::LOW_QUALITY_PAGE_LINK_NORMAL);

    }

}
