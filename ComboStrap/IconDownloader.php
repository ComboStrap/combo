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
 * Class Icon
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
class IconDownloader
{

    const CONF_ICONS_MEDIA_NAMESPACE = "icons_namespace";
    const CONF_ICONS_MEDIA_NAMESPACE_DEFAULT = ":" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME . ":icons";


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
        self::DASHICONS => "https://raw.githubusercontent.com/WordPress/dashicons/master/svg-min",
        self::ICONOIR => "https://raw.githubusercontent.com/lucaburgio/iconoir/master/icons",
        self::BOX_ICON => "https://raw.githubusercontent.com/atisawd/boxicons/master/svg",
        self::LINE_AWESOME => "https://raw.githubusercontent.com/icons8/line-awesome/master/svg",
        self::FONT_AWESOME_SOLID => "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/solid",
        self::FONT_AWESOME_BRANDS => "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/brands",
        self::FONT_AWESOME_REGULAR => "https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/svgs/regular",
        self::VAADIN => "https://raw.githubusercontent.com/vaadin/vaadin-icons/master/assets/svg",
        self::CORE_UI_BRAND => "https://raw.githubusercontent.com/coreui/coreui-icons/master/svg/brand",
        self::FLAT_COLOR_ICON => "https://raw.githubusercontent.com/icons8/flat-color-icons/master/svg",
        self::PHOSPHOR_ICONS => "https://raw.githubusercontent.com/phosphor-icons/phosphor-icons/master/assets",
        self::VSCODE => "https://raw.githubusercontent.com/vscode-icons/vscode-icons/master/icons",
        self::SI_GLYPH => "https://raw.githubusercontent.com/frexy/glyph-iconset/master/svg",
        self::AKAR_ICONS => "https://raw.githubusercontent.com/artcoholic/akar-icons/master/src/svg",
        self::ARCTICONS => "https://raw.githubusercontent.com/Donnnno/Arcticons/main/icons/black",
        self::HEALTH_ICONS => "https://raw.githubusercontent.com/resolvetosavelives/healthicons/main/public/icons/svg"
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
        self::DASHICONS => "https://developer.wordpress.org/resource/dashicons/",
        self::ICONOIR => "https://iconoir.com",
        self::BOX_ICON => "https://boxicons.com",
        self::LINE_AWESOME => "https://icons8.com/line-awesome",
        self::FONT_AWESOME => "https://fontawesome.com/",
        self::FONT_AWESOME_SOLID => "https://fontawesome.com/",
        self::FONT_AWESOME_BRANDS => "https://fontawesome.com/",
        self::FONT_AWESOME_REGULAR => "https://fontawesome.com/",
        self::VAADIN => "https://vaadin.com/icons",
        self::CORE_UI_BRAND => "https://coreui.io/icons/",
        self::FLAT_COLOR_ICON => "https://icons8.com/icons/color",
        self::PHOSPHOR_ICONS => "https://phosphoricons.com/",
        self::VSCODE => "https://marketplace.visualstudio.com/items?itemName=vscode-icons-team.vscode-icons",
        self::SI_GLYPH => "https://glyph.smarticons.co/",
        self::AKAR_ICONS => "https://akaricons.com/",
        self::ARCTICONS => "https://arcticons.com/",
        self::HEALTH_ICONS => "https://healthicons.org/",
        self::MATERIAL_DESIGN_ACRONYM => "https://materialdesignicons.com/",
        self::COMBO => ""
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
        "simple-line-icons" => self::SIMPLE_LINE,
        "icomoon-free" => self::ICOMOON,
        "dashicons" => self::DASHICONS,
        "iconoir" => self::ICONOIR,
        "bx" => self::BOX_ICON,
        "la" => self::LINE_AWESOME,
        "fa-solid" => self::FONT_AWESOME_SOLID,
        "fa-brands" => self::FONT_AWESOME_BRANDS,
        "fa-regular" => self::FONT_AWESOME_REGULAR,
        "vaadin" => self::VAADIN,
        "cib" => self::CORE_UI_BRAND,
        "flat-color-icons" => self::FLAT_COLOR_ICON,
        "ph" => self::PHOSPHOR_ICONS,
        "vscode-icons" => self::VSCODE,
        "si-glyph" => self::SI_GLYPH,
        "akar-icons" => self::AKAR_ICONS,
        "arcticons" => self::ARCTICONS,
        "healthicons" => self::HEALTH_ICONS,
        "combo" => self::COMBO
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
    const ICONOIR = "iconoir";
    const BOX_ICON = "box-icon";
    const LINE_AWESOME = "line-awesome";
    const FONT_AWESOME_SOLID = "font-awesome-solid";
    const FONT_AWESOME_BRANDS = "font-awesome-brands";
    const FONT_AWESOME_REGULAR = "font-awesome-regular";
    const FONT_AWESOME = "font-awesome";
    const VAADIN = "vaadin";
    const CORE_UI_BRAND = "cib";
    const FLAT_COLOR_ICON = "flat-color-icons";
    const PHOSPHOR_ICONS = "ph";
    const VSCODE = "vscode";
    const SI_GLYPH = "si-glyph";
    const COMBO = WikiPath::COMBO_DRIVE;
    const AKAR_ICONS = "akar-icons";
    const ARCTICONS = "articons";
    const HEALTH_ICONS = "healthicons";


