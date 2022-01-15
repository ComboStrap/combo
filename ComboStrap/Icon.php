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
class Icon extends ImageSvg
{
    const CONF_ICONS_MEDIA_NAMESPACE = "icons_namespace";
    const CONF_ICONS_MEDIA_NAMESPACE_DEFAULT = ":" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME . ":icons";
    // Canonical name
    const NAME = "icon";


    const ICON_LIBRARY_URLS = array(
        self::BOOTSTRAP => "https://raw.githubusercontent.com/twbs/icons/main/icons",
        self::MATERIAL_DESIGN => "https://raw.githubusercontent.com/Templarian/MaterialDesign/master/svg",
        self::FEATHER => "https://raw.githubusercontent.com/feathericons/feather/master/icons",
        self::CODE_ICON => "https://raw.githubusercontent.com/microsoft/vscode-codicons/main/src/icons",
        self::LOGOS => "https://raw.githubusercontent.com/gilbarbara/logos/master/logos",
        self::CARBON => "https://raw.githubusercontent.com/carbon-design-system/carbon/main/packages/icons/src/svg/32",
        self::TWEET_EMOJI => "https://raw.githubusercontent.com/twitter/twemoji/master/assets/svg",
        self::ANT_DESIGN => "https://raw.githubusercontent.com/ant-design/ant-design-icons/master/packages/icons-svg/svg"
    );

    const ICON_LIBRARY_WEBSITE_URLS = array(
        self::BOOTSTRAP => "https://icons.getbootstrap.com/",
        self::MATERIAL_DESIGN => "https://materialdesignicons.com/",
        self::FEATHER => "https://feathericons.com/",
        self::CODE_ICON => "https://microsoft.github.io/vscode-codicons/",
        self::LOGOS => "https://svgporn.com/",
        self::CARBON => "https://www.carbondesignsystem.com/guidelines/icons/library/",
        self::TWEET_EMOJI => "https://twemoji.twitter.com/",
        self::ANT_DESIGN => "https://ant.design/components/icon/"
    );

    const CONF_DEFAULT_ICON_LIBRARY = "defaultIconLibrary";
    const CONF_DEFAULT_ICON_LIBRARY_DEFAULT = self::MATERIAL_DESIGN_ACRONYM;

    /**
     * Deprecated library acronym / name
     */
    const DEPRECATED_LIBRARY_ACRONYM = array(
        "bs" => self::BOOTSTRAP, // old one (deprecated) - the good acronym is bi (seen also in the class)
        "md" => self::MATERIAL_DESIGN
    );

    /**
     * Public known acronym / name (Used in the configuration)
     */
    const PUBLIC_LIBRARY_ACRONYM = array(
        "bi" => self::BOOTSTRAP,
        self::MATERIAL_DESIGN_ACRONYM => self::MATERIAL_DESIGN,
        "fe" => self::FEATHER,
        "codicon" => self::CODE_ICON,
        "logos" => self::LOGOS,
        "carbon" => self::CARBON,
        "twemoji" => self::TWEET_EMOJI,
        "ant-design" => self::ANT_DESIGN
    );

    const FEATHER = "feather";
    const BOOTSTRAP = "bootstrap";
    const MATERIAL_DESIGN = "material-design";
    const CODE_ICON = "codicon";
    const LOGOS = "logos";
    const CARBON = "carbon";
    const MATERIAL_DESIGN_ACRONYM = "mdi";
    const TWEET_EMOJI = "twemoji";
    const ANT_DESIGN = "ant-design";


