<?php

namespace ComboStrap;

use action_plugin_combo_docustom;

class SiteConfig
{
    const LOG_EXCEPTION_LEVEL = 'log-exception-level';

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
    private $executionContext;

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
        $this->setConf(action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM, 0);
        return $this;
    }

    public function isTemplatingEnabled(): bool
    {
        return $this->getBooleanValue(action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM, action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM_DEFAULT);
    }

    public function getValue(string $key, ?string $default)
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
        $this->setConf('console',0);
        return $this;
    }

    public function setLogExceptionToError(): SiteConfig
    {
        $this->setLogExceptionLevel(LogUtility::LVL_MSG_ERROR);
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




}
