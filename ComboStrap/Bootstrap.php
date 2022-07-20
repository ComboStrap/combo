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
            $bootstrapVersion = self::getBootStrapVersion();
            return $bootstrapVersion[0];
        }
        return $default;


    }

    /**
     * @return array
     * Return the headers needed by this template
     *
     * @throws Exception
     */
    public static function getBootstrapMetaHeaders(): array
    {

        // The version
        $bootstrapVersion = self::getBootStrapVersion();
        if ($bootstrapVersion === false) {
            /**
             * Strap may be called for test
             * by combo
             * In this case, the conf may not be reloaded
             */
            TplUtility::reloadConf();
            $bootstrapVersion = self::getBootStrapVersion();
            if ($bootstrapVersion === false) {
                throw new Exception("Bootstrap version should not be false");
            }
        }
        $scriptsMeta = self::buildBootstrapHeadTags($bootstrapVersion);

        // if cdn
        $useCdn = PluginUtility::getConfValue(SnippetManager::CONF_USE_CDN, SnippetManager::CONF_USE_CDN_DEFAULT);


        // Build the returned Js script array
        $jsScripts = array();
        foreach ($scriptsMeta as $key => $script) {
            $path_parts = pathinfo($script["file"]);
            $extension = $path_parts['extension'];
            if ($extension === "js") {
                $src = DOKU_BASE . "lib/tpl/strap/bootstrap/$bootstrapVersion/" . $script["file"];
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
        $href = DOKU_BASE . "lib/tpl/strap/bootstrap/$bootstrapVersion/" . $cssScript["file"];
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


        return array(
            'script' => $jsScripts,
            'link' => $css
        );


    }

    public static function getBootStrapVersion()
    {
        $bootstrapStyleSheetVersion = tpl_getConf(Bootstrap::CONF_BOOTSTRAP_VERSION_STYLESHEET, Bootstrap::DEFAULT_BOOTSTRAP_VERSION_STYLESHEET);
        $bootstrapStyleSheetArray = explode(Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR, $bootstrapStyleSheetVersion);
        return $bootstrapStyleSheetArray[0];
    }

    public static function getStyleSheetConf()
    {
        $bootstrapStyleSheetVersion = tpl_getConf(Bootstrap::CONF_BOOTSTRAP_VERSION_STYLESHEET, Bootstrap::DEFAULT_BOOTSTRAP_VERSION_STYLESHEET);
        $bootstrapStyleSheetArray = explode(Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR, $bootstrapStyleSheetVersion);
        return $bootstrapStyleSheetArray[1];
    }

    /**
     *
     * @param $version - return only the selected version if set
     * @return array - an array of the meta JSON custom files
     */
    public static function getStyleSheetsFromJsonFileAsArray($version = null): array
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


        if (isset($version)) {
            if (!isset($styleSheets[$version])) {
                TplUtility::msg("The bootstrap version ($version) could not be found in the custom CSS file ($stylesheetsFile, or $localStyleSheetsFile)");
            } else {
                $styleSheets = $styleSheets[$version];
            }
        }

        /**
         * Select Rtl or Ltr
         * Stylesheet name may have another level
         * with direction property of the language
         *
         * Bootstrap needs another stylesheet
         * See https://getbootstrap.com/docs/5.0/getting-started/rtl/
         */
        $direction = Lang::createFromRequestedMarkup()->getDirection();
        $directedStyleSheets = [];
        foreach ($styleSheets as $name => $styleSheetDefinition) {
            if (isset($styleSheetDefinition[$direction])) {
                $directedStyleSheets[$name] = $styleSheetDefinition[$direction];
            } else {
                $directedStyleSheets[$name] = $styleSheetDefinition;
            }
        }

        return $directedStyleSheets;
    }

    /**
     *
     * Build from all Bootstrap JSON meta files only one array
     * @param $version
     * @return array
     *
     * @throws ExceptionNotFound
     */
    public static function buildBootstrapHeadTags($version): array
    {
        $bootstrapJsonFile = WikiPath::createComboResource(":library:bootstrap:bootstrapJavascript.json");
        try {
            $bootstrapMetas = Json::createFromPath($bootstrapJsonFile)->toArray();
        } catch (ExceptionBadSyntax $e) {
            // should not happen, no need to advertise it
            throw new ExceptionRuntimeInternal("Unable to read the file {$bootstrapJsonFile} as json", self::CANONICAL, 1, $e);
        }

        if (!isset($bootstrapMetas[$version])) {
            throw new ExceptionNotFound("The bootstrap version ($version) could not be found in the file $bootstrapJsonFile");
        }
        $bootstrapMetas = $bootstrapMetas[$version];


        // Css
        $bootstrapCssFile = Bootstrap::getStyleSheetConf();
        $bootstrapCustomMetas = Bootstrap::getStyleSheetsFromJsonFileAsArray($version);

        if (!isset($bootstrapCustomMetas[$bootstrapCssFile])) {
            LogUtility::error("The bootstrap custom file ($bootstrapCssFile) could not be found in the custom CSS files for the version ($version)", self::CANONICAL);
        } else {
            $bootstrapMetas['css'] = $bootstrapCustomMetas[$bootstrapCssFile];
        }

        return $bootstrapMetas;
    }

    /**
     * @return array - A list of all available stylesheets
     * This function is used to build the configuration as a list of files
     */
    public static function getStylesheetsForMetadataConfiguration()
    {
        $cssVersionsMetas = Bootstrap::getStyleSheetsFromJsonFileAsArray();
        $listVersionStylesheetMeta = array();
        foreach ($cssVersionsMetas as $bootstrapVersion => $cssVersionMeta) {
            foreach ($cssVersionMeta as $fileName => $values) {
                $listVersionStylesheetMeta[] = $bootstrapVersion . Bootstrap::BOOTSTRAP_VERSION_STYLESHEET_SEPARATOR . $fileName;
            }
        }
        return $listVersionStylesheetMeta;
    }
}
