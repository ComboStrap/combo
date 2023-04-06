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


use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Meta\Field\Region;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlRewrite;
use Exception;

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
    private $executingContext;

    /**
     * @param ExecutionContext $executingContext
     */
    public function __construct(ExecutionContext $executingContext)
    {
        $this->executingContext = $executingContext;
    }


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
     * @deprecated see {@link SiteConfig::setUrlRewriteToDoku()}
     */
    public static function setUrlRewriteToDoku()
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()
            ->setUrlRewriteToDoku();
    }

    /**
     * Web server rewrite (Apache rewrite (htaccess), Nginx)
     * @deprecated see {@link SiteConfig::setUrlRewriteToWebServer()}
     */
    public static function setUrlRewriteToWebServer()
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()
            ->setUrlRewriteToWebServer();
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
     * @param int $int
     * @deprecated
     */
    public static function setTocMinHeading(int $int)
    {
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->setTocMinHeading($int);
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

    /**
     * @param int $int
     * @return void
     * @deprecated
     */
    public static function setTocTopLevel(int $int)
    {
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->setTocTopLevel($int);

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

    /**
     * The host of the actual server
     * (may be virtual)
     * @return string
     */
    public static function getServerHost(): string
    {
        /**
         * Based on {@link getBaseURL()}
         * to be dokuwiki compliant
         */
        $remoteHost = $_SERVER['HTTP_HOST'];
        if ($remoteHost !== null) {
            return $remoteHost;
        }
        $remoteHost = $_SERVER['SERVER_NAME'];
        if ($remoteHost !== null) {
            return $remoteHost;
        }
        /**
         * OS name
         */
        return php_uname('n');
    }

    public static function getLangDirection()
    {
        global $lang;
        return $lang['direction'];
    }

    /**
     * Set a site configuration outside a {@link ExecutionContext}
     * It permits to configure the installation before execution
     *
     * For instance, we set the {@link PageTemplateName::CONF_DEFAULT_NAME default page layout} as {@link PageTemplateName::BLANK_TEMPLATE_VALUE}
     * in test by default to speed ud test. In a normal environment, the default is {@link PageTemplateName::HOLY_TEMPLATE_VALUE}
     *
     * @param $key
     * @param $value
     * @param string|null $namespace - the plugin name
     * @return void
     * @deprecated use {@link SiteConfig::setConf()}
     */
    public static function setConf($key, $value, ?string $namespace = PluginUtility::PLUGIN_BASE_NAME)
    {
        global $conf;
        if ($namespace !== null) {
            $conf['plugin'][$namespace][$key] = $value;
        } else {
            $conf[$key] = $value;
        }
    }

    public static function getLangObject(): Lang
    {
        return Lang::createFromValue(Site::getLang());
    }


    public static function getOldDirectory(): LocalPath
    {
        global $conf;
        /**
         * Data dir is the pages dir (savedir is the data dir)
         */
        $oldDirConf = $conf['olddir'];
        if ($oldDirConf === null) {
            throw new ExceptionRuntime("The old directory ($oldDirConf) is null");
        }
        return LocalPath::createFromPathString($oldDirConf);
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
            $pngLogoPath = WikiPath::createMediaPathFromId($pngLogo);
            if (!FileSystems::exists($pngLogoPath)) {
                continue;
            }
            try {
                return FetcherRaster::createImageRasterFetchFromPath($pngLogoPath);
            } catch (ExceptionCompile $e) {
                LogUtility::error("Error while getting the log as raster image: The png logo ($pngLogo) returns an error. {$e->getMessage()}", self::CANONICAL, $e);
                continue;
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
        $region = SiteConfig::getConfValue(Region::CONF_SITE_LANGUAGE_REGION);
        if (!empty($region)) {
            return $region;
        } else {

            if (extension_loaded("intl")) {
                $locale = locale_get_default();
                $localeParts = explode("_", $locale, 2);
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

    public static function setTemplate($template)
    {
        global $conf;
        $conf['template'] = $template;
    }

    /**
     * @return void
     * @deprecated
     */
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
        return LocalPath::createFromPathString($pageDirectory);
    }

    public static function disableHeadingSectionEditing()
    {
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->setDisableHeadingSectionEditing();
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

    /**
     * @return LocalPath
     * @deprecated
     */
    public static function getDataDirectory(): LocalPath
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getDataDirectory();
    }

    public static function isLowQualityProtectionEnable(): bool
    {
        return SiteConfig::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE) === 1;
    }

    /**
     * @return string
     * @deprecated
     */
    public static function getIndexPageName()
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getIndexPageName();
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

    /**
     * @param string $primaryColorValue
     * @return void
     * @deprecated
     */
    public static function setPrimaryColor(string $primaryColorValue)
    {
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->setPrimaryColor($primaryColorValue);
    }

    /**
     * @param $default
     * @return ColorRgb|null
     * @deprecated use {@link SiteConfig::getPrimaryColor()} instead
     */
    public static function getPrimaryColor($default = null): ?ColorRgb
    {
        try {
            return ExecutionContext::getActualOrCreateFromEnv()
                ->getConfig()
                ->getPrimaryColor();
        } catch (ExceptionNotFound $e) {
            return $default;
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
        self::setConf(ColorRgb::SECONDARY_COLOR_CONF, $secondaryColorValue);
    }

    public static function unsetPrimaryColor()
    {
        self::setConf(BrandingColors::PRIMARY_COLOR_CONF, null);
    }


    /**
     * @return bool
     * @deprecated
     */
    public static function isBrandingColorInheritanceEnabled(): bool
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->isBrandingColorInheritanceEnabled();
    }


    /**
     * @param $default
     * @return string|null
     */
    public static function getPrimaryColorValue($default = null): ?string
    {
        $value = SiteConfig::getConfValue(BrandingColors::PRIMARY_COLOR_CONF, $default);
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
        $value = SiteConfig::getConfValue(ColorRgb::SECONDARY_COLOR_CONF, $default);
        if ($value === null || trim($value) === "") {
            return null;
        }
        return $value;
    }

    public static function setCanonicalUrlType(string $value)
    {
        self::setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, $value);
    }

    public static function setCanonicalUrlTypeToDefault()
    {
        self::setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, null);
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
        return LocalPath::createFromPathString($mediaDirectory);
    }

    public static function getCacheDirectory(): LocalPath
    {
        global $conf;
        $cacheDirectory = $conf['cachedir'];
        if ($cacheDirectory === null) {
            throw new ExceptionRuntime("The cache directory ($cacheDirectory) is null");
        }
        return LocalPath::createFromPathString($cacheDirectory);
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


    /**
     * @return void
     * @deprecated
     */
    public static function enableSectionEditing()
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()->setEnableSectionEditing();
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
            $files[] = LocalPath::createFromPathString($fileConfig);
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
        $scriptName = LocalPath::createFromPathString($_SERVER['SCRIPT_NAME']);
        if ($scriptName->getExtension() === 'php') {
            return Url::toUrlSeparator($scriptName->getParent()->toAbsoluteId());
        }
        $phpSelf = LocalPath::createFromPathString($_SERVER['PHP_SELF']);
        if ($phpSelf->getExtension() === "php") {
            return Url::toUrlSeparator($scriptName->getParent()->toAbsoluteId());
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
        $confKey = UrlRewrite::CONF_KEY;
        $urlRewrite = $conf[$confKey];
        try {
            $urlRewriteInt = DataType::toInteger($urlRewrite);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("The ($confKey) configuration is not an integer ($urlRewrite)");
            return UrlRewrite::NO_REWRITE;
        }
        switch ($urlRewriteInt) {
            case UrlRewrite::NO_REWRITE_DOKU_VALUE:
                return UrlRewrite::NO_REWRITE;
            case UrlRewrite::WEB_SERVER_REWRITE_DOKU_VALUE:
                return UrlRewrite::WEB_SERVER_REWRITE;
            case UrlRewrite::DOKU_REWRITE_DOKU_VALUE:
                return UrlRewrite::VALUE_DOKU_REWRITE;
            default:
                LogUtility::internalError("The ($confKey) configuration value ($urlRewriteInt) is not a valid value (0, 1 or 2). No rewrite");
                return UrlRewrite::NO_REWRITE;
        }
    }

    public static function getDefaultMediaLinking(): string
    {
        return SiteConfig::getConfValue(MediaMarkup::CONF_DEFAULT_LINKING, MediaMarkup::LINKING_DIRECT_VALUE);
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

    public function getConfig(): SiteConfig
    {
        if (isset($this->config)) {
            return $this->config;
        }
        $this->config = new SiteConfig($this->executingContext);
        return $this->config;
    }


}
