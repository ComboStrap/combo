<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageTemplateName;
use dokuwiki\Extension\PluginTrait;
use syntax_plugin_combo_headingwiki;

class SiteConfig
{
    const LOG_EXCEPTION_LEVEL = 'log-exception-level';

    /**
     * A configuration to enable the theme/template system
     */
    public const CONF_ENABLE_THEME_SYSTEM = "combo-conf-001";
    public const CONF_ENABLE_THEME_SYSTEM_DEFAULT = 1;

    /**
     * The default font-size for the pages
     */
    const REM_CONF = "combo-conf-002";
    const REM_CANONICAL = "rfs";

    /**
     * The maximum size to be embedded
     * Above this size limit they are fetched
     *
     * 2kb is too small for icon.
     * For instance, the et:twitter is 2,600b
     */
    public const HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT = "combo-conf-003";
    public const HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT_DEFAULT = 4;
    /**
     * Private configuration used in test
     * When set to true, all javascript snippet will be inlined
     */
    public const HTML_ALWAYS_INLINE_LOCAL_JAVASCRIPT = "combo-conf-004";
    const CANONICAL = "site-config";
    const GLOBAL_SCOPE = null;
    /**
     * The default name
     */
    public const CONF_DEFAULT_INDEX_NAME = "start";


    /**
     * @var WikiPath the {@link self::getContextPath()} when no context could be determined
     */
    private WikiPath $defaultContextPath;

    private array $authorizedUrlSchemes;

    /**
     * @var array - the configuration value to restore
     *
     * Note we can't capture the whole global $conf
     * because the configuration are loaded at runtime via {@link PluginTrait::loadConfig()}
     *
     * Meaning that the configuration environment at the start is not fully loaded
     * and does not represent the environment totally
     *
     * We capture then the change and restore them at the end
     */
    private array $configurationValuesToRestore = [];
    private ExecutionContext $executionContext;
    private array $interWikis;

    /**
     * @param ExecutionContext $executionContext
     */
    public function __construct(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;
    }

    /**
     * TODO: Default: Note that the config of plugin are loaded
     *   via {@link PluginTrait::loadConfig()}
     *   when {@link PluginTrait::getConf()} is used
     *   Therefore whenever possible, for now {@link PluginTrait::getConf()}
     *   should be used otherwise, there is no default
     *   Or best, the default should be also in the code
     *
     */
    public static function getConfValue($confName, $defaultValue = null, ?string $namespace = PluginUtility::PLUGIN_BASE_NAME)
    {
        global $conf;
        if ($namespace !== null) {

            $namespace = $conf['plugin'][$namespace] ?? null;
            if ($namespace === null) {
                return $defaultValue;
            }
            $value = $namespace[$confName] ?? null;

        } else {

            $value = $conf[$confName] ?? null;

        }
        if (DataType::isBoolean($value)) {
            /**
             * Because the next line
             * `trim($value) === ""`
             * is true for a false value
             */
            return $value;
        }
        if ($value === null || trim($value) === "") {
            return $defaultValue;
        }
        return $value;
    }


    /**
     * @param string $key
     * @param $value
     * @param string|null $pluginNamespace - null for the global namespace
     * @return $this
     */
    public function setConf(string $key, $value, ?string $pluginNamespace = PluginUtility::PLUGIN_BASE_NAME): SiteConfig
    {
        /**
         * Environment within dokuwiki is a global variable
         *
         * We set it the global variable
         *
         * but we capture it {@link ExecutionContext::$capturedConf}
         * to restore it when the execution context os {@link ExecutionContext::close()}
         */
        $globalKey = "$pluginNamespace:$key";
        if (!isset($this->configurationValuesToRestore[$globalKey])) {
            $oldValue = self::getConfValue($key, $value, $pluginNamespace);
            $this->configurationValuesToRestore[$globalKey] = $oldValue;
        }
        Site::setConf($key, $value, $pluginNamespace);
        return $this;
    }

    /**
     * Restore the configuration
     * as it was when php started
     * @return void
     */
    public function restoreConfigState()
    {

        foreach ($this->configurationValuesToRestore as $guid => $value) {
            [$plugin, $confKey] = explode(":", $guid);
            Site::setConf($confKey, $value, $plugin);
        }
    }

