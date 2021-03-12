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


class Site
{

    public static function getImageUrlAsSvg()
    {
        $look = array(
            ':wiki:logo.svg',
            ':logo.svg'
        );

        $url = null;
        foreach ($look as $svgLogo) {

            $svgLogoFN = mediaFN($svgLogo);

            if (file_exists($svgLogoFN)) {
                $url = ml($svgLogo, '', true, '', true);
                break;
            };
        }
        return $url;
    }

    public static function getImageUrl()
    {
        $look = array(
            ':logo.png',
            ':wiki:logo.png',
            ':favicon-32×32.png',
            ':favicon-16×16.png',
            ':apple-touch-icon.png',
            ':android-chrome-192x192.png'
        );

        $url = null;
        foreach ($look as $svgLogo) {

            $svgLogoFN = mediaFN($svgLogo);

            if (file_exists($svgLogoFN)) {
                $url = ml($svgLogo, '', true, '', true);
                break;
            };
        }
        return $url;
    }

    /**
     * https://www.dokuwiki.org/config:title
     * @return mixed
     */
    public static function getTitle()
    {
        global $conf;
        return $conf['title'];
    }
}
