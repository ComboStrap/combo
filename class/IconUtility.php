<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

require_once(__DIR__ . "/../class/XmlUtility.php");
require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/ConfUtility.php');

/**
 * Class IconUtility
 * @package ComboStrap
 * @see https://combostrap.com/icon
 *
 *
 * Material design does not have a repository structure where we can extract the location
 * from the name
 * https://material.io/resources/icons https://google.github.io/material-design-icons/
 *
 * Injection via javascript to avoid problem with the php svgsimple library
 * https://www.npmjs.com/package/svg-injector
 */
class IconUtility
{
    const CONF_ICONS_MEDIA_NAMESPACE = "icons_namespace";
    const CONF_ICONS_MEDIA_NAMESPACE_DEFAULT = ":" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME . ":icons";
    // Canonical name
    const NAME = "icon";
    // Arbitrary default namespace to be able to query with xpath
    const SVG_NAMESPACE = "svg";

    const ICON_LIBRARY_URLS = array(
        self::BOOTSTRAP => "https://raw.githubusercontent.com/twbs/icons/main/icons",
        self::MATERIAL_DESIGN => "https://raw.githubusercontent.com/Templarian/MaterialDesign/master/svg",
        self::FEATHER => "https://raw.githubusercontent.com/feathericons/feather/master/icons"
    );

    const CONF_DEFAULT_ICON_LIBRARY = "defaultIconLibrary";
    const LIBRARY_ACRONYM = array(
        "bs" => self::BOOTSTRAP,
        "md" => self::MATERIAL_DESIGN,
        "fe" => self::FEATHER
    );
    const FEATHER = "feather";
    const BOOTSTRAP = "bootstrap";
    const MATERIAL_DESIGN = "material-design";