    /**
     * The icon library
     * @var mixed|null
     */
    private $library;
    /**
     * @var false|string
     */
    private $iconName;
    private WikiPath $path;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct(string $name)
    {

        $iconNameSpace = SiteConfig::getConfValue(self::CONF_ICONS_MEDIA_NAMESPACE, self::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT);
        if (substr($iconNameSpace, 0, 1) != WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $iconNameSpace = WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $iconNameSpace;
        }
        if (substr($iconNameSpace, -1) != WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $iconNameSpace = $iconNameSpace . ":";
        }
        $mediaPathId = $iconNameSpace . $name . ".svg";;
        $this->path = WikiPath::createMediaPathFromPath($mediaPathId);
        // Bug: null file created when the stream could not get any byte
        // We delete them
        if (FileSystems::exists($this->path)) {
            if (FileSystems::getSize($this->path) === 0) {
                FileSystems::delete($this->path);
            }
        }

        /**
         * Name parsing to extract the library name and icon name
         */
        // default
        $confValue = SiteConfig::getConfValue(self::CONF_DEFAULT_ICON_LIBRARY, self::CONF_DEFAULT_ICON_LIBRARY_DEFAULT);
        $this->setLibrary($confValue);
        $this->setIconName($name);
        // parse
        $sepPosition = strpos($name, ":");
        if ($sepPosition != false) {
            $libraryName = substr($name, 0, $sepPosition);
            $this->setLibrary($libraryName);
            $iconName = substr($name, $sepPosition + 1);
            $this->setIconName($iconName);

            /**
             * Special case, internal library
             */
            if ($this->getLibrary() === self::COMBO) {
                $this->path = WikiPath::createComboResource($iconName . ".svg");
            }
        }


    }


    public static
    function isInIconDirectory(Path $path): bool
    {
        $iconNameSpace = SiteConfig::getConfValue(IconDownloader::CONF_ICONS_MEDIA_NAMESPACE, IconDownloader::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT);
        if (strpos($path->toQualifiedPath(), $iconNameSpace) !== false) {
            return true;
        }
        return false;
    }


    /**
     * @throws ExceptionBadArgument - if the icon library is not supported
     */
    public static function createFromName(string $name): IconDownloader
    {
        return new IconDownloader($name);
    }

