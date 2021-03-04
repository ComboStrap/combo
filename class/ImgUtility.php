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

    public static function render($attributes)
    {
        $src = $attributes['src'];
        $width = $attributes['width'];
        $height = $attributes['height'];
        $title = $attributes['title'];
        $class = $attributes['class'];
        //Snippet taken from $renderer->doc .= $renderer->internalmedia($src, $linking = 'nolink');
        $linkAttributes = array('cache' => true);
        if ($width != null) {
            $linkAttributes['w'] = $width;
        }
        if ($height != null) {
            $linkAttributes['h'] = $height;
        }
        $imgHTML = '<img class="' . $class . '" src="' . ml($src, array('w' => $width, 'h' => $height, 'cache' => true)) . '"';
        if ($title != null) {
            $imgHTML .= ' alt="' . $title . '"';
        }
        if ($width != null) {
            $imgHTML .= 'width="' . $width . '"';
        }
        return  $imgHTML.'>' ;
    }

    function isImage($text){
        return preg_match(' / ' . self::IMAGE_PATTERN . ' / msSi', $text);
    }

}
