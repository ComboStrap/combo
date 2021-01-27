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


class RenderUtility
{

    /**
     * @param $content
     * @return string|null
     */
    public static function renderText2Xhtml($content)
    {
        $instructions = self::getInstructions($content);
        return p_render('xhtml', $instructions, $info);
    }

    /**
     * @param $pageContent - the text (not the id)
     * @param bool $stripOpenAndEnd - to avoid the p element in test rendering
     * @return array
     */
    public static function getInstructions($pageContent, $stripOpenAndEnd = true)
    {

        $instructions = p_get_instructions($pageContent);

        if ($stripOpenAndEnd) {
            $lastPBlockPosition = sizeof($instructions) - 2;
            /**
             * p_open = document_start in renderer
             */
            if ($instructions[1][0] == 'p_open') {
                unset($instructions[1]);
            }
            /**
             * p_open = document_end in renderer
             */
            if ($instructions[$lastPBlockPosition][0] == 'p_close') {
                unset($instructions[$lastPBlockPosition]);
            }
        }

        return $instructions;
    }

    /**
     * @param $pageId
     * @return string|null
     */
    public static function renderId2Xhtml($pageId)
    {
        $file = wikiFN($pageId);
        if (file_exists($file)) {
            $content = file_get_contents($file);
            return self::renderText2Xhtml($content);
        } else {
            return false;
        }
    }

    public static function renderId2Json($pageId)
    {
        return Analytics::getDataAsJson($pageId);
    }
}
