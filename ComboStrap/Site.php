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
use http\Exception\RuntimeException;

class Site
{

    const STRAP_TEMPLATE_NAME = "strap";

    const SVG_LOGO_IDS = array(
        'wiki:logo.svg',
        'logo.svg'
    );

    const PNG_LOGO_IDS = array(
        'logo.png',
        'wiki:logo.png',
        'favicon-32×32.png',
        'favicon-16×16.png',
        'apple-touch-icon.png',
        'android-chrome-192x192.png'
    );
    /**
     * Name of the main header slot
     */
    public const SLOT_MAIN_HEADER_NAME = "slot_main_header";
    /**
     * Name of the main footer slot
     */
    public const SLOT_MAIN_FOOTER_NAME = "slot_main_footer";

    public const SLOT_MAIN_SIDE_NAME = "slot_main_side";

    const CANONICAL = "configuration";
    /**
     * Strap Template meta (version, release date, ...)
     * @var array
     */
    private static $STRAP_TEMPLATE_INFO;


    /**
     * @return WikiPath[]
     */
    public static function getLogoImagesAsPath(): array
    {
        $logosPaths = PluginUtility::mergeAttributes(self::PNG_LOGO_IDS, self::SVG_LOGO_IDS);
        $logos = [];
        foreach ($logosPaths as $logoPath) {
            $dokuPath = WikiPath::createMediaPathFromId($logoPath);
            if (FileSystems::exists($dokuPath)) {
                try {
                    $logos[] = $dokuPath;
                } catch (Exception $e) {
                    // The image is not valid
                    LogUtility::msg("The logo ($logoPath) is not a valid image. {$e->getMessage()}");
                }
            }
        }
        return $logos;
    }

    /**
     * @return void https://www.dokuwiki.org/config:userewrite
     */
    public static function setUrlRewriteToDoku()
    {
        global $conf;
        $conf['userewrite'] = '2';
    }

    /**
     * Web server rewrite (Apache rewrite (htaccess), Nginx)
     * @return void
     */
    public static function setWebServerUrlRewrite()
    {
        global $conf;
        $conf['userewrite'] = '1';
    }

    /**
     * https://www.dokuwiki.org/config:useslash
     * @return void
     */
    public static function useSlashSeparatorInEndpointUrl()
    {
        global $conf;
        $conf['useslash'] = 1; // use slash instead of ;
    }


    public static function getUrlEndpointSeparator(): string
    {
        $defaultSeparator = WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
        $slashSeparator = "/";
        global $conf;
        $key = 'useslash';
        $value = $conf[$key];
        try {
            $valueInt = DataType::toInteger($value);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("The ($key) configuration does not have an integer value ($value). Default separator returned");
            return $defaultSeparator;
        }
        switch ($valueInt) {
            case 0:
                return $defaultSeparator;
            case 1:
                return $slashSeparator;
            default:
                LogUtility::internalError("The ($key) configuration has an integer value ($valueInt) that is not a valid one (0 or 1). Default separator returned");
                return $defaultSeparator;
        }
    }

    /**
     * https://www.dokuwiki.org/config:useslash
     * @return void
     */
    public static function setUrlRewriteToDefault()
    {
        global $conf;
        $conf['useslash'] = 0;
    }

    public static function getTocMinHeadings(): int
    {
        global $conf;
        $confKey = 'tocminheads';
        $tocMinHeads = $conf[$confKey];
        if ($tocMinHeads === null) {
            return 0;
        }
        try {
            return DataType::toInteger($tocMinHeads);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The configuration ($confKey) is not an integer. Error:{$e->getMessage()}", self::CANONICAL);
            return 0;
        }
    }

    /**
     *
     */
    public static function getTocMax(): int
    {
        global $conf;
        try {
            return DataType::toInteger($conf['maxseclevel']);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("Unable to the the maxseclevel as integer. Error: {$e->getMessage()}", Toc::CANONICAL);
            return 0;
        }
    }

    /**
     * @param int $int
     */
    public static function setTocMinHeading(int $int)
    {
        global $conf;
        $conf['tocminheads'] = $int;
    }

    public static function getTopTocLevel(): int
    {
        global $conf;
        $confKey = 'toptoclevel';
        $value = $conf[$confKey];
        try {
            return DataType::toInteger($value);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The configuration ($confKey) has a value ($value) that is not an integer", self::CANONICAL);
            return 1;
        }
    }

    public static function setTocTopLevel(int $int)
    {
        global $conf;
        $confKey = 'toptoclevel';
        $conf[$confKey] = $int;

    }

