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
    const TAG = self::CANONICAL;
    public const DEFAULT_BOOTSTRAP_4 = "4.5.0 - bootstrap";
    public const DEFAULT_BOOTSTRAP_5 = "5.0.1 - bootstrap";
    private Snippet $jquerySnippet;
    private Snippet $jsSnippet;
    private Snippet $popperSnippet;
    private Snippet $cssSnippet;

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
        $this->build();

    }


    const BootStrapDefaultMajorVersion = 5;
    const BootStrapFiveMajorVersion = 5;

    const CONF_BOOTSTRAP_MAJOR_VERSION = "bootstrapMajorVersion";
    const BootStrapFourMajorVersion = 4;
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

    public function getMajorVersion(): string
    {
        $bootstrapVersion = $this->getVersion();
        return $bootstrapVersion[0];

    }

    /**
     * Utility function that returns the major version
     * because this is really common in code
     * @return string - the major version
     */
    public static function getBootStrapMajorVersion(): string
    {
        return Bootstrap::get()->getMajorVersion();
    }


    public function getVersion(): string
    {
        return $this->version;
    }

    public static function createFromQualifiedVersion(string $boostrapVersion): Bootstrap
    {
        return new Bootstrap($boostrapVersion);
    }

    public static function createFromConfiguration(): Bootstrap
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
     * @return array - an array of stylesheet tag
     */
    public static function getStyleSheetMetas(): array
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
            foreach ($localStyleSheets as $bootstrapVersion => &$localStyleSheetData) {
                $actualStyleSheet = $styleSheets[$bootstrapVersion];
                if (isset($actualStyleSheet)) {
                    $styleSheets[$bootstrapVersion] = array_merge($actualStyleSheet, $localStyleSheetData);
                } else {
                    $styleSheets[$bootstrapVersion] = $localStyleSheetData;
                }
            }
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            // user file does not exists and that's okay
        }
        return $styleSheets;


    }


    /**
     * @return array - A list of all available stylesheets
     * This function is used to build the configuration as a list of files
     */
    public static function getQualifiedVersions(): array
    {
        $cssVersionsMetas = Bootstrap::getStyleSheetMetas();
        $listVersionStylesheetMeta = array();
        foreach ($cssVersionsMetas as $bootstrapVersion => $cssVersionMeta) {
            foreach ($cssVersionMeta as $fileName => $values) {
                $listVersionStylesheetMeta[] = $bootstrapVersion . Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR . $fileName;
            }
        }
        return $listVersionStylesheetMeta;
    }




    /**
     * @return Bootstrap
     */
    public static function get()
    {
        $wikiRequest = ExecutionContext::getOrCreateFromEnv();
        try {
            return $wikiRequest->getObject(self::CANONICAL);
        } catch (ExceptionNotFound $e) {
            $bootstrap = Bootstrap::createFromConfiguration();
            $wikiRequest->setObject(self::CANONICAL, $bootstrap);
            return $bootstrap;
        }

    }


    /**
     * @throws ExceptionNotFound
     */
    public function getCssSnippet(): Snippet
    {
        if (isset($this->cssSnippet)) {
            return $this->cssSnippet;
        }
        throw new ExceptionNotFound("No css snippet");
    }

    /**
     * @return Snippet[] the js snippets in order
     */
    public function getJsSnippets(): array
    {

        /**
         * The javascript snippet order is important
         */
        $snippets = [];
        try {
            $snippets[] = $this->getJquerySnippet();
        } catch (ExceptionNotFound $e) {
            // error already send at build time
            // or just not present
        }
        try {
            $snippets[] = $this->getPopperSnippet();
        } catch (ExceptionNotFound $e) {
            // error already send at build time
        }
        try {
            $snippets[] = $this->getBootstrapJsSnippet();
        } catch (ExceptionNotFound $e) {
            // error already send at build time
        }
        return $snippets;

    }

    /**
     *
     */
    private function build(): void
    {


        $version = $this->getVersion();

        // Javascript
        $bootstrapJsonFile = WikiPath::createComboResource(":library:bootstrap:bootstrapJavascript.json");
        try {
            $bootstrapJsonMetas = Json::createFromPath($bootstrapJsonFile)->toArray();
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            // should not happen, no need to advertise it
            throw new ExceptionRuntimeInternal("Unable to read the file {$bootstrapJsonFile} as json.", self::CANONICAL, 1, $e);
        }
        if (!isset($bootstrapJsonMetas[$version])) {
            throw new ExceptionRuntimeInternal("The bootstrap version ($version) could not be found in the file $bootstrapJsonFile");
        }
        $bootstrapMetas = $bootstrapJsonMetas[$version];

        // Css
        $bootstrapMetas["stylesheet"] = $this->getStyleSheetMeta();


        foreach ($bootstrapMetas as $key => $script) {
            $fileNameWithExtension = $script["file"];
            $file = LocalPath::createFromPathString($fileNameWithExtension);

            $path = WikiPath::createComboResource(":bootstrap:$version:$fileNameWithExtension");
            $snippet = Snippet::createSnippet($path)
                ->setComponentId(self::TAG);
            $url = $script["url"];
            if (!empty($url)) {
                try {
                    $url = Url::createFromString($url);
                    $snippet->setRemoteUrl($url);
                    if (isset($script['integrity'])) {
                        $snippet->setIntegrity($script['integrity']);
                    }
                } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
                    LogUtility::internalError("The url ($url) for the bootstrap metadata ($fileNameWithExtension) from the bootstrap dictionary is not valid. Error:{$e->getMessage()}", self::CANONICAL);
                }
            }

            try {
                $extension = $file->getExtension();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("No extension was found on the file metadata ($fileNameWithExtension) from the bootstrap dictionary", self::CANONICAL);
                continue;
            }
            switch ($extension) {
                case Snippet::EXTENSION_JS:
                    $snippet->setCritical(false);
                    switch ($key) {
                        case "jquery":
                            $this->jquerySnippet = $snippet;
                            break;
                        case "js":
                            $this->jsSnippet = $snippet;
                            break;
                        case "popper":
                            $this->popperSnippet = $snippet;
                            break;
                        default:
                            LogUtility::internalError("The snippet key ($key) is unknown for bootstrap", self::CANONICAL);
                            break;
                    }
                    break;
                case Snippet::EXTENSION_CSS:
                    switch ($key) {
                        case "stylesheet":
                            $this->cssSnippet = $snippet;
                            break;
                        default:
                            LogUtility::internalError("The snippet key ($key) is unknown for bootstrap");
                            break;
                    }
            }
        }


    }

    public function getSnippets(): array
    {

        $snippets = [];
        try {
            $snippets[] = $this->getCssSnippet();
        } catch (ExceptionNotFound $e) {
            // error already send at build time
        }

        /**
         * The javascript snippet
         */
        return array_merge($snippets, $this->getJsSnippets());
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getPopperSnippet(): Snippet
    {
        if (isset($this->popperSnippet)) {
            return $this->popperSnippet;
        }
        throw new ExceptionNotFound("No popper snippet");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getBootstrapJsSnippet(): Snippet
    {
        if (isset($this->jsSnippet)) {
            return $this->jsSnippet;
        }
        throw new ExceptionNotFound("No js snippet");
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getJquerySnippet(): Snippet
    {
        if (isset($this->jquerySnippet)) {
            return $this->jquerySnippet;
        }
        throw new ExceptionNotFound("No jquery snippet");
    }

    /**
     * @return array - the stylesheet meta (file, url, ...) for the version
     */
    private function getStyleSheetMeta(): array
    {

        $styleSheets = self::getStyleSheetMetas();

        $version = $this->getVersion();
        if (!isset($styleSheets[$version])) {
            LogUtility::internalError("The bootstrap version ($version) could not be found");
            return [];
        }
        $styleSheetsForVersion = $styleSheets[$version];

        $styleSheetName = $this->getStyleSheetName();
        if (!isset($styleSheetsForVersion[$styleSheetName])) {
            LogUtility::internalError("The bootstrap stylesheet ($styleSheetName) could not be found for the version ($version) in the distribution or custom configuration files");
            return [];
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
}