    /**
     * The function used to render an icon
     * @param $attributes -  the icon attributes
     * @return bool|mixed - false if any error or the HTML
     */
    static public function renderIconByAttributes($attributes)
    {

        $name = "name";
        if (!array_key_exists($name, $attributes)) {
            LogUtility::msg("The attributes should have a name. It's mandatory for an icon.", LogUtility::LVL_MSG_ERROR, self::NAME);
            return false;
        }

        /**
         * The attribute
         */
        $iconNameAttribute = $attributes[$name];


        // If the name have an extension, it's a file
        // Otherwise, it's an icon from the library
        $path = pathinfo($iconNameAttribute);
        if ($path['extension'] != "") {
            // loop through candidates until a match was found:
            $mediaFile = mediaFN($iconNameAttribute);
            // May be an icon from the templates
            if (!file_exists($mediaFile)) {
                $mediaTplFile = tpl_incdir() . 'images/' . $iconNameAttribute;
                if (!file_exists($mediaTplFile)) {
                    // Trying to see if it's not in the template images directory
                    $message = "\n The media file could not be found in the media or template library. If you want an icon from the material design icon library, indicate a name without extension.";
                    $message .= "\n Media File Library tested: $mediaFile";
                    $message .= "\n Media Template Library tested: $mediaTplFile";
                    LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::NAME);
                    return false;
                } else {
                    $mediaFile = $mediaTplFile;
                }
            }

        } else {


            // It may be a icon already downloaded
            $iconNameSpace = ConfUtility::getConf(self::CONF_ICONS_MEDIA_NAMESPACE);
            $mediaId = $iconNameSpace . ":" . $iconNameAttribute . ".svg";
            $mediaFile = mediaFN($mediaId);

            // Bug: null file created when the stream could not get any byte
            // We delete them
            if (file_exists($mediaFile)){
                if (filesize($mediaFile)==0){
                    unlink($mediaFile);
                }
            }

            if (!file_exists($mediaFile)) {

                /**
                 * Download the icon
                 */

                // Create the target directory if it does not exist
                $pathInfo = pathinfo($mediaFile);
                $iconDir = $pathInfo['dirname'];
                if (!file_exists($iconDir)) {
                    $filePointer = mkdir($iconDir, $mode = 0770, $recursive = true);
                    if ($filePointer == false) {
                        LogUtility::msg("The icon directory ($iconDir) could not be created.", LogUtility::LVL_MSG_ERROR, self::NAME);
                        return false;
                    }
                }

                // Name parsing to extract the library name and icon name
                $sepPosition = strpos($iconNameAttribute, ":");
                $library = PluginUtility::getConfValue(self::CONF_DEFAULT_ICON_LIBRARY);
                $iconName = $iconNameAttribute;
                if ($sepPosition != false) {
                    $library = substr($iconNameAttribute, 0, $sepPosition);
                    $iconName = substr($iconNameAttribute, $sepPosition+1);
                }

                // Get the qualified library name
                $acronymLibraries = self::LIBRARY_ACRONYM;
                if (isset($acronymLibraries[$library])) {
                    $library = $acronymLibraries[$library];
                }

                // Get the url
                $iconLibraries = self::ICON_LIBRARY_URLS;
                if (!isset($iconLibraries[$library])) {
                    LogUtility::msg("The icon library ($library) is unknown. The icon could not be downloaded.", LogUtility::LVL_MSG_ERROR, self::NAME);
                    return false;
                } else {
                    $iconBaseUrl = $iconLibraries[$library];
                }

                // The url
                $downloadUrl = "$iconBaseUrl/$iconName.svg";
                $filePointer = @fopen($downloadUrl, 'r');
                if ($filePointer != false) {

                    $numberOfByte = @file_put_contents($mediaFile, $filePointer);
                    if ($numberOfByte != false) {
                        LogUtility::msg("The icon ($iconName) from the library ($library) was downloaded to ($mediaId)", LogUtility::LVL_MSG_INFO, self::NAME);
                    } else {
                        LogUtility::msg("Internal error: The icon ($iconName) from the library ($library) could no be written to ($mediaId)", LogUtility::LVL_MSG_ERROR, self::NAME);
                    }

                } else {

                    LogUtility::msg("The file ($downloadUrl) from the library ($library) does not exist", LogUtility::LVL_MSG_ERROR, self::NAME);

                }

            }

        }

        if (file_exists($mediaFile)) {

            return (new SvgFile($mediaFile))->getSvg($attributes);

        } else {
            return "";
        }


    }

    /**
     * @param $iconName
     * @param $mediaFile
     * @deprecated Old code to download icon from the material design api
     */
    public static function downloadIconFromMaterialDesignApi($iconName, $mediaFile)
    {
        // Try the official API
        // Read the icon meta of
        // Meta Json file got all icons
        //
        //   * Available at: https://raw.githubusercontent.com/Templarian/MaterialDesign/master/meta.json
        //   * See doc: https://github.com/Templarian/MaterialDesign-Site/blob/master/src/content/api.md)
        $arrayFormat = true;
        $iconMetaJson = json_decode(file_get_contents(__DIR__ . '/icon-meta.json'), $arrayFormat);
        $iconId = null;
        foreach ($iconMetaJson as $key => $value) {
            if ($value['name'] == $iconName) {
                $iconId = $value['id'];
                break;
            }
        }
        if ($iconId != null) {

            // Download
            // Call to the API
            // https://dev.materialdesignicons.com/contribute/site/api
            $downloadUrl = "https://materialdesignicons.com/api/download/icon/svg/$iconId";
            $filePointer = file_put_contents($mediaFile, fopen($downloadUrl, 'r'));
            if ($filePointer == false) {
                LogUtility::msg("The file ($downloadUrl) could not be downloaded to ($mediaFile)", LogUtility::LVL_MSG_ERROR, self::NAME);
            } else {
                LogUtility::msg("The material design icon ($iconName) was downloaded to ($mediaFile)", LogUtility::LVL_MSG_INFO, self::NAME);
            }

        }

    }


}