    public function setDisableThemeSystem(): SiteConfig
    {
        $this->setConf(self::CONF_ENABLE_THEME_SYSTEM, 0);
        return $this;
    }

    public function isThemeSystemEnabled(): bool
    {
        return $this->getBooleanValue(self::CONF_ENABLE_THEME_SYSTEM, self::CONF_ENABLE_THEME_SYSTEM_DEFAULT);
    }

    public function getValue(string $key, ?string $default = null, ?string $scope = PluginUtility::PLUGIN_BASE_NAME)
    {
        return self::getConfValue($key, $default, $scope);
    }

    /**
     * @param string $key
     * @param int $default - the default value (1=true,0=false in the dokuwiki config system)
     * @return bool
     */
    public function getBooleanValue(string $key, int $default): bool
    {
        $value = $this->getValue($key, $default);
        /**
         * Boolean in config is normally the value 1
         */
        return DataType::toBoolean($value);
    }

    public function setCacheXhtmlOn()
    {
        // ensure the value is not -1, which disables caching
        // https://www.dokuwiki.org/config:cachetime

        $this->setConf('cachetime', 60 * 60, null);
        return $this;
    }

    public function setConsoleOn(): SiteConfig
    {
        $this->setConf('console', 1);
        return $this;
    }

    public function isConsoleOn(): bool
    {
        return $this->getBooleanValue('console', 0);
    }

    public function getExecutionContext(): ExecutionContext
    {
        return $this->executionContext;
    }

    public function setConsoleOff(): SiteConfig
    {
        $this->setConf('console', 0);
        return $this;
    }

    public function setLogExceptionToError(): SiteConfig
    {
        $this->setLogExceptionLevel(LogUtility::LVL_MSG_ERROR);
        return $this;
    }

    public function setDisableLogException(): SiteConfig
    {
        $this->setLogExceptionLevel(LogUtility::LVL_MSG_ABOVE_ERROR);
        return $this;
    }

    public function setLogExceptionLevel(int $level): SiteConfig
    {
        $this->setConf(self::LOG_EXCEPTION_LEVEL, $level);
        return $this;
    }