    /**
     * @return int
     * https://www.dokuwiki.org/config:breadcrumbs
     */
    public static function getVisitedPagesCountInHistoricalBreadCrumb(): int
    {
        global $conf;
        $confKey = 'breadcrumbs';
        $visitedPagesInBreadCrumb = $conf[$confKey];
        $defaultReturnValue = 10;
        if ($visitedPagesInBreadCrumb === null) {
            return $defaultReturnValue;
        }
        try {
            return DataType::toInteger($visitedPagesInBreadCrumb);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The configuration ($confKey) has value ($visitedPagesInBreadCrumb) that is not an integer. Error:{$e->getMessage()}");
            return $defaultReturnValue;
        }

    }

    /**
     * This setting enables the standard DokuWiki XHTML renderer to be replaced by a render plugin that also provides XHTML output.
     * @param string $string
     * @return void
     */
    public static function setXhtmlRenderer(string $string)
    {
        global $conf;
        $conf["renderer_xhtml"] = $string;
    }


    function getEmailObfuscationConfiguration()
    {
        global $conf;
        return $conf['mailguard'];
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

    /**
     * @throws ExceptionNotFound
     */
    public static function getLogoAsSvgImage(): WikiPath
    {
        foreach (self::SVG_LOGO_IDS as $svgLogo) {
            $image = WikiPath::createMediaPathFromId($svgLogo);
            if (FileSystems::exists($image)) {
                return $image;
            }
        }
        throw new ExceptionNotFound("No Svg Logo Image found");
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getLogoAsRasterImage(): FetcherRaster
    {
        foreach (self::PNG_LOGO_IDS as $pngLogo) {

            try {
                $image = FetcherRaster::createImageRasterFetchFromId($pngLogo);
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The png Logo ($pngLogo) returns an error. {$e->getMessage()}");
                continue;
            }
            if (FileSystems::exists($image->getOriginalPath())) {
                return $image;
            }
        }
        throw new ExceptionNotFound("No raster logo image was found");
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
    public static function getLocale(string $sep = "-"): string
    {

        $locale = null;

        $lang = self::getLang();

        $country = self::getLanguageRegion();

        $locale = strtolower($lang) . $sep . strtoupper($country);

        return $locale;
    }

    /**
     *
     * ISO 3166 alpha-2 country code
     *
     */
    public static function getLanguageRegion(): string
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
        }
        return "us";

    }

    /**
     * @return string
     * Wrapper around  https://www.dokuwiki.org/config:lang
     */
    public static function getLang(): string
    {
        global $conf;
        $lang = $conf['lang'];
        if ($lang === null) {
            return "en";
        }
        return $lang;
    }

    /**
     * In a {@link PluginUtility::isDevOrTest()} dev environment,
     * don't set the
     * https://www.dokuwiki.org/config:baseurl
     * to be able to test the metadata / social integration
     * via a tunnel
     *
     */
    public static function getBaseUrl(): string
    {

        /**
         * Same as {@link getBaseURL()} ??
         */
        global $conf;
        $baseUrl = $conf['baseurl'];
        if (!empty($baseUrl)) {
            return $baseUrl;
        }
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

    public static function getPageDirectory(): LocalPath
    {
        global $conf;
        /**
         * Data dir is the pages dir (savedir is the data dir)
         */
        $pageDirectory = $conf['datadir'];
        if ($pageDirectory === null) {
            throw new ExceptionRuntime("The page directory ($pageDirectory) is null");
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
            throw new ExceptionRuntime("The data directory ($dataDirectory) is null");
        }
        return LocalPath::createFromPath($dataDirectory);
    }

    public static function isLowQualityProtectionEnable(): bool
    {
        return PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE) === 1;
    }

    public static function getIndexPageName()
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
     * @return int
     */
    public static function getXhtmlCacheTime(): int
    {
        global $conf;
        $cacheTime = $conf['cachetime'];
        if ($cacheTime === null) {
            LogUtility::internalError("The global `cachetime` configuration was not set.", self::CANONICAL);
            return FetcherMarkup::MAX_CACHE_AGE;
        }
        try {
            return DataType::toInteger($cacheTime);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The global `cachetime` configuration has a value ($cacheTime) that is not an integer. Error: {$e->getMessage()}", self::CANONICAL);
            return FetcherMarkup::MAX_CACHE_AGE;
        }
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
        (ExceptionCompile $e) {
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
        } catch (ExceptionCompile $e) {
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
            try {
                self::loadStrapUtilityTemplateIfPresentAndSameVersion();
            } catch (ExceptionCompile $e) {
                return $defaultRem;
            }

            $value = TplUtility::getRem();
            if ($value === null) {
                return $defaultRem;
            }
            try {
                return DataType::toInteger($value);
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The rem configuration value ($value) is not a integer. Error: {$e->getMessage()}");
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
        } catch (ExceptionCompile $e) {
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
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while calculating the secondary text color. {$e->getMessage()}");
            return null;
        }

    }


    public static function getSecondarySlotNames(): array
    {

        try {
            return [
                Site::getSidebarName(),
                Site::getPageHeaderSlotName(),
                Site::getPageFooterSlotName(),
                Site::getPrimaryHeaderSlotName(),
                Site::getPrimaryFooterSlotName(),
                Site::getPrimarySideSlotName()
            ];
        } catch (ExceptionCompile $e) {
            LogUtility::msg("An error has occurred while retrieving the name of the secondary slots. Error: {$e->getMessage()}");
            // We known at least this one
            return [
                Site::getSidebarName(),
                Site::getPrimaryHeaderSlotName(),
                Site::getPrimaryFooterSlotName()
            ];
        }


    }


    /**
     *
     */
    public static function getPrimaryHeaderSlotName(): ?string
    {
        return self::SLOT_MAIN_HEADER_NAME;
    }

    /**
     * Strap is loaded only if this is the same version
     * to avoid function, class, or members that does not exist
     * @throws ExceptionCompile if strap template utility class could not be loaded
     */
    public static function loadStrapUtilityTemplateIfPresentAndSameVersion(): void
    {

        if (class_exists("ComboStrap\TplUtility")) {
            /**
             * May be of bad version (loaded in memory by php-fpm)
             */
            Site::checkTemplateVersion();
            return;
        }

        $templateUtilityFile = __DIR__ . '/../../../tpl/strap/class/TplUtility.php';
        if (file_exists($templateUtilityFile)) {

            Site::checkTemplateVersion();
            require_once($templateUtilityFile);
            return;

        }

        if (Site::getTemplate() !== self::STRAP_TEMPLATE_NAME) {
            $message = "The strap template is not installed";
        } else {
            $message = "The file ($templateUtilityFile) was not found";
        }
        throw new ExceptionCompile($message);

    }

    /**
     *
     */
    public static function getPrimarySideSlotName(): string
    {

        return self::SLOT_MAIN_SIDE_NAME;

    }

    /**
     */
    public static function getPageFooterSlotName()
    {
        return tpl_getConf("footerSlotPageName", "slot_footer");
    }

    /**
     *
     */
    public static function getPageHeaderSlotName()
    {
        return tpl_getConf("headerSlotPageName", "slot_header");
    }

    /**
     *
     */
    public static function getPageSideSlotName()
    {
        return tpl_getConf("sidekickSlotPageName", "slot_main_side");
    }

    /**
     * @throws ExceptionCompile
     */
    public static function setConfStrapTemplate($name, $value)
    {
        Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        TplUtility::setConf($name, $value);

    }

    /**
     *
     */
    public static function getPrimaryFooterSlotName(): string
    {
        return self::SLOT_MAIN_FOOTER_NAME;
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
            throw new ExceptionRuntime("The media directory ($mediaDirectory) is null");
        }
        return LocalPath::createFromPath($mediaDirectory);
    }

    public static function getCacheDirectory(): LocalPath
    {
        global $conf;
        $cacheDirectory = $conf['cachedir'];
        if ($cacheDirectory === null) {
            throw new ExceptionRuntime("The cache directory ($cacheDirectory) is null");
        }
        return LocalPath::createFromPath($cacheDirectory);
    }


    /**
     * @throws ExceptionNotFound
     */
    public static function getLogoHtml(): ?string
    {

        $logoImagesPath = Site::getLogoImagesAsPath();
        $tagAttributes = TagAttributes::createEmpty("identity")
            ->addClassName("logo");
        foreach ($logoImagesPath as $logoImagePath) {
            try {
                if (!Identity::isReader($logoImagePath->getWikiId())) {
                    continue;
                }
                $imageFetcher = IFetcherLocalImage::createImageFetchFromPath($logoImagePath)
                    ->setRequestedHeight(72)
                    ->setRequestedWidth(72);
                if ($imageFetcher instanceof FetcherSvg) {
                    $imageFetcher->setRequestedType(FetcherSvg::ICON_TYPE);
                }
                return MediaMarkup::createFromFetcher($imageFetcher)
                    ->setLazyLoad(false)
                    ->setHtmlOrSetterTagAttributes($tagAttributes)
                    ->toHtml();
            } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound|ExceptionCompile $e) {
                LogUtility::msg("Error while rendering in HTML the logo $imageFetcher");
            }

        }
        throw new ExceptionNotFound("No logo image could be found");
    }

