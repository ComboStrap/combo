<?php

namespace ComboStrap;


class SiteConfig
{
    const LOG_EXCEPTION_LEVEL = 'log-exception-level';

    /**
     * A configuration to enable the template system
     */
    public const CONF_ENABLE_TEMPLATE_SYSTEM = "combo-conf-001";
    public const CONF_ENABLE_TEMPLATE_SYSTEM_DEFAULT = 1;

    /**
     * The default font-size for the pages
     */
    const REM_CONF = "combo-conf-002";
    const REM_CONF_DEFAULT = 16;
    const REM_CANONICAL = "rfs";

    /**
     * The maximum size to be embedded
     * Above this size limit they are fetched
     */
    public const HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT = "combo-conf-003";
    public const HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT_DEFAULT = 4;
    /**
     * Private configuration used in test
     * When set to true, all javascript snippet will be inlined
     */
    public const HTML_ALWAYS_INLINE_LOCAL_JAVASCRIPT = "combo-conf-004";
    const CANONICAL = "site-config";


    /**
     * @var WikiPath the {@link self::getContextPath()} when no context could be determined
     */
    private WikiPath $defaultContextPath;

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

    /**
     * @param $executionContext
     */
    public function __construct($executionContext)
    {
        $this->executionContext = $executionContext;
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
            $oldValue = Site::getConfValue($key, $value, $pluginNamespace);
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

    public function setDisableTemplating(): SiteConfig
    {
        $this->setConf(self::CONF_ENABLE_TEMPLATE_SYSTEM, 0);
        return $this;
    }

    public function isTemplatingEnabled(): bool
    {
        return $this->getBooleanValue(self::CONF_ENABLE_TEMPLATE_SYSTEM, self::CONF_ENABLE_TEMPLATE_SYSTEM_DEFAULT);
    }

    public function getValue(string $key, ?string $default = null)
    {
        return Site::getConfValue($key, $default);
    }

    /**
     * @param string $key
     * @param int $default - the default value (1=true,0=false in the dokuwiki config system)
     * @return bool
     */
    private function getBooleanValue(string $key, int $default): bool
    {
        $value = $this->getValue($key, $default);
        /**
         * Boolean in config is the value 1
         * the non strict equality is needed, we get a string for an unknown reason
         */
        return $value == 1;
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

    public function getRem(): int
    {

        $value = $this->getValue(self::REM_CONF, self::REM_CONF_DEFAULT);
        try {
            return DataType::toInteger($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The rem configuration value ($value) is not a integer. Error: {$e->getMessage()}");
            return self::REM_CONF_DEFAULT;
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

    public function getDefaultContextPath(): WikiPath
    {
        if (isset($this->defaultContextPath)) {
            return $this->defaultContextPath;
        }
        // in a admin or dynamic rendering
        // dokuwiki may have set a $ID
        global $ID;
        if (isset($ID) && $ID !== ExecutionContext::DEFAULT_SLOT_ID_FOR_TEST) {
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


}
