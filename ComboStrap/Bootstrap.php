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

use action_plugin_combo_bootstrap;
use Exception;

class Bootstrap
{
    const DEFAULT_STYLESHEET_NAME = "bootstrap";

    /**
     * @param string $qualifiedVersion - the bootstrap version separated by the stylesheet
     */
    public function __construct(string $qualifiedVersion)
    {
        $bootstrapStyleSheetArray = explode(Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR, $qualifiedVersion);
        $this->version = $bootstrapStyleSheetArray[0];
        if (isset($bootstrapStyleSheetArray[1])) {
            $this->styleSheetName = $bootstrapStyleSheetArray[1];
        } else {
            $this->styleSheetName = self::DEFAULT_STYLESHEET_NAME;
        }
    }


    const BootStrapDefaultMajorVersion = "5";
    const BootStrapFiveMajorVersion = "5";

    const CONF_BOOTSTRAP_MAJOR_VERSION = "bootstrapMajorVersion";
    const BootStrapFourMajorVersion = "4";
    const CANONICAL = "bootstrap";
    /**
     * Stylesheet and Boostrap should have the same version
     * This conf is a mix between the version and the stylesheet
     *
     * majorVersion.0.0 - stylesheetname
     */
    public const CONF_BOOTSTRAP_VERSION_STYLESHEET = "bootstrapVersionStylesheet";
    /**
     * The separator in {@link Bootstrap::CONF_BOOTSTRAP_VERSION_STYLESHEET}
     */
    public const BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR = " - ";
    public const DEFAULT_BOOTSTRAP_VERSION_STYLESHEET = "5.0.1" . Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR . "bootstrap";
    private bool $wasBuild = false;
    /**
     * @var mixed|string
     */
    private $version;
    /**
     * @var string - the stylesheet name
     */
    private $styleSheetName;

    public static function getDataNamespace()
    {
        $dataToggleNamespace = "";
        if (self::getBootStrapMajorVersion() == self::BootStrapFiveMajorVersion) {
            $dataToggleNamespace = "-bs";
        }
        return $dataToggleNamespace;
    }

    public static function getBootStrapMajorVersion()
    {
        $default = PluginUtility::getConfValue(self::CONF_BOOTSTRAP_MAJOR_VERSION, self::BootStrapDefaultMajorVersion);
        if (Site::isStrapTemplate()) {
            try {
                Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
            } catch (ExceptionCompile $e) {
                return $default;
            }
            $bootstrapVersion = self::getVersion();
            return $bootstrapVersion[0];
        }
        return $default;


    }


    public function getVersion(): string
    {
        return $this->version;
    }

    public static function createFromQualifiedVersion(string $boostrapVersion): Bootstrap
    {
        return new Bootstrap($boostrapVersion);
    }

    public static function createFromConfiguration(string $boostrapVersion): Bootstrap
    {
        $bootstrapStyleSheetVersion = PluginUtility::getConfValue(Bootstrap::CONF_BOOTSTRAP_VERSION_STYLESHEET, Bootstrap::DEFAULT_BOOTSTRAP_VERSION_STYLESHEET);
        return new Bootstrap($bootstrapStyleSheetVersion);
    }

    public function getStyleSheetName(): string
    {
        return $this->styleSheetName;
    }

    /**
     *
     * @return array - an array of stylesheet tag (the standard stylesheet and the rtl stylesheet if any)
     * @throws ExceptionBadState
     */
    public function getStyleSheetTags(): array
    {

        /**
         * Standard stylesheet
         */
        $stylesheetsFile = WikiPath::createComboResource(':library:bootstrap:bootstrapStylesheet.json');
        try {
            $styleSheets = Json::createFromPath($stylesheetsFile)->toArray();
        } catch (ExceptionNotFound|ExceptionBadSyntax $e) {
            LogUtility::internalError("An error has occurred reading the file ($stylesheetsFile). Error:{$e->getMessage()}", self::CANONICAL);
            return [];
        }

        /**
         * User defined stylesheet
         */
        $localStyleSheetsFile = WikiPath::createComboResource(':library:bootstrap:bootstrapLocal.json');
        try {
            $localStyleSheets = Json::createFromPath($localStyleSheetsFile)->toArray();
            foreach ($styleSheets as $bootstrapVersion => &$stylesheetsFiles) {
                if (isset($localStyleSheets[$bootstrapVersion])) {
                    $stylesheetsFiles = array_merge($stylesheetsFiles, $localStyleSheets[$bootstrapVersion]);
                }
            }
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            // user file does not exists and that's okay
        }


        $version = $this->getVersion();
        if (!isset($styleSheets[$version])) {
            throw new ExceptionBadState("The bootstrap version ($version) could not be found in the custom CSS file ($stylesheetsFile, or $localStyleSheetsFile)");
        }
        $styleSheetsForVersion = $styleSheets[$version];

        $styleSheetName = $this->getStyleSheetName();
        if (!isset($styleSheetsForVersion[$styleSheetName])) {
            throw new ExceptionBadState("The bootstrap stylesheet ($styleSheetName) could not be found for the version ($version) in the distribution or custom configuration files ($stylesheetsFile, or $localStyleSheetsFile)");
        }
        $styleSheetForVersionAndName = $styleSheetsForVersion[$styleSheetName];

        /**
         * Select Rtl or Ltr
         * Stylesheet name may have another level
         * with direction property of the language
         *
         * Bootstrap needs another stylesheet
         * See https://getbootstrap.com/docs/5.0/getting-started/rtl/
         */
        $direction = Lang::createFromRequestedMarkup()->getDirection();
        if (isset($styleSheetForVersionAndName[$direction])) {
            return $styleSheetForVersionAndName[$direction];
        }

        return $styleSheetForVersionAndName;


    }