    /**
     * @throws ExceptionCompile
     */
    private static function checkTemplateVersion()
    {
        /**
         * Check the version
         */
        if (self::$STRAP_TEMPLATE_INFO === null) {
            self::$STRAP_TEMPLATE_INFO = confToHash(__DIR__ . '/../../../tpl/strap/template.info.txt');
        }
        $templateVersion = self::$STRAP_TEMPLATE_INFO['version'];
        $comboVersion = PluginUtility::$INFO_PLUGIN['version'];
        /** @noinspection DuplicatedCode */
        if ($templateVersion !== $comboVersion) {
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
            throw new ExceptionCompile($message);
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getLogoImage(): WikiPath
    {
        $logosImages = Site::getLogoImagesAsPath();
        if (empty($logosImages)) {
            throw new ExceptionNotFound("No logo image was installed", "logo");
        }
        return $logosImages[0];
    }

    public static function isSectionEditingEnabled(): bool
    {
        return self::getTocMax() > 0;
    }

    public static function enableSectionEditing()
    {
        global $conf;
        $conf['maxseclevel'] = 999;
    }

    public static function setDefaultTemplate()
    {
        global $conf;
        $conf['template'] = "dokuwiki";
    }

    /**
     * @return LocalPath[]
     */
    public static function getConfigurationFiles(): array
    {
        $files = [];
        foreach (getConfigFiles('main') as $fileConfig) {
            $files[] = LocalPath::createFromPath($fileConfig);
        }
        return $files;
    }

    /**
     * @return string the base directory for all url
     * @throws ExceptionNotFound
     */
    public static function getUrlPathBaseDir(): string
    {
        /**
         * Based on {@link getBaseURL()}
         */
        global $conf;
        if (!empty($conf['basedir'])) {
            return $conf['basedir'];
        }
        $scriptName = LocalPath::createFromPath($_SERVER['SCRIPT_NAME']);
        if ($scriptName->getExtension() === 'php') {
            return Url::toUrlSeparator($scriptName->getParent()->toPathString());
        }
        $phpSelf = LocalPath::createFromPath($_SERVER['PHP_SELF']);
        if ($phpSelf->getExtension() === "php") {
            return Url::toUrlSeparator($scriptName->getParent()->toPathString());
        }
        if ($_SERVER['DOCUMENT_ROOT'] && $_SERVER['SCRIPT_FILENAME']) {
            $dir = preg_replace('/^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '/') . '/', '',
                $_SERVER['SCRIPT_FILENAME']);
            return Url::toUrlSeparator(dirname('/' . $dir));
        }
        throw new ExceptionNotFound("No Base dir");

    }

    public static function getUrlRewrite(): string
    {
        global $conf;
        $confKey = 'userewrite';
        $urlRewrite = $conf[$confKey];
        try {
            $urlRewriteInt = DataType::toInteger($urlRewrite);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("The ($confKey) configuration is not an integer ($urlRewrite)");
            return UrlRewrite::NO_REWRITE;
        }
        switch ($urlRewriteInt) {
            case 0:
                return UrlRewrite::NO_REWRITE;
            case 1:
                return UrlRewrite::WEB_SERVER_REWRITE;
            case 2:
                return UrlRewrite::DOKU_REWRITE;
            default:
                LogUtility::internalError("The ($confKey) configuration value ($urlRewriteInt) is not a valid value (0, 1 or 2). No rewrite");
                return UrlRewrite::NO_REWRITE;
        }
    }

    public static function getDefaultMediaLinking(): string
    {
        return PluginUtility::getConfValue(MediaMarkup::CONF_DEFAULT_LINKING, MediaMarkup::LINKING_DIRECT_VALUE);
    }

    public static function shouldEndpointUrlBeAbsolute(): bool
    {
        global $conf;
        return $conf['canonical'] === 1;
    }

    public static function setEndpointToAbsoluteUrl()
    {
        global $conf;
        $conf['canonical'] = 1;
    }

    public static function setEndpointToDefaultUrl()
    {
        global $conf;
        $conf['canonical'] = 0;
    }


}
