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
     * @return Image[]
     */
    public static function getLogoImages(): array
    {
        $logosPaths = PluginUtility::mergeAttributes(self::PNG_LOGO_IDS, self::SVG_LOGO_IDS);
        $logos = [];
        foreach ($logosPaths as $logoPath) {
            $dokuPath = DokuPath::createMediaPathFromId($logoPath);
            if (FileSystems::exists($dokuPath)) {
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
    public static function getLogoUrlAsSvg(): ?string
    {


        $url = null;
        foreach (self::SVG_LOGO_IDS as $svgLogo) {

            $svgLogoFN = mediaFN($svgLogo);
            if (file_exists($svgLogoFN)) {
                $url = ml($svgLogo, '', true, '', true);
                break;
            }
        }
        return $url;
    }

    public static function getLogoAsSvgImage(): ?ImageSvg
    {
        foreach (self::SVG_LOGO_IDS as $svgLogo) {

            try {
                $image = ImageSvg::createImageFromId($svgLogo);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The svg ($svgLogo) returns an error. {$e->getMessage()}");
                continue;
            }
            if ($image->exists()) {
                return $image;
            }
        }
        return null;
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
     * @return mixed
     * @deprecated use {@link Site::getName()} instead
     * https://www.dokuwiki.org/config:title
     */
    public static function getTitle()
    {
        global $conf;
        return $conf['title'];
    }

    /**
     * https://www.dokuwiki.org/config:title
     */
    public static function setName($name)
    {
        global $conf;
        $conf['title'] = $name;
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
         * Same as {@link wl()} without nothing
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
        $conf['template'] = self::STRAP_TEMPLATE_NAME;
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

    public static function getAjaxUrl(): string
    {
        return self::getBaseUrl() . "lib/exe/ajax.php";
    }

    public static function getPageDirectory()
    {
        global $conf;
        /**
         * Data dir is the pages dir (savedir is the data dir)
         */
        $pageDirectory = $conf['datadir'];
        if ($pageDirectory === null) {
            throw new ExceptionComboRuntime("The page directory ($pageDirectory) is null");
        }
        return LocalPath::createFromPath($pageDirectory);
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

    public static function getDataDirectory(): LocalPath
    {
        global $conf;
        $dataDirectory = $conf['savedir'];
        if ($dataDirectory === null) {
            throw new ExceptionComboRuntime("The data directory ($dataDirectory) is null");
        }
        return LocalPath::createFromPath($dataDirectory);
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
        return self::getTitle();
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
    public static function shouldUrlBeAbsolute(): bool
    {
        global $conf;
        $value = $conf['canonical'];
        if ($value === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $description
     * Same as {@link Site::setDescription()}
     */
    public static function setTagLine(string $description)
    {
        global $conf;
        $conf['tagline'] = $description;
    }

    /**
     * @param string $description
     *
     */
    public static function setDescription(string $description)
    {
        self::setTagLine($description);
    }

    public static function setPrimaryColor(string $primaryColorValue)
    {
        PluginUtility::setConf(ColorRgb::PRIMARY_COLOR_CONF, $primaryColorValue);
    }

    public static function getPrimaryColor($default = null): ?ColorRgb
    {
        $value = self::getPrimaryColorValue($default);
        if (
            $value === null ||
            (trim($value) === "")) {
            return null;
        }
        try {
            return ColorRgb::createFromString($value);
        } catch
        (ExceptionCombo $e) {
            LogUtility::msg("The primary color value configuration ($value) is not valid. Error: {$e->getMessage()}");
            return null;
        }
    }

    public static function getSecondaryColor($default = null): ?ColorRgb
    {
        $value = Site::getSecondaryColorValue($default);
        if ($value === null) {
            return null;
        }
        try {
            return ColorRgb::createFromString($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The secondary color value configuration ($value) is not valid. Error: {$e->getMessage()}");
            return null;
        }
    }

    public static function setSecondaryColor(string $secondaryColorValue)
    {
        PluginUtility::setConf(ColorRgb::SECONDARY_COLOR_CONF, $secondaryColorValue);
    }

    public static function unsetPrimaryColor()
    {
        PluginUtility::setConf(ColorRgb::PRIMARY_COLOR_CONF, null);
    }


    public static function isBrandingColorInheritanceEnabled(): bool
    {
        return PluginUtility::getConfValue(ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF, ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT) === 1;
    }

    public static function getRem(): int
    {
        $defaultRem = 16;
        if (Site::getTemplate() === self::STRAP_TEMPLATE_NAME) {
            $loaded = self::loadStrapUtilityTemplateIfPresentAndSameVersion();
            if ($loaded) {
                $value = TplUtility::getRem();
                if ($value === null) {
                    return $defaultRem;
                }
                try {
                    return DataType::toInteger($value);
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("The rem configuration value ($value) is not a integer. Error: {$e->getMessage()}");
                }
            }
        }
        return $defaultRem;
    }

    public static function enableBrandingColorInheritance()
    {
        PluginUtility::setConf(ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF, 1);
    }

    public static function setBrandingColorInheritanceToDefault()
    {
        PluginUtility::setConf(ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF, ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT);
    }

    public static function getPrimaryColorForText(string $default = null): ?ColorRgb
    {
        $primaryColor = self::getPrimaryColor($default);
        if ($primaryColor === null) {
            return null;
        }
        try {
            return $primaryColor
                ->toHsl()
                ->setSaturation(30)
                ->setLightness(40)
                ->toRgb()
                ->toMinimumContrastRatioAgainstWhite();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while calculating the primary text color. {$e->getMessage()}");
            return null;
        }
    }

    /**
     * More lightness than the text
     * @return ColorRgb|null
     */
    public static function getPrimaryColorTextHover(): ?ColorRgb
    {

        $primaryColor = self::getPrimaryColor();
        if ($primaryColor === null) {
            return null;
        }
        try {
            return $primaryColor
                ->toHsl()
                ->setSaturation(88)
                ->setLightness(53)
                ->toRgb()
                ->toMinimumContrastRatioAgainstWhite();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while calculating the secondary text color. {$e->getMessage()}");
            return null;
        }

    }


    public static function getSecondarySlotNames(): array
    {

        try {
            return [
                Site::getSidebarName(),
                Site::getHeaderSlotPageName(),
                Site::getFooterSlotPageName(),
                Site::getMainHeaderSlotName(),
                Site::getMainFooterSlotName()
            ];
        } catch (ExceptionCombo $e) {
            // We known at least this one
            return [Site::getSidebarName()];
        }


    }


    /**
     * @throws ExceptionCombo if the strap template is not installed or could not be loaded
     */
    public static function getMainHeaderSlotName(): ?string
    {
        self::loadStrapUtilityTemplateIfPresentAndSameVersion();
        return TplUtility::getMainHeaderSlotName();
    }

    /**
     * Strap is loaded only if this is the same version
     * to avoid function, class, or members that does not exist
     * @throws ExceptionCombo if strap template utility class could not be loaded
     */
    public static function loadStrapUtilityTemplateIfPresentAndSameVersion(): void
    {

        if (class_exists("ComboStrap\TplUtility")) {
            return;
        }

        $templateUtilityFile = __DIR__ . '/../../../tpl/strap/class/TplUtility.php';
        if (file_exists($templateUtilityFile)) {
            /**
             * Check the version
             */
            $templateInfo = confToHash(__DIR__ . '/../../../tpl/strap/template.info.txt');
            $templateVersion = $templateInfo['version'];
            $comboVersion = PluginUtility::$INFO_PLUGIN['version'];
            if ($templateVersion != $comboVersion) {
                $strapName = "Strap";
                $comboName = "Combo";
                $strapLink = "<a href=\"https://www.dokuwiki.org/template:strap\">$strapName</a>";
                $comboLink = "<a href=\"https://www.dokuwiki.org/plugin:combo\">$comboName</a>";
                if ($comboVersion > $templateVersion) {
                    $upgradeTarget = $strapName;
                } else {
                    $upgradeTarget = $comboName;
                }
                $upgradeLink = "<a href=\"" . wl() . "&do=admin&page=extension" . "\">upgrade <b>$upgradeTarget</b> via the extension manager</a>";
                $message = "You should $upgradeLink to the latest version to get a fully functional experience. The version of $comboLink is ($comboVersion) while the version of $strapLink is ($templateVersion).";
                LogUtility::msg($message);
                throw new ExceptionCombo($message);
            } else {
                /** @noinspection PhpIncludeInspection */
                require_once($templateUtilityFile);

            }
        }

        if (Site::getTemplate() !== self::STRAP_TEMPLATE_NAME) {
            $message = "The strap template is not installed";
        } else {
            $message = "The file ($templateUtilityFile) was not found";
        }
        throw new ExceptionCombo($message);

    }

    /**
     * @throws ExceptionCombo
     */
    public static function getSideKickSlotPageName()
    {

        Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        return TplUtility::getSideKickSlotPageName();

    }

    /**
     * @throws ExceptionCombo
     */
    public static function getFooterSlotPageName()
    {
        Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        return TplUtility::getFooterSlotPageName();
    }

    /**
     * @throws ExceptionCombo
     */
    public static function getHeaderSlotPageName()
    {
        Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        return TplUtility::getHeaderSlotPageName();
    }

    /**
     * @throws ExceptionCombo
     */
    public static function setConfStrapTemplate($name, $value)
    {
        Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        TplUtility::setConf($name, $value);

    }

    /**
     * @throws ExceptionCombo
     */
    public static function getMainFooterSlotName(): string
    {
        self::loadStrapUtilityTemplateIfPresentAndSameVersion();
        return TplUtility::getMainFooterSlotName();
    }

    public static function getPrimaryColorValue($default = null)
    {
        $value = PluginUtility::getConfValue(ColorRgb::PRIMARY_COLOR_CONF, $default);
        if ($value !== null && trim($value) !== "") {
            return $value;
        }
        if (PluginUtility::isTest()) {
            // too much trouble
            // the load of styles is not consistent
            return null;
        }
        $styles = ColorRgb::getDokuWikiStyles();
        return $styles["replacements"]["__theme_color__"];

    }

    public static function getSecondaryColorValue($default = null)
    {
        $value = PluginUtility::getConfValue(ColorRgb::SECONDARY_COLOR_CONF, $default);
        if ($value === null || trim($value) === "") {
            return null;
        }
        return $value;
    }

    public static function setCanonicalUrlType(string $value)
    {
        PluginUtility::setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, $value);
    }

    public static function setCanonicalUrlTypeToDefault()
    {
        PluginUtility::setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, null);
    }

    public static function isBrandingColorInheritanceFunctional(): bool
    {
        return self::isBrandingColorInheritanceEnabled() && Site::getPrimaryColorValue() !== null;
    }

    public static function getMediaDirectory(): LocalPath
    {
        global $conf;
        $mediaDirectory = $conf['mediadir'];
        if ($mediaDirectory === null) {
            throw new ExceptionComboRuntime("The media directory ($mediaDirectory) is null");
        }
        return LocalPath::createFromPath($mediaDirectory);
    }

    public static function getCacheDirectory(): LocalPath
    {
        global $conf;
        $cacheDirectory = $conf['cachedir'];
        if ($cacheDirectory === null) {
            throw new ExceptionComboRuntime("The cache directory ($cacheDirectory) is null");
        }
        return LocalPath::createFromPath($cacheDirectory);
    }


    public static function getComboHome(): LocalPath
    {
        return LocalPath::create(DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME);
    }

    public static function getComboImagesDirectory(): LocalPath
    {
        return self::getComboResourcesDirectory()->resolve("images");
    }

    public static function getComboResourcesDirectory(): LocalPath
    {
        return Site::getComboHome()->resolve("resources");
    }

    public static function getComboDictionaryDirectory(): LocalPath
    {
        return Site::getComboResourcesDirectory()->resolve("dictionary");
    }

    public static function getComboResourceSnippetDirectory(): LocalPath
    {
        return Site::getComboResourcesDirectory()->resolve("snippet");
    }

    public static function getLogoHtml(): ?string
    {

        $tagAttributes = TagAttributes::createEmpty("identity");
        $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ICON_TYPE);
        $tagAttributes->addClassName("logo");


        /**
         * Logo
         */
        $logoImages = Site::getLogoImages();
        foreach ($logoImages as $logoImage) {
            $path = $logoImage->getPath();
            $mediaLink = MediaLink::createMediaLinkFromPath($path, $tagAttributes)
                ->setLazyLoad(false);
            try {
                return $mediaLink->renderMediaTag();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while rendering the logo $logoImage");
            }
        }

        return null;
    }


}
