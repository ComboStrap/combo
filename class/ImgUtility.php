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


class ImgUtility
{

    const IMAGE_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";

    public static function parse($match)
    {
        return Doku_Handler_Parse_Media($match);
    }

    public static function render($payload, $class)
    {
        $src = $payload['src'];
        $width = $payload['width'];
        $height = $payload['height'];
        $title = $payload['title'];
        //Snippet taken from $renderer->doc .= $renderer->internalmedia($src, $linking = 'nolink');
        return '<img class="'.$class.'" src="' . ml($src, array('w' => $width, 'h' => $height, 'cache' => true)) . '" alt="' . $title . '" width="' . $width . '">' ;
    }

    function isImage($text){
        return preg_match('/' . self::IMAGE_PATTERN . '/msSi', $text);
    }

}
