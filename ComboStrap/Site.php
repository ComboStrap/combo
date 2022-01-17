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


use Exception;
use RuntimeException;

class Site
{

    const STRAP_TEMPLATE_NAME = "strap";

    const SVG_LOGO_IDS = array(
        ':wiki:logo.svg',
        ':logo.svg'
    );

    const PNG_LOGO_IDS = array(
        ':logo.png',
        ':wiki:logo.png',
        ':favicon-32×32.png',
        ':favicon-16×16.png',
        ':apple-touch-icon.png',
        ':android-chrome-192x192.png'
    );


    /**
     * @return string|null the html img tag or null
     */
    public static function getLogoImgHtmlTag($tagAttributes = null): ?string
    {
        $logoIds = self::getLogoIds();
        foreach ($logoIds as $logoId) {
            if ($logoId->exists()) {
                $mediaLink = MediaLink::createMediaLinkFromPath($logoId->getPath(), $tagAttributes)
                    ->setLazyLoad(false);
                return $mediaLink->renderMediaTag();
            }
        }
        return null;
    }

    /**
     * @return Image[]
     */
    private static function getLogoIds(): array
    {
        $logosPaths = PluginUtility::mergeAttributes(self::PNG_LOGO_IDS, self::SVG_LOGO_IDS);
        $logos = [];
        foreach ($logosPaths as $logoPath) {
            $dokuPath = DokuPath::createMediaPathFromId($logoPath);
            if(FileSystems::exists($dokuPath)) {
                try {
                    $logos[] = Image::createImageFromPath($dokuPath);
                } catch (Exception $e) {
                    // The image is not valid
                    LogUtility::msg("The logo ($logoPath) is not a valid image. {$e->getMessage()}");
                }
            }
        }
        return $logos;
    }


    /**
     * @return string|null
     */
    public static function getLogoUrlAsSvg()
    {


        $url = null;
        foreach (self::SVG_LOGO_IDS as $svgLogo) {

            $svgLogoFN = mediaFN($svgLogo);

            if (file_exists($svgLogoFN)) {
                $url = ml($svgLogo, '', true, '', true);
                break;
            };
        }
        return $url;
    }

    public static function getLogoUrlAsPng()
    {

        $url = null;
        foreach (self::PNG_LOGO_IDS as $svgLogo) {

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
     * @param string $sep - the separator - generally ("-") but not always
     * @return string
     *
     * Locale always canonicalizes to upper case.
     */
    public static function getLocale(string $sep = "-"): ?string
    {

        $locale = null;

        $lang = self::getLang();
        if ($lang != null) {
            $country = self::getLanguageRegion();
            if ($country != null) {
                $locale = strtolower($lang) . $sep . strtoupper($country);
            }
        }

        return $locale;
    }

    /**
     *
     * ISO 3166 alpha-2 country code
     *
     */
    public static function getLanguageRegion()
    {
        $region = PluginUtility::getConfValue(Region::CONF_SITE_LANGUAGE_REGION);
        if (!empty($region)) {
            return $region;
        } else {

            if (extension_loaded("intl")) {
                $locale = locale_get_default();
                $localeParts = preg_split("/_/", $locale, 2);
                if (sizeof($localeParts) === 2) {
                    return $localeParts[1];
                }
            }

            return null;
        }

    }

    /**
     * @return mixed|null
     * Wrapper around  https://www.dokuwiki.org/config:lang
     */
    public static function getLang()
    {

        global $conf;
        $lang = $conf['lang'];
        return ($lang ?: null);
    }

    public static function getBaseUrl(): string
    {

        /**
         * In a {@link PluginUtility::isDevOrTest()} dev environment,
         * don't set the
         * https://www.dokuwiki.org/config:baseurl
         * to be able to test the metadata / social integration
         * via a tunnel
         *
         * Same as {@link getBaseURL()} ??
         */

        return DOKU_URL;

    }

    public static function getTag()
    {
        global $conf;
        $tag = $conf['tag'];
        return ($tag ? $tag : null);
    }

    /**
     * @return string - the name of the sidebar page
     */
    public static function getSidebarName()
    {
        global $conf;
        return $conf["sidebar"];
    }

    public static function setTemplate($template)
    {
        global $conf;
        $conf['template'] = $template;
    }

    public static function setCacheXhtmlOn()
    {
        // ensure the value is not -1, which disables caching
        // https://www.dokuwiki.org/config:cachetime
        global $conf;
        $conf['cachetime'] = 60 * 60;
    }

    public static function debugIsOn()
    {
        global $conf;
        return $conf['allowdebug'];
    }

    public static function setTemplateToStrap()
    {
        global $conf;
        $conf['template'] = 'strap';
    }

    public static function setTemplateToDefault()
    {
        global $conf;
        $conf['template'] = 'dokuwiki';
    }

    public static function setCacheDefault()
    {
        // The value is -1, which disables caching
        // https://www.dokuwiki.org/config:cachetime
        global $conf;
        $conf['cachetime'] = -1;
    }

    public static function useHeadingAsTitle()
    {
        // https://www.dokuwiki.org/config:useheading
        global $conf;
        $conf['useheading'] = 1;
    }

    public static function useHeadingDefault()
    {
        // https://www.dokuwiki.org/config:useheading
        global $conf;
        $conf['useheading'] = 0;
    }

    public static function getTemplate()
    {
        global $conf;
        return $conf['template'];

    }

    public static function isStrapTemplate()
    {
        global $conf;
        return $conf['template'] == self::STRAP_TEMPLATE_NAME;
    }

    public static function getAjaxUrl()
    {
        return self::getBaseUrl() . "lib/exe/ajax.php";
    }

    public static function getPageDirectory()
    {
        global $conf;
        /**
         * Data dir is the pages dir
         */
        return $conf['datadir'];
    }

    public static function disableHeadingSectionEditing()
    {
        global $conf;
        $conf['maxseclevel'] = 0;
    }

    public static function setBreadCrumbOn()
    {
        global $conf;
        $conf['youarehere'] = 1;
    }

    public static function isHtmlRenderCacheOn(): bool
    {
        global $conf;
        return $conf['cachetime'] !== -1;
    }

    public static function getDataDirectory()
    {
        global $conf;
        $dataDirectory = $conf['datadir'];
        if ($dataDirectory === null) {
            throw new RuntimeException("The base directory ($dataDirectory) is null");
        }
        $file = File::createFromPath($dataDirectory)->getParent();
        return $file->getAbsoluteFileSystemPath();
    }

    public static function isLowQualityProtectionEnable(): bool
    {
        return PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE) === 1;
    }

    public static function getHomePageName()
    {
        global $conf;
        return $conf["start"];
    }

    /**
     * @return mixed - Application / Website name
     */
    public static function getName()
    {
        global $conf;
        return $conf["title"];
    }

    public static function getTagLine()
    {
        global $conf;
        return $conf['tagline'];
    }

    /**
     * @return int|null
     */
    public static function getCacheTime(): ?int
    {
        global $conf;
        $cacheTime = $conf['cachetime'];
        if ($cacheTime === null) {
            return null;
        }
        if (is_numeric($cacheTime)) {
            return intval($cacheTime);
        }
        return null;
    }

    /**
     * Absolute vs Relative URL
     * https://www.dokuwiki.org/config:canonical
     */
    public static function getCanonicalConfForRelativeVsAbsoluteUrl()
    {
        global $conf;
        return $conf['canonical'];
    }


}
