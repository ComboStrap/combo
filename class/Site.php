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

    const CONF_SITE_ISO_COUNTRY = "siteIsoCountry";

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

    /**
     * @return string
     *
     * Locale always canonicalizes to upper case.
     */
    public static function getLocale()
    {

        $locale = null;

        $lang = self::getLang();
        if ($lang != null) {
            $country = self::getCountry();
            if ($country != null) {
                $locale = strtoupper("{$lang}_{$country}");
            }
        }

        return $locale;
    }

    /**
     *
     * ISO 3166 alpha-2 country code
     *
     */
    public static function getCountry()
    {
        $country = PluginUtility::getConfValue(self::CONF_SITE_ISO_COUNTRY);
        if (!StringUtility::match($country, "[a-zA-Z]{2}")) {
            LogUtility::msg("The country configuration value ($country) does not have two letters (ISO 3166 alpha-2 country code)", LogUtility::LVL_MSG_ERROR, "country");
        }
        return ($country ? $country : null);
    }

    /**
     * @return mixed|null
     * Wrapper around  https://www.dokuwiki.org/config:lang
     */
    private static function getLang()
    {

        global $conf;
        $locale = $conf['lang'];
        return ($locale ? $locale : null);
    }
}
