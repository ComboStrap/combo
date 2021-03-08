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


class CallStack
{

    /**
     * Delete from the call stack
     * @param $calls
     * @param $start
     * @param $end
     */
    public static function deleteCalls(&$calls, $start, $end)
    {
        for ($i = $start; $i <= $end; $i++) {
            unset($calls[$i]);
        }
    }

    /**
     * @param array $calls
     * @param integer $position
     * @param array $callStackToInsert
     */
    public static function insertCallStackUpWards(&$calls, $position, $callStackToInsert)
    {

        array_splice($calls, $position, 0, $callStackToInsert);

    }

    /**
     * Insert a tag above
     * @param $tagName
     * @param $state
     * @param $attribute
     * @param $context
     * @param string $content
     * @return array - a call
     */
    public static function createCall($tagName, $state, $attribute, $context, $content = '')
    {
        $data = array(
            PluginUtility::ATTRIBUTES => $attribute,
            PluginUtility::CONTEXT => $context,
            PluginUtility::STATE => $state
        );
        $positionInText = 0;

        return [
            "plugin",
            array(
                PluginUtility::getComponentName($tagName),
                $data,
                $state,
                $content
            ),
            $positionInText
        ];

    }




}
