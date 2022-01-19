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
    const ICON_CANONICAL_NAME = "icon";


    const ICON_LIBRARY_URLS = array(
        self::ANT_DESIGN => "https://raw.githubusercontent.com/ant-design/ant-design-icons/master/packages/icons-svg/svg",
        self::BOOTSTRAP => "https://raw.githubusercontent.com/twbs/icons/main/icons",
        self::CARBON => "https://raw.githubusercontent.com/carbon-design-system/carbon/main/packages/icons/src/svg/32",
        self::CLARITY => "https://raw.githubusercontent.com/vmware/clarity-assets/master/icons/essential",
        self::CODE_ICON => "https://raw.githubusercontent.com/microsoft/vscode-codicons/main/src/icons",
        self::ELEGANT_THEME => "https://raw.githubusercontent.com/pprince/etlinefont-bower/master/images/svg/individual_icons",
        self::ENTYPO => "https://raw.githubusercontent.com/hypermodules/entypo/master/src/Entypo",
        self::ENTYPO_SOCIAL => "https://raw.githubusercontent.com/hypermodules/entypo/master/src/Entypo%20Social%20Extension",
        self::EVA => "https://raw.githubusercontent.com/akveo/eva-icons/master/package/icons",
        self::FEATHER => "https://raw.githubusercontent.com/feathericons/feather/master/icons",
        self::FAD => "https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs",
        self::ICONSCOUT => "https://raw.githubusercontent.com/Iconscout/unicons/master/svg/line",
        self::LOGOS => "https://raw.githubusercontent.com/gilbarbara/logos/master/logos",
        self::MATERIAL_DESIGN => "https://raw.githubusercontent.com/Templarian/MaterialDesign/master/svg",
        self::OCTICON => "https://raw.githubusercontent.com/primer/octicons/main/icons",
        self::TWEET_EMOJI => "https://raw.githubusercontent.com/twitter/twemoji/master/assets/svg",
        self::SIMPLE_LINE => "https://raw.githubusercontent.com/thesabbir/simple-line-icons/master/src/svgs",
        self::ICOMOON => "https://raw.githubusercontent.com/Keyamoon/IcoMoon-Free/master/SVG",
        self::DASHICONS => "https://raw.githubusercontent.com/WordPress/dashicons/master/svg-min"
    );

    const ICON_LIBRARY_WEBSITE_URLS = array(
        self::BOOTSTRAP => "https://icons.getbootstrap.com/",
        self::MATERIAL_DESIGN => "https://materialdesignicons.com/",
        self::FEATHER => "https://feathericons.com/",
        self::CODE_ICON => "https://microsoft.github.io/vscode-codicons/",
        self::LOGOS => "https://svgporn.com/",
        self::CARBON => "https://www.carbondesignsystem.com/guidelines/icons/library/",
        self::TWEET_EMOJI => "https://twemoji.twitter.com/",
        self::ANT_DESIGN => "https://ant.design/components/icon/",
        self::CLARITY => "https://clarity.design/foundation/icons/",
        self::OCTICON => "https://primer.style/octicons/",
        self::ICONSCOUT => "https://iconscout.com/unicons/explore/line",
        self::ELEGANT_THEME => "https://github.com/pprince/etlinefont-bower",
        self::EVA => "https://akveo.github.io/eva-icons/",
        self::ENTYPO_SOCIAL => "http://www.entypo.com",
        self::ENTYPO => "http://www.entypo.com",
        self::SIMPLE_LINE => "https://thesabbir.github.io/simple-line-icons",
        self::ICOMOON => "https://icomoon.io/",
        self::DASHICONS => "https://developer.wordpress.org/resource/dashicons/"

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
        "ant-design" => self::ANT_DESIGN,
        "fad" => self::FAD,
        "clarity" => self::CLARITY,
        "octicon" => self::OCTICON,
        "uit" => self::ICONSCOUT,
        "et" => self::ELEGANT_THEME,
        "eva" => self::EVA,
        "entypo-social" => self::ENTYPO_SOCIAL,
        "entypo" => self::ENTYPO,
        "simple-line-icons"=> self::SIMPLE_LINE,
        "icomoon-free" => self::ICOMOON,
        "dashicons" => self::DASHICONS
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
    const FAD = "fad";
    const CLARITY = "clarity";
    const OCTICON = "octicon";
    const ICONSCOUT = "iconscout";
    const ELEGANT_THEME = "elegant-theme";
    const EVA = "eva";
    const ENTYPO_SOCIAL = "entypo-social";
    const ENTYPO = "entypo";
    const SIMPLE_LINE = "simple-line";
    const ICOMOON = "icomoon";
    const DASHICONS = " dashicons";

    private $fullQualifiedName;
    /**
     * The icon library
     * @var mixed|null
     */
    private $library;
    /**
     * @var false|string
     */
    private $iconName;

    /**
     * Icon constructor.
     * @throws ExceptionCombo
     * @var string $fullQualifiedName - generally a short icon name (but it may be media id)
     */
    public function __construct($fullQualifiedName, $tagAttributes = null)
    {

        $this->fullQualifiedName = $fullQualifiedName;

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
        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty();
        }
        $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ICON_TYPE);

        /**
         * If the name have an extension, it's a file from the media directory
         * Otherwise, it's an icon from a library
         */
        $mediaDokuPath = DokuPath::createMediaPathFromId($fullQualifiedName);
        if (!empty($mediaDokuPath->getExtension())) {

            // loop through candidates until a match was found:
            // May be an icon from the templates
            if (!FileSystems::exists($mediaDokuPath)) {

                // Trying to see if it's not in the template images directory
                $message = "The media file could not be found in the media library. If you want an icon from an icon library, indicate a name without extension.";
                $message .= "<BR> Media File Library tested: $mediaDokuPath";
                throw new ExceptionCombo($message, self::ICON_CANONICAL_NAME);


            }

            parent::__construct($mediaDokuPath, $tagAttributes);
            return;

        }

        /**
         * From the icon library
         */
        $iconNameSpace = PluginUtility::getConfValue(self::CONF_ICONS_MEDIA_NAMESPACE, self::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT);
        if (substr($iconNameSpace, 0, 1) != DokuPath::PATH_SEPARATOR) {
            $iconNameSpace = DokuPath::PATH_SEPARATOR . $iconNameSpace;
        }
        if (substr($iconNameSpace, -1) != DokuPath::PATH_SEPARATOR) {
            $iconNameSpace = $iconNameSpace . ":";
        }
        $mediaPathId = $iconNameSpace . $fullQualifiedName . ".svg";
        $mediaDokuPath = DokuPath::createMediaPathFromAbsolutePath($mediaPathId);

        // Bug: null file created when the stream could not get any byte
        // We delete them
        if (FileSystems::exists($mediaDokuPath)) {
            if (FileSystems::getSize($mediaDokuPath) == 0) {
                FileSystems::delete($mediaDokuPath);
            }
        }

        /**
         * Name parsing to extract the library name and icon name
         */
        // default
        $this->library = PluginUtility::getConfValue(self::CONF_DEFAULT_ICON_LIBRARY, self::CONF_DEFAULT_ICON_LIBRARY_DEFAULT);
        $this->iconName = $this->fullQualifiedName;
        // parse
        $sepPosition = strpos($this->fullQualifiedName, ":");
        if ($sepPosition != false) {
            $this->library = substr($this->fullQualifiedName, 0, $sepPosition);
            $this->iconName = substr($this->fullQualifiedName, $sepPosition + 1);
        }

        parent::__construct($mediaDokuPath, $tagAttributes);

    }


    /**
     * The function used to render an icon
     * @param string $name - icon name
     * @param TagAttributes|null $tagAttributes -  the icon attributes
     * @return Icon
     * @throws ExceptionCombo
     */
    static public function create(string $name, TagAttributes $tagAttributes = null): Icon
    {

        return new Icon($name, $tagAttributes);

    }

    /**
     * @throws ExceptionCombo
     */
    private static function getPhysicalNameFromDictionary(string $logicalName, string $library)
    {

        $jsonArray = Dictionary::getFrom("$library-icons");
        $physicalName = $jsonArray[$logicalName];
        if ($physicalName === null) {
            LogUtility::msg("The icon ($logicalName) is unknown for the library ($library)");
            // by default, just lowercase
            return strtolower($logicalName);
        }
        return $physicalName;

    }

    public function getFullQualifiedName(): string
    {
        return $this->fullQualifiedName;
    }

    /**
     * @throws ExceptionCombo
     */
    public function getDownloadUrl(): string
    {


        // Get the qualified library name
        $library = $this->library;
        $acronymLibraries = self::getLibraries();
        if (isset($acronymLibraries[$library])) {
            $library = $acronymLibraries[$library];
        }

        // Get the url
        $iconLibraries = self::ICON_LIBRARY_URLS;
        if (!isset($iconLibraries[$library])) {
            throw new ExceptionCombo("The icon library ($library) is unknown. The icon could not be downloaded.", self::ICON_CANONICAL_NAME);
        } else {
            $iconBaseUrl = $iconLibraries[$library];
        }

        /**
         * Name processing
         */
        $iconName = $this->iconName;
        switch ($library) {

            case self::TWEET_EMOJI:
                try {
                    $iconName = self::getEmojiCodePoint($iconName);
                } catch (ExceptionCombo $e) {
                    throw new ExceptionCombo("The emoji name $iconName is unknown. The emoji could not be downloaded.", self::ICON_CANONICAL_NAME, 0, $e);
                }
                break;
            case self::ANT_DESIGN:
                // table-outlined where table is the svg, outlined the category
                // ordered-list-outlined where ordered-list is the svg, outlined the category
                [$iconName, $iconType] = self::explodeIconNameAndType($iconName, "-");
                $iconBaseUrl .= "/$iconType";
                break;
            case self::CARBON:
                /**
                 * Iconify normalized the name of the carbon library (making them lowercase)
                 *
                 * For instance, CSV is csv (https://icon-sets.iconify.design/carbon/csv/)
                 *
                 * This dictionary reproduce it.
                 */
                $iconName = self::getPhysicalNameFromDictionary($iconName,self::CARBON);
                break;
            case self::FAD:
                $iconName = self::getPhysicalNameFromDictionary($iconName,self::FAD);
                break;
            case self::ICOMOON:
                $iconName = self::getPhysicalNameFromDictionary($iconName,self::ICOMOON);
                break;
            case self::EVA:
                // Eva
                // example: eva:facebook-fill
                [$iconName, $iconType] = self::explodeIconNameAndType($iconName, "-");
                $iconBaseUrl .= "/$iconType/svg";
                break;

            case self::SIMPLE_LINE:
                // Bug
                if($iconName==="social-pinterest"){
                    $iconName = "social-pintarest";
                }
                break;
        }


        // The url
        return "$iconBaseUrl/$iconName.svg";

    }

    /**
     * @throws ExceptionCombo
     */
    public function download()
    {

        $mediaDokuPath = $this->getPath();
        if(!($mediaDokuPath instanceof DokuPath)){
            throw new ExceptionCombo("The icon path ($mediaDokuPath) is not a wiki path. This is not yet supported");
        }
        $library = $this->getLibrary();

        /**
         * Create the target directory if it does not exist
         */
        $iconDir = $mediaDokuPath->getParent();
        if (!FileSystems::exists($iconDir)) {
            try {
                FileSystems::createDirectory($iconDir);
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The icon directory ($iconDir) could not be created.", self::ICON_CANONICAL_NAME, 0, $e);
            }
        }

        /**
         * Download the icon
         */
        $downloadUrl = $this->getDownloadUrl();
        $filePointer = @fopen($downloadUrl, 'r');
        if ($filePointer == false) {
            // (ie no icon file found at ($downloadUrl)
            $urlLibrary = self::ICON_LIBRARY_WEBSITE_URLS[$library];
            throw new ExceptionCombo("The library (<a href=\"$urlLibrary\">$library</a>) does not have a icon (<a href=\"$downloadUrl\">$this->iconName</a>).", LogUtility::LVL_MSG_ERROR, self::ICON_CANONICAL_NAME);
        }

        $numberOfByte = @file_put_contents($mediaDokuPath->toLocalPath()->toAbsolutePath()->toString(), $filePointer);
        if ($numberOfByte != false) {
            LogUtility::msg("The icon ($this) from the library ($library) was downloaded to ($mediaDokuPath)", LogUtility::LVL_MSG_INFO, self::ICON_CANONICAL_NAME);
        } else {
            LogUtility::msg("Internal error: The icon ($this) from the library ($library) could no be written to ($mediaDokuPath)", LogUtility::LVL_MSG_ERROR, self::ICON_CANONICAL_NAME);
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
                LogUtility::msg("The file ($downloadUrl) could not be downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_ERROR, self::ICON_CANONICAL_NAME);
            } else {
                LogUtility::msg("The material design icon ($iconName) was downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_INFO, self::ICON_CANONICAL_NAME);
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
     * @param string $iconName
     * @param string $sep
     * @return array
     * @throws ExceptionCombo
     */
    private static function explodeIconNameAndType(string $iconName, string $sep = "-"): array
    {
        $index = strrpos($iconName, $sep);
        if ($index === false) {
            throw new ExceptionCombo ("We expect that the icon name ($iconName) has two parts separated by a `-` (example: table-outlined). The icon could not be downloaded.", self::ICON_CANONICAL_NAME);
        }
        $extractedIconName = substr($iconName, 0, $index);
        $iconType = substr($iconName, $index + 1);
        return [$extractedIconName, $iconType];
    }


    /**
     * @throws ExceptionCombo
     */
    public function render(): string
    {

        if (!FileSystems::exists($this->getPath())) {
            try {
                $this->download();
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The icon ($this) does not exist and could not be downloaded ({$e->getMessage()}.", self::ICON_CANONICAL_NAME);
            }
        }

        $svgImageLink = SvgImageLink::createMediaLinkFromPath(
            $this->getPath(),
            $this->getAttributes()
        );
        return $svgImageLink->renderMediaTag();


    }

    public function __toString()
    {
        return $this->getFullQualifiedName();
    }

    private function getLibrary()
    {
        return $this->library;
    }


}