    public function getLogExceptionLevel(): int
    {
        return $this->getValue(self::LOG_EXCEPTION_LEVEL, LogUtility::DEFAULT_THROW_LEVEL);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRemFontSize(): int
    {

        $value = $this->getValue(self::REM_CONF);
        if ($value === null) {
            throw new ExceptionNotFound("No rem sized defined");
        }
        try {
            return DataType::toInteger($value);
        } catch (ExceptionCompile $e) {
            $message = "The rem configuration value ($value) is not a integer. Error: {$e->getMessage()}";
            LogUtility::msg($message);
            throw new ExceptionNotFound($message);
        }

    }

    public function setDefaultContextPath(WikiPath $contextPath)
    {
        $this->defaultContextPath = $contextPath;
        if (FileSystems::isDirectory($this->defaultContextPath)) {
            /**
             * Not a directory.
             *
             * If the link or path is the empty path, the path is not the directory
             * but the actual markup
             */
            throw new ExceptionRuntimeInternal("The path ($contextPath) should not be a namespace path");
        }
        return $this;
    }

    /**
     * @return WikiPath - the default context path is if not set the root page
     */
    public function getDefaultContextPath(): WikiPath
    {
        if (isset($this->defaultContextPath)) {
            return $this->defaultContextPath;
        }
        // in a admin or dynamic rendering
        // dokuwiki may have set a $ID
        global $ID;
        if (isset($ID)) {
            return WikiPath::createMarkupPathFromId($ID);
        }
        return WikiPath::createRootNamespacePathOnMarkupDrive()->resolve(Site::getIndexPageName() . "." . WikiPath::MARKUP_DEFAULT_TXT_EXTENSION);
    }

    public function getHtmlMaxInlineResourceSize()
    {
        try {
            return DataType::toInteger($this->getValue(SiteConfig::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT, self::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT_DEFAULT)) * 1024;
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("Max in line size error.", self::CANONICAL, $e);
            return self::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT_DEFAULT * 1024;
        }
    }

    public function setHtmlMaxInlineResourceSize(int $kbSize): SiteConfig
    {
        $this->setConf(SiteConfig::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT, $kbSize);
        return $this;
    }

    public function setDisableHeadingSectionEditing(): SiteConfig
    {
        $this->setConf('maxseclevel', 0, null);
        return $this;
    }

    public function setHtmlEnableAlwaysInlineLocalJavascript(): SiteConfig
    {
        $this->setConf(self::HTML_ALWAYS_INLINE_LOCAL_JAVASCRIPT, 1);
        return $this;
    }

    public function setHtmlDisableAlwaysInlineLocalJavascript(): SiteConfig
    {
        $this->setConf(self::HTML_ALWAYS_INLINE_LOCAL_JAVASCRIPT, 0);
        return $this;
    }

    public function isLocalJavascriptAlwaysInlined(): bool
    {
        return $this->getBooleanValue(self::HTML_ALWAYS_INLINE_LOCAL_JAVASCRIPT, 0);
    }


    public function disableLazyLoad(): SiteConfig
    {
        return $this->setConf(SvgImageLink::CONF_LAZY_LOAD_ENABLE, 0)
            ->setConf(LazyLoad::CONF_RASTER_ENABLE, 0);

    }

    public function setUseHeadingAsTitle(): SiteConfig
    {
        return $this->setConf('useheading', 1, self::GLOBAL_SCOPE);
    }

    public function setEnableSectionEditing(): SiteConfig
    {
        return $this->setConf('maxseclevel', 999, self::GLOBAL_SCOPE);
    }

    public function isSectionEditingEnabled(): bool
    {
        return $this->getTocMaxLevel() > 0;
    }

    public function getTocMaxLevel(): int
    {
        $value = $this->getValue('maxseclevel', null, self::GLOBAL_SCOPE);
        try {
            return DataType::toInteger($value);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("Unable to the the maxseclevel as integer. Error: {$e->getMessage()}", Toc::CANONICAL);
            return 0;
        }
    }

    public function setTocMinHeading(int $int): SiteConfig
    {
        return $this->setConf('tocminheads', $int, self::GLOBAL_SCOPE);
    }

    public function getIndexPageName()
    {
        return $this->getValue("start", self::CONF_DEFAULT_INDEX_NAME, self::GLOBAL_SCOPE);
    }

    public function getAuthorizedUrlSchemes(): ?array
    {
        if (isset($this->authorizedUrlSchemes)) {
            return $this->authorizedUrlSchemes;
        }
        $this->authorizedUrlSchemes = getSchemes();
        $this->authorizedUrlSchemes[] = "whatsapp";
        $this->authorizedUrlSchemes[] = "mailto";
        return $this->authorizedUrlSchemes;
    }

    public function getInterWikis(): array
    {
        $this->loadInterWikiIfNeeded();
        return $this->interWikis;
    }

    public function addInterWiki(string $name, string $value): SiteConfig
    {
        $this->loadInterWikiIfNeeded();
        $this->interWikis[$name] = $value;
        return $this;
    }

    private function loadInterWikiIfNeeded(): void
    {
        if (isset($this->interWikis)) {
            return;
        }
        $this->interWikis = getInterwiki();
    }

    public function setTocTopLevel(int $int): SiteConfig
    {
        return $this->setConf('toptoclevel', $int, self::GLOBAL_SCOPE);
    }

    public function getMetaDataDirectory(): LocalPath
    {
        $metadataDirectory = $this->getValue('metadir', null, self::GLOBAL_SCOPE);
        if ($metadataDirectory === null) {
            throw new ExceptionRuntime("The meta directory configuration value ('metadir') is null");
        }
        return LocalPath::createFromPathString($metadataDirectory);
    }

    public function setCanonicalUrlType(string $value): SiteConfig
    {
        return $this->setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, $value);
    }

    public function setEnableTheming(): SiteConfig
    {
        $this->setConf(SiteConfig::CONF_ENABLE_THEME_SYSTEM, 1);
        return $this;
    }