    /**
     * @throws ExceptionCompile
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

    public function getIconName(): string
    {
        return $this->iconName;
    }

    /**
     * @throws ExceptionCompile
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
            throw new ExceptionCompile("The icon library ($library) is unknown. The icon could not be downloaded.", Icon::ICON_CANONICAL_NAME);
        } else {
            $iconBaseUrl = $iconLibraries[$library];
        }

        /**
         * Name processing
         */
        $iconName = $this->iconName;
        switch ($library) {

            case self::VSCODE:
            case self::FLAT_COLOR_ICON:
                $iconName = str_replace("-", "_", $iconName);
                break;
            case self::TWEET_EMOJI:
                try {
                    $iconName = self::getEmojiCodePoint($iconName);
                } catch (ExceptionCompile $e) {
                    throw new ExceptionCompile("The emoji name $iconName is unknown. The emoji could not be downloaded.", Icon::ICON_CANONICAL_NAME, 0, $e);
                }
                break;
            case self::ANT_DESIGN:
                // table-outlined where table is the svg, outlined the category
                // ordered-list-outlined where ordered-list is the svg, outlined the category
                [$iconName, $iconType] = self::explodeInTwoPartsByLastPosition($iconName, "-");
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
                $iconName = self::getPhysicalNameFromDictionary($iconName, self::CARBON);
                break;
            case self::FAD:
                $iconName = self::getPhysicalNameFromDictionary($iconName, self::FAD);
                break;
            case self::ICOMOON:
                $iconName = self::getPhysicalNameFromDictionary($iconName, self::ICOMOON);
                break;
            case self::CORE_UI_BRAND:
                $iconName = self::getPhysicalNameFromDictionary($iconName, self::CORE_UI_BRAND);
                break;
            case self::EVA:
                // Eva
                // example: eva:facebook-fill
                [$iconName, $iconType] = self::explodeInTwoPartsByLastPosition($iconName, "-");
                $iconBaseUrl .= "/$iconType/svg";
                if ($iconType === "outline") {
                    // for whatever reason, the name of outline icon has outline at the end
                    // and not for the fill icon
                    $iconName .= "-$iconType";
                }
                break;
            case self::PHOSPHOR_ICONS:
                // example: activity-light
                [$iconShortName, $iconType] = self::explodeInTwoPartsByLastPosition($iconName, "-");
                $iconBaseUrl .= "/$iconType";
                break;
            case self::SIMPLE_LINE:
                // Bug
                if ($iconName === "social-pinterest") {
                    $iconName = "social-pintarest";
                }
                break;
            case self::BOX_ICON:
                [$iconType, $extractedIconName] = self::explodeInTwoPartsByLastPosition($iconName, "-");
                switch ($iconType) {
                    case "bxl":
                        $iconBaseUrl .= "/logos";
                        break;
                    case "bx":
                        $iconBaseUrl .= "/regular";
                        break;
                    case "bxs":
                        $iconBaseUrl .= "/solid";
                        break;
                    default:
                        throw new ExceptionCompile("The box-icon icon ($iconName) has a type ($iconType) that is unknown, we can't determine the location of the icon to download");
                }
                break;
            case self::SI_GLYPH:
                $iconName = "si-glyph-" . $iconName;
                break;
            case self::HEALTH_ICONS:
                [$extractedIconName, $iconType] = self::explodeInTwoPartsByLastPosition($iconName, "-");
                switch ($iconType) {
                    case "outline":
                    case "negative":
                        $iconBaseUrl .= "/$iconType";
                        $iconName = $extractedIconName;
                        break;
                    default:
                        // no
                        $iconBaseUrl .= "/filled";
                }
                $iconName = self::getPhysicalNameFromDictionary($iconName, self::HEALTH_ICONS);
                break;

        }


