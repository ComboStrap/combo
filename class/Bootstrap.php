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

class Bootstrap
{

    const BootStrapDefaultMajorVersion = "4";
    const BootStrapFiveMajorVersion = "5";

    public static function getDataNamespace()
    {
        $dataToggleNamespace = "";
        if (self::getBootStrapMajorVersion() == self::BootStrapFiveMajorVersion) {
            $dataToggleNamespace = "-bs";
        }
        return $dataToggleNamespace;
    }

    public static function getBootStrapMajorVersion()
    {
        if (Site::isStrapTemplate()) {
            require_once(__DIR__ . '/../../../tpl/strap/class/TplUtility.php');
            $bootstrapVersion = tpl_getConf(TplUtility::CONF_BOOTSTRAP_VERSION, self::BootStrapDefaultMajorVersion);
            return $bootstrapVersion[0];
        } else {
            return self::BootStrapDefaultMajorVersion;
        }

    }
}