    public function getTheme(): string
    {
        return $this->getValue(TemplateEngine::CONF_THEME, TemplateEngine::CONF_THEME_DEFAULT);
    }

    /**
     * Note: in test to speed the test execution,
     * the default is set to {@link PageTemplateName::BLANK_TEMPLATE_VALUE}
     */
    public function getDefaultLayoutName()
    {
        return $this->getValue(PageTemplateName::CONF_DEFAULT_NAME, PageTemplateName::HOLY_TEMPLATE_VALUE);
    }

    public function setEnableThemeSystem(): SiteConfig
    {
        // this is the default but yeah
        $this->setConf(self::CONF_ENABLE_THEME_SYSTEM, 1);
        return $this;
    }

    /**
     * DokuRewrite
     * `doku.php/id/...`
     * https://www.dokuwiki.org/config:userewrite
     * @return $this
     */
    public function setUrlRewriteToDoku(): SiteConfig
    {
        $this->setConf('userewrite', '2', self::GLOBAL_SCOPE);
        return $this;
    }

    /**
     * Web server rewrite (Apache rewrite (htaccess), Nginx)
     * https://www.dokuwiki.org/config:userewrite
     * @return $this
     */
    public function setUrlRewriteToWebServer(): SiteConfig
    {
        $this->setConf('userewrite', '1', self::GLOBAL_SCOPE);
        return $this;
    }

    public function getRemFontSizeOrDefault(): int
    {
        try {
            return $this->getRemFontSize();
        } catch (ExceptionNotFound $e) {
            return 16;
        }
    }

    public function getDataDirectory(): LocalPath
    {
        global $conf;
        $dataDirectory = $conf['savedir'];
        if ($dataDirectory === null) {
            throw new ExceptionRuntime("The data directory ($dataDirectory) is null");
        }
        return LocalPath::createFromPathString($dataDirectory);
    }

    public function setTheme(string $themeName): SiteConfig
    {
        $this->setConf(TemplateEngine::CONF_THEME, $themeName);
        return $this;
    }

    public function getPageHeaderSlotName()
    {
        return $this->getValue(TemplateSlot::CONF_PAGE_HEADER_NAME, TemplateSlot::CONF_PAGE_HEADER_NAME_DEFAULT);
    }

    public function setConfDokuWiki(string $key, $value): SiteConfig
    {
        return $this->setConf($key, $value, self::GLOBAL_SCOPE);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getPrimaryColor(): ColorRgb
    {
        $value = Site::getPrimaryColorValue();
        if (
            $value === null ||
            (trim($value) === "")) {
            throw new ExceptionNotFound();
        }
        try {
            return ColorRgb::createFromString($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The primary color value configuration ($value) is not valid. Error: {$e->getMessage()}");
            throw new ExceptionNotFound();
        }
    }

    public function setPrimaryColor(string $primaryColorValue): SiteConfig
    {
        self::setConf(BrandingColors::PRIMARY_COLOR_CONF, $primaryColorValue);
        return $this;
    }

    public function getPrimaryColorOrDefault(string $defaultColor): ColorRgb
    {
        try {
            return $this->getPrimaryColor();
        } catch (ExceptionNotFound $e) {
            try {
                return ColorRgb::createFromString($defaultColor);
            } catch (ExceptionBadArgument $e) {
                LogUtility::internalError("The default color $defaultColor is not a color string.", self::CANONICAL, $e);
                return ColorRgb::getDefaultPrimary();
            }
        }
    }

    public function isBrandingColorInheritanceEnabled(): bool
    {
        return $this->getValue(BrandingColors::BRANDING_COLOR_INHERITANCE_ENABLE_CONF, BrandingColors::BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT) === 1;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getSecondaryColor(): ColorRgb
    {
        $secondaryColor = Site::getSecondaryColor();
        if ($secondaryColor === null) {
            throw new ExceptionNotFound();
        }
        return $secondaryColor;
    }

    public function isXhtmlCacheOn(): bool
    {
        global $conf;
        return $conf['cachetime'] !== -1;
    }

    public function isHeadingWikiComponentDisabled(): bool
    {
        return  !$this->getValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE,syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE);
    }


}