        // The url
        return "$iconBaseUrl/$iconName.svg";

    }

    /**
     * @throws ExceptionCompile
     */
    public function download()
    {

        $mediaDokuPath = $this->path;
        $library = $this->getLibrary();

        /**
         * Create the target directory if it does not exist
         */
        $iconDir = $mediaDokuPath->getParent();
        if (!FileSystems::exists($iconDir)) {
            try {
                FileSystems::createDirectory($iconDir);
            } catch (ExceptionCompile $e) {
                throw new ExceptionCompile("The icon directory ($iconDir) could not be created.", Icon::ICON_CANONICAL_NAME, 0, $e);
            }
        }

        /**
         * Download the icon
         * The `@` delete the E_WARNING upon failure
         *
         * https://www.php.net/manual/en/function.fopen.php
         */
        $downloadUrl = $this->getDownloadUrl();
        ErrorHandler::phpErrorAsException();
        try {
            $filePointer = fopen($downloadUrl, 'r');
        } catch (ExceptionPhpError $e) {
            // (ie no icon file found at ($downloadUrl)
            $message = "We couldn't find the <a href=\"$downloadUrl\">icon $this->iconName</a>) from the";
            try {
                $urlLibrary = $this->getLibraryUrl();
                $message = "$message <a href=\"$urlLibrary\">library $library</a>";
            } catch (ExceptionNotFound $e) {
                if (PluginUtility::isDevOrTest()) {
                    throw $e;
                }
                $message = "$message library $library";
            }
            $message = "$message. Error: {$e->getMessage()}";
            throw new ExceptionCompile($message, Icon::ICON_CANONICAL_NAME);
        } finally {
            ErrorHandler::restore();
        }

        $numberOfByte = file_put_contents($mediaDokuPath->toLocalPath()->toAbsolutePath()->toQualifiedPath(), $filePointer);
        if ($numberOfByte != false) {
            LogUtility::msg("The icon ($this) from the library ($library) was downloaded to ($mediaDokuPath)", LogUtility::LVL_MSG_INFO, Icon::ICON_CANONICAL_NAME);
        } else {
            LogUtility::msg("Internal error: The icon ($this) from the library ($library) could no be written to ($mediaDokuPath)", LogUtility::LVL_MSG_ERROR, Icon::ICON_CANONICAL_NAME);
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
                LogUtility::msg("The file ($downloadUrl) could not be downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_ERROR, Icon::ICON_CANONICAL_NAME);
            } else {
                LogUtility::msg("The material design icon ($iconName) was downloaded to ($mediaFilePath)", LogUtility::LVL_MSG_INFO, Icon::ICON_CANONICAL_NAME);
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
     * @throws ExceptionCompile
     */
    public static function getEmojiCodePoint(string $emojiName)
    {
        $path = DirectoryLayout::getComboDictionaryDirectory()->resolve("emojis.json");
        $jsonContent = FileSystems::getContent($path);
        $jsonArray = Json::createFromString($jsonContent)->toArray();
        return $jsonArray[$emojiName];
    }


    /**
     * @param string $iconName
     * @param string $sep
     * @return array
     * @throws ExceptionCompile
     */
    private static function explodeInTwoPartsByLastPosition(string $iconName, string $sep = "-"): array
    {
        $index = strrpos($iconName, $sep);
        if ($index === false) {
            throw new ExceptionCompile ("We expect that the icon name ($iconName) has two parts separated by a `-` (example: table-outlined). The icon could not be downloaded.", Icon::ICON_CANONICAL_NAME);
        }
        $firstPart = substr($iconName, 0, $index);
        $secondPart = substr($iconName, $index + 1);
        return [$firstPart, $secondPart];
    }


    public function __toString()
    {
        return $this->getIconName();
    }

    private function getLibrary()
    {
        return $this->library;
    }


    /**
     * @throws ExceptionBadArgument
     */
    private function setLibrary($libraryName)
    {
        if (!in_array($libraryName, array_keys($this->getLibraries()))) {
            throw new ExceptionBadArgument("The library ($libraryName) is not a icon library supported");
        }
        $this->library = $libraryName;
    }

    private function setIconName(string $iconName)
    {
        $this->iconName = $iconName;
    }

    public function getPath(): WikiPath
    {
        return $this->path;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getLibraryUrl()
    {
        $library = $this->getLibrary();
        $libraryUrl = @self::ICON_LIBRARY_WEBSITE_URLS[$library];
        if ($libraryUrl === null) {
            throw new ExceptionNotFound("The url for the library ($library) was not found");
        }
        return $libraryUrl;
    }


}