    /**
     * The function used to render an icon
     * @param TagAttributes $tagAttributes -  the icon attributes
     * @return bool|mixed - false if any error or the HTML
     * @throws ExceptionCombo
     */
    static public function create(TagAttributes $tagAttributes): Icon
    {


        $name = "name";
        if (!$tagAttributes->hasComponentAttribute($name)) {
            LogUtility::msg("The attributes should have a name. It's mandatory for an icon.", LogUtility::LVL_MSG_ERROR, self::NAME);
            return false;
        }

        /**
         * The Name
         */
        $iconNameAttribute = $tagAttributes->getValue($name);

        /**
         * If the name have an extension, it's a file from the media directory
         * Otherwise, it's an icon from a library
         */
        $mediaDokuPath = DokuPath::createMediaPathFromId($iconNameAttribute);
        if (!empty($mediaDokuPath->getExtension())) {

            // loop through candidates until a match was found:
            // May be an icon from the templates
            if (!FileSystems::exists($mediaDokuPath)) {

                // Trying to see if it's not in the template images directory
                $message = "The media file could not be found in the media library. If you want an icon from an icon library, indicate a name without extension.";
                $message .= "<BR> Media File Library tested: $mediaDokuPath";
                LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::NAME);
                return false;

            }

        } else {

            // It may be a icon already downloaded
            $iconNameSpace = PluginUtility::getConfValue(self::CONF_ICONS_MEDIA_NAMESPACE, self::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT);
            if (substr($iconNameSpace, 0, 1) != DokuPath::PATH_SEPARATOR) {
                $iconNameSpace = DokuPath::PATH_SEPARATOR . $iconNameSpace;
            }
            if (substr($iconNameSpace, -1) != DokuPath::PATH_SEPARATOR) {
                $iconNameSpace = $iconNameSpace . ":";
            }
            $mediaPathId = $iconNameSpace . $iconNameAttribute . ".svg";
            $mediaDokuPath = DokuPath::createMediaPathFromAbsolutePath($mediaPathId);

            // Bug: null file created when the stream could not get any byte
            // We delete them
            if (FileSystems::exists($mediaDokuPath)) {
                if (FileSystems::getSize($mediaDokuPath) == 0) {
                    FileSystems::delete($mediaDokuPath);
                }
            }

            if (!FileSystems::exists($mediaDokuPath)) {

                /**
                 * Download the icon
                 */

                // Create the target directory if it does not exist
                $iconDir = $mediaDokuPath->getParent();
                if (!FileSystems::exists($iconDir)) {
                    try {
                        FileSystems::createDirectory($iconDir);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The icon directory ($iconDir) could not be created.", LogUtility::LVL_MSG_ERROR, self::NAME);
                        return false;
                    }
                }

                // Name parsing to extract the library name and icon name
                $sepPosition = strpos($iconNameAttribute, ":");
                $library = PluginUtility::getConfValue(self::CONF_DEFAULT_ICON_LIBRARY, self::CONF_DEFAULT_ICON_LIBRARY_DEFAULT);
                $iconName = $iconNameAttribute;
                if ($sepPosition != false) {
                    $library = substr($iconNameAttribute, 0, $sepPosition);
                    $iconName = substr($iconNameAttribute, $sepPosition + 1);
                }

                // Get the qualified library name
                $acronymLibraries = self::getLibraries();
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

                /**
                 * Name processing
                 */
                switch ($library) {

                    case self::TWEET_EMOJI:
                        try {
                            $iconName = self::getEmojiCodePoint($iconName);
                        } catch (ExceptionCombo $e) {
                            LogUtility::msg("The emoji name $iconName is unknown. The emoji could not be downloaded.", LogUtility::LVL_MSG_ERROR, self::NAME);
                            return false;
                        }
                        break;
                    case self::ANT_DESIGN:
                        // table-outlined where table is the svg, outline the category
                        $names = explode("-", $iconName);
                        if (sizeof($names) != 2) {
                            LogUtility::msg("We expect that a ant design icon name ($iconName) has two parts separated by a `-` (example: table-outlined). The icon could not be downloaded.", LogUtility::LVL_MSG_ERROR, self::NAME);
                            return false;
                        }
                        $iconName = $names[0];
                        $iconType = $names[1];
                        $iconBaseUrl .= "/$iconType";
                        break;
                    case self::CARBON:
                        $iconName = self::getCarbonPhysicalName($iconName);
                        break;
                }


                // The url
                $downloadUrl = "$iconBaseUrl/$iconName.svg";
                $filePointer = @fopen($downloadUrl, 'r');
                if ($filePointer != false) {

                    $numberOfByte = @file_put_contents($mediaDokuPath->toLocalPath()->toAbsolutePath()->toString(), $filePointer);
                    if ($numberOfByte != false) {
                        LogUtility::msg("The icon ($iconName) from the library ($library) was downloaded to ($mediaPathId)", LogUtility::LVL_MSG_INFO, self::NAME);
                    } else {
                        LogUtility::msg("Internal error: The icon ($iconName) from the library ($library) could no be written to ($mediaPathId)", LogUtility::LVL_MSG_ERROR, self::NAME);
                    }

                } else {

                    // (ie no icon file found at ($downloadUrl)
                    $urlLibrary = self::ICON_LIBRARY_WEBSITE_URLS[$library];
                    LogUtility::msg("The library (<a href=\"$urlLibrary\">$library</a>) does not have a icon (<a href=\"$downloadUrl\">$iconName</a>).", LogUtility::LVL_MSG_ERROR, self::NAME);

                }

            }

        }

        /**
         * After optimization, the width and height of the svg are gone
         * but the icon type set them again
         *
         * The icon type is used to set:
         *   * the default dimension
         *   * color styling
         *   * disable the responsive properties
         *
         */
        $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ICON_TYPE);

        return new Icon($mediaDokuPath, $tagAttributes);

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
        $iconMetaJson = json_decode(file_get_contents(__DIR__ . '/../resources/dictionary/icon-meta.json'), $arrayFormat);
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

    private static function getLibraries(): array
    {
        return array_merge(
            self::PUBLIC_LIBRARY_ACRONYM,
            self::DEPRECATED_LIBRARY_ACRONYM
        );
    }

    /**
     * @throws ExceptionCombo
     */
    public static function getEmojiCodePoint(string $emojiName)
    {
        $path = LocalPath::createFromPath(Resources::getDictionaryDirectory() . "/emojis.json");
        $jsonContent = FileSystems::getContent($path);
        $jsonArray = Json::createFromString($jsonContent)->toArray();
        return $jsonArray[$emojiName];
    }

    /**
     * Iconify normalized the name of the carbon library (making them lowercase)
     *
     * For instance, CSV is csv (https://icon-sets.iconify.design/carbon/csv/)
     *
     * This dictionary reproduce it.
     *
     * @param string $logicalName
     * @return mixed
     * @throws ExceptionCombo
     */
    private static function getCarbonPhysicalName(string $logicalName)
    {
        $path = LocalPath::createFromPath(Resources::getDictionaryDirectory() . "/carbon-icons.json");
        $jsonContent = FileSystems::getContent($path);
        $jsonArray = Json::createFromString($jsonContent)->toArray();
        $physicalName = $jsonArray[$logicalName];
        if($physicalName===null){
            LogUtility::msg("The icon ($logicalName) is unknown as 32x32 carbon icon");
            // by default, just lowercase
            return lower($logicalName);
        }
        return $physicalName;
    }


    public function render(): string
    {

        if (FileSystems::exists($this->getPath())) {

            $svgImageLink = SvgImageLink::createMediaLinkFromPath(
                $this->getPath(),
                $this->getAttributes()
            );
            return $svgImageLink->renderMediaTag();

        } else {

            LogUtility::msg("The icon ($this) does not exist and cannot be rendered.");
            return "";

        }

    }


}
