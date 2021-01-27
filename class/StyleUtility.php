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

require_once(__DIR__ . '/StringUtility.php');

class StyleUtility
{

    public static function getRule(array $styles, $selector)
    {
        $rule = $selector." {".DOKU_LF;
        foreach ($styles as $key => $value){
            $rule .= "    $key:$value;".DOKU_LF;
        }
        StringUtility::rtrim($rule,";");
        return $rule.DOKU_LF."}".DOKU_LF;

    }
}
