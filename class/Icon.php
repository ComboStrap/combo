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

require_once(__DIR__ . '/PluginUtility.php');
require_once(__DIR__ . '/ConfUtility.php');
require_once(__DIR__ . '/SvgImageLink.php');


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
class Icon
{
    const CONF_ICONS_MEDIA_NAMESPACE = "icons_namespace";
    const CONF_ICONS_MEDIA_NAMESPACE_DEFAULT = ":" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME . ":icons";
    // Canonical name
    const NAME = "icon";


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
     * @param TagAttributes $tagAttributes -  the icon attributes
     * @return bool|mixed - false if any error or the HTML
     */
    static public function renderIconByAttributes($tagAttributes)
    {


        $name = "name";
        if (!$tagAttributes->hasComponentAttribute($name)) {
            LogUtility::msg("The attributes should have a name. It's mandatory for an icon.", LogUtility::LVL_MSG_ERROR, self::NAME);
            return false;
        }

        /**
         * The Name
         */
        $iconNameAttribute = $tagAttributes->getValueAndRemove($name);

        /**
         * If the name have an extension, it's a file from the media directory
         * Otherwise, it's an icon from a library
         */
        $mediaDokuPath = DokuPath::createMediaPathFromId($iconNameAttribute);
        if (!empty($mediaDokuPath->getExtension())) {

            // loop through candidates until a match was found:
            // May be an icon from the templates
            if (!$mediaDokuPath->exists()) {

                // Trying to see if it's not in the template images directory
                $message = "The media file could not be found in the media library. If you want an icon from an icon library, indicate a name without extension.";
                $message .= "<BR> Media File Library tested: $mediaDokuPath";
                LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::NAME);
                return false;

            }

        } else {

            // It may be a icon already downloaded
            $iconNameSpace = ConfUtility::getConf(self::CONF_ICONS_MEDIA_NAMESPACE);
            $mediaId = $iconNameSpace . ":" . $iconNameAttribute . ".svg";
            $mediaDokuPath = DokuPath::createMediaPathFromId($mediaId);

            // Bug: null file created when the stream could not get any byte
            // We delete them
            if ($mediaDokuPath->exists()) {
                if ($mediaDokuPath->getSize() == 0) {
                    $mediaDokuPath->remove();
                }
            }

            if (!$mediaDokuPath->exists()) {

                /**
                 * Download the icon
                 */

                // Create the target directory if it does not exist
                $iconDir = $mediaDokuPath->getParent();
                if (!$iconDir->exists()) {
                    $filePointer = $iconDir->createAsDirectory();
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
                    $iconName = substr($iconNameAttribute, $sepPosition + 1);
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

                    $numberOfByte = @file_put_contents($mediaDokuPath->getPath(), $filePointer);
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

        if ($mediaDokuPath->exists()) {

            /**
             * Dimension
             * After optimization, the width and height of the svg are gone
             * but we set them on the style attribute
             */
            if (!$tagAttributes->hasComponentAttribute("width")) {
                $tagAttributes->addComponentAttributeValue("width", "24px");
            }
            if (!$tagAttributes->hasComponentAttribute("height")) {
                $tagAttributes->addComponentAttributeValue("height", "24px");
            }
            /**
             * The icon type is used to set the color styling
             * and responsive properties
             */
            if (!$tagAttributes->hasComponentAttribute("type")) {
                $tagAttributes->addComponentAttributeValue("type", SvgDocument::ICON_TYPE);
            }

            $svgImageLink = SvgImageLink::createFromId($mediaDokuPath->getId());
            return $svgImageLink->renderMediaTag($tagAttributes);

        } else {

            return "";

        }


    }

    /**
     * @param $iconName
     * @param $mediaFilePath
     * @deprecated Old code to download icon from the material design api
     */
    public
    static function downloadIconFromMaterialDesignApi($iconName, $mediaFilePath)
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
            $filePointer = file_put_contents($mediaFilePath, fopen($downloadUrl, 'r'));
            if ($filePointer == false) {
                LogUtility::msg("The file ($downloadUrl) could not be downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_ERROR, self::NAME);
            } else {
                LogUtility::msg("The material design icon ($iconName) was downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_INFO, self::NAME);
            }

        }

    }


}