    /**
     * @return array - A list of all available stylesheets
     * This function is used to build the configuration as a list of files
     */
    public static function getStylesheetsForMetadataConfiguration()
    {
        $cssVersionsMetas = Bootstrap::getStyleSheetTags();
        $listVersionStylesheetMeta = array();
        foreach ($cssVersionsMetas as $bootstrapVersion => $cssVersionMeta) {
            foreach ($cssVersionMeta as $fileName => $values) {
                $listVersionStylesheetMeta[] = $bootstrapVersion . Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR . $fileName;
            }
        }
        return $listVersionStylesheetMeta;
    }

    public static function getClass(): string
    {
        return StyleUtility::addComboStrapSuffix(self::CANONICAL);
    }

    /**
     * @return string the class attached to the tag
     */
    public static function getBundleClass(): string
    {
        return StyleUtility::addComboStrapSuffix(self::CANONICAL . "-bundle");
    }


    public static function get(): Bootstrap
    {
        return new Bootstrap();
    }

    public function getCssSnippet(): Snippet
    {
        $this->buildBootstrapMetaIfNeeded();
    }

    /**
     * @throws ExceptionNotFound
     * @throws ExceptionBadState
     */
    private function buildBootstrapMetaIfNeeded(): void
    {

        if ($this->wasBuild) {
            return;
        }
        $this->wasBuild = true;


        $version = $this->getVersion();
        $styleSheetName = $this->getStyleSheetName();


        // if cdn
        $useCdn = PluginUtility::getConfValue(SnippetManager::CONF_USE_CDN, SnippetManager::CONF_USE_CDN_DEFAULT);

        // metadata
        $bootstrapJsonFile = WikiPath::createComboResource(":library:bootstrap:bootstrapJavascript.json");
        try {
            $bootstrapJsonMetas = Json::createFromPath($bootstrapJsonFile)->toArray();
        } catch (ExceptionBadSyntax $e) {
            // should not happen, no need to advertise it
            throw new ExceptionRuntimeInternal("Unable to read the file {$bootstrapJsonFile} as json", self::CANONICAL, 1, $e);
        }
        if (!isset($bootstrapJsonMetas[$version])) {
            throw new ExceptionNotFound("The bootstrap version ($version) could not be found in the file $bootstrapJsonFile");
        }
        $bootstrapMetas = $bootstrapJsonMetas[$version];

        // Css
        $bootstrapMetas["stylesheet"] = $this->getStyleSheetTags();

        $scriptsMeta = [];
        // Build the returned Js script array
        $jsScripts = array();
        foreach ($bootstrapMetas as $key => $script) {
            $path_parts = pathinfo($script["file"]);
            $extension = $path_parts['extension'];
            if ($extension === "js") {
                $src = DOKU_BASE . "lib/tpl/strap/bootstrap/$version/" . $script["file"];
                if ($useCdn) {
                    if (isset($script["url"])) {
                        $src = $script["url"];
                    }
                }
                $jsScripts[$key] =
                    array(
                        'src' => $src,
                        'defer' => null
                    );
                if (isset($script['integrity'])) {
                    $jsScripts[$key]['integrity'] = $script['integrity'];
                    $jsScripts[$key]['crossorigin'] = 'anonymous';
                }
            }
        }

        $css = array();
        $cssScript = $scriptsMeta['css'];
        $href = DOKU_BASE . "lib/tpl/strap/bootstrap/$version/" . $cssScript["file"];
        if ($useCdn) {
            if (isset($script["url"])) {
                $href = $script["url"];
            }
        }
        $css['css'] =
            array(
                'href' => $href,
                'rel' => "stylesheet"
            );
        if (isset($script['integrity'])) {
            $css['css']['integrity'] = $script['integrity'];
            $css['css']['crossorigin'] = 'anonymous';
        }


    }

    public function getSnippets(): array
    {
        return [];
    }
}
