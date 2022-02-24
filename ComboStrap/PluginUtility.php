<?php


namespace ComboStrap;


use dokuwiki\Extension\Plugin;
use dokuwiki\Extension\SyntaxPlugin;
use PHPUnit\Exception;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Parent in th hierarchy should be first
 * Ie before {@link ImageLink, SvgImageLink, RasterImageLink)
 */
require_once(__DIR__ . '/CachedDocument.php');
require_once(__DIR__ . '/PageCompilerDocument.php');
require_once(__DIR__ . '/OutputDocument.php');
require_once(__DIR__ . '/FileSystem.php');
require_once(__DIR__ . '/Path.php');
require_once(__DIR__ . '/PathAbs.php');
require_once(__DIR__ . '/File.php');
require_once(__DIR__ . '/DokuFs.php');
require_once(__DIR__ . '/DokuPath.php');
require_once(__DIR__ . '/ResourceCombo.php');
require_once(__DIR__ . '/ResourceComboAbs.php');
require_once(__DIR__ . '/Media.php');
require_once(__DIR__ . '/MediaLink.php');
require_once(__DIR__ . '/Metadata.php');
require_once(__DIR__ . '/MetadataBoolean.php');
require_once(__DIR__ . '/MetadataDateTime.php');
require_once(__DIR__ . '/MetadataMultiple.php');
require_once(__DIR__ . '/MetadataTabular.php');
require_once(__DIR__ . '/MetadataText.php');
require_once(__DIR__ . '/MetadataJson.php');
require_once(__DIR__ . '/MetadataWikiPath.php');
require_once(__DIR__ . '/MetadataStore.php');
require_once(__DIR__ . '/MetadataStoreAbs.php');
require_once(__DIR__ . '/MetadataSingleArrayStore.php');
require_once(__DIR__ . '/XmlDocument.php');

/**
 * Plugin Utility is added in all Dokuwiki extension
 * and
 * all classes are added in plugin utility
 *
 * This is an utility master and the class loader
 *
 * If the load is relative, the load path is used
 * and the bad php file may be loaded
 * Furthermore, the absolute path helps
 * the IDE when refactoring
 */
require_once(__DIR__ . '/AdsUtility.php');
require_once(__DIR__ . '/Alias.php');
require_once(__DIR__ . '/AliasPath.php');
require_once(__DIR__ . '/AliasType.php');
require_once(__DIR__ . '/Aliases.php');
require_once(__DIR__ . '/Align.php');
require_once(__DIR__ . '/AnalyticsDocument.php');
require_once(__DIR__ . '/AnalyticsMenuItem.php');
require_once(__DIR__ . '/Animation.php');
require_once(__DIR__ . '/ArrayCaseInsensitive.php');
require_once(__DIR__ . '/ArrayUtility.php');
require_once(__DIR__ . '/Background.php');
require_once(__DIR__ . '/BacklinkCount.php');
require_once(__DIR__ . '/BacklinkMenuItem.php');
require_once(__DIR__ . '/Boldness.php');
require_once(__DIR__ . '/Boolean.php');
require_once(__DIR__ . '/Bootstrap.php');
require_once(__DIR__ . '/Brand.php');
require_once(__DIR__ . '/BrandButton.php');
require_once(__DIR__ . '/CacheDependencies.php');
require_once(__DIR__ . '/CacheExpirationDate.php');
require_once(__DIR__ . '/CacheExpirationFrequency.php');
require_once(__DIR__ . '/CacheLog.php');
require_once(__DIR__ . '/CacheManager.php');
require_once(__DIR__ . '/CacheMedia.php');
require_once(__DIR__ . '/CacheMenuItem.php');
require_once(__DIR__ . '/CacheReportHtmlDataBlockArray.php');
require_once(__DIR__ . '/CacheResults.php');
require_once(__DIR__ . '/CacheResult.php');
require_once(__DIR__ . '/Call.php');
require_once(__DIR__ . '/CallStack.php');
require_once(__DIR__ . '/Canonical.php');
require_once(__DIR__ . '/ColorRgb.php');
require_once(__DIR__ . '/ColorHsl.php');
require_once(__DIR__ . '/ComboStrap.php');
require_once(__DIR__ . '/ConditionalValue.php');
require_once(__DIR__ . '/Console.php');
require_once(__DIR__ . '/Cron.php');
require_once(__DIR__ . '/DatabasePageRow.php');
require_once(__DIR__ . '/DataType.php');
require_once(__DIR__ . '/Dictionary.php');
require_once(__DIR__ . '/Dimension.php');
require_once(__DIR__ . '/DisqusIdentifier.php');
require_once(__DIR__ . '/Display.php');
require_once(__DIR__ . '/DokuwikiUrl.php');
require_once(__DIR__ . '/DokuwikiId.php');
require_once(__DIR__ . '/EndDate.php');
require_once(__DIR__ . '/Event.php');
require_once(__DIR__ . '/ExitException.php');
require_once(__DIR__ . '/ExceptionCombo.php');
require_once(__DIR__ . '/ExceptionComboNotFound.php');
require_once(__DIR__ . '/ExceptionComboRuntime.php');
require_once(__DIR__ . '/FileSystems.php');
require_once(__DIR__ . '/FloatAttribute.php');
require_once(__DIR__ . '/FormMeta.php');
require_once(__DIR__ . '/FormMetaTab.php');
require_once(__DIR__ . '/FormMetaField.php');
require_once(__DIR__ . '/FontSize.php');
require_once(__DIR__ . '/FsWikiUtility.php');
require_once(__DIR__ . '/HeaderUtility.php');
require_once(__DIR__ . '/HtmlDocument.php');
require_once(__DIR__ . '/HistoricalBreadcrumbMenuItem.php');
require_once(__DIR__ . '/Hover.php');
require_once(__DIR__ . '/Html.php');
require_once(__DIR__ . '/Http.php');
require_once(__DIR__ . '/HttpResponse.php');
require_once(__DIR__ . '/Identity.php');
require_once(__DIR__ . '/Image.php');
require_once(__DIR__ . '/ImageLink.php');
require_once(__DIR__ . '/ImageRaster.php');
require_once(__DIR__ . '/ImageSvg.php');
require_once(__DIR__ . '/Icon.php'); // icon is an image svg and should be after
require_once(__DIR__ . '/Index.php');
require_once(__DIR__ . '/InstructionsDocument.php');
require_once(__DIR__ . '/InternetPath.php');
require_once(__DIR__ . '/InterWikiPath.php');
require_once(__DIR__ . '/Iso8601Date.php');
require_once(__DIR__ . '/Json.php');
require_once(__DIR__ . '/JavascriptLibrary.php');
require_once(__DIR__ . '/Lang.php');
require_once(__DIR__ . '/LdJson.php');
require_once(__DIR__ . '/LineSpacing.php');
require_once(__DIR__ . '/Locale.php');
require_once(__DIR__ . '/LocalFs.php');
require_once(__DIR__ . '/LocalPath.php');
require_once(__DIR__ . '/LogException.php');
require_once(__DIR__ . '/LogUtility.php');
require_once(__DIR__ . '/LowQualityPage.php');
require_once(__DIR__ . '/LowQualityPageOverwrite.php');
require_once(__DIR__ . '/LowQualityCalculatedIndicator.php');
require_once(__DIR__ . '/MarkupRef.php');
require_once(__DIR__ . '/Math.php');
require_once(__DIR__ . '/MetaManagerForm.php');
require_once(__DIR__ . '/MetaManagerMenuItem.php');
require_once(__DIR__ . '/MetadataDokuWikiStore.php');
require_once(__DIR__ . '/MetadataFormDataStore.php');
require_once(__DIR__ . '/MetadataFrontmatterStore.php');
require_once(__DIR__ . '/MetadataDbStore.php');
require_once(__DIR__ . '/MetadataStoreTransfer.php');
require_once(__DIR__ . '/Message.php');
require_once(__DIR__ . '/Mermaid.php');
require_once(__DIR__ . '/Mime.php');
require_once(__DIR__ . '/ModificationDate.php');
require_once(__DIR__ . '/NavBarUtility.php');
require_once(__DIR__ . '/Opacity.php');
require_once(__DIR__ . '/Os.php');
require_once(__DIR__ . '/Page.php');
require_once(__DIR__ . '/PageDescription.php');
require_once(__DIR__ . '/PageEdit.php');
require_once(__DIR__ . '/PageId.php');
require_once(__DIR__ . '/PageKeywords.php');
require_once(__DIR__ . '/PageImages.php');
require_once(__DIR__ . '/PageImage.php');
require_once(__DIR__ . '/PageImagePath.php');
require_once(__DIR__ . '/PageImageUsage.php');
require_once(__DIR__ . '/PageLayout.php');
require_once(__DIR__ . '/PagePath.php');
require_once(__DIR__ . '/PageProtection.php');
require_once(__DIR__ . '/PageRules.php');
require_once(__DIR__ . '/PageSql.php');
require_once(__DIR__ . '/PageSqlParser/PageSqlLexer.php');
require_once(__DIR__ . '/PageSqlParser/PageSqlParser.php');
require_once(__DIR__ . '/PageSqlTreeListener.php');
require_once(__DIR__ . '/PageType.php');
require_once(__DIR__ . '/PageTitle.php');
require_once(__DIR__ . '/PageUrlPath.php');
require_once(__DIR__ . '/PageUrlType.php');
require_once(__DIR__ . '/PipelineUtility.php');
require_once(__DIR__ . '/Position.php');
require_once(__DIR__ . '/Prism.php');
require_once(__DIR__ . '/PagePublicationDate.php');
require_once(__DIR__ . '/PageCreationDate.php');
require_once(__DIR__ . '/PageH1.php');
require_once(__DIR__ . '/QualityDynamicMonitoringOverwrite.php');
require_once(__DIR__ . '/QualityMenuItem.php');
require_once(__DIR__ . '/RasterImageLink.php');
require_once(__DIR__ . '/Region.php');
require_once(__DIR__ . '/RenderUtility.php');
require_once(__DIR__ . '/ReplicationDate.php');
require_once(__DIR__ . '/ResourceName.php');
require_once(__DIR__ . '/Sanitizer.php');
require_once(__DIR__ . '/Shadow.php');
require_once(__DIR__ . '/Site.php');
require_once(__DIR__ . '/Skin.php');
require_once(__DIR__ . '/Slug.php');
require_once(__DIR__ . '/Snippet.php');
require_once(__DIR__ . '/SnippetManager.php');
require_once(__DIR__ . '/Spacing.php');
require_once(__DIR__ . '/Sqlite.php');
require_once(__DIR__ . '/SqliteRequest.php');
require_once(__DIR__ . '/SqliteResult.php');
require_once(__DIR__ . '/StringUtility.php');
require_once(__DIR__ . '/StartDate.php');
require_once(__DIR__ . '/StyleUtility.php');
require_once(__DIR__ . '/SvgDocument.php');
require_once(__DIR__ . '/SvgImageLink.php');
require_once(__DIR__ . '/Syntax.php');
require_once(__DIR__ . '/TableUtility.php');
require_once(__DIR__ . '/Tag.php');
require_once(__DIR__ . '/TagAttributes.php');
require_once(__DIR__ . '/Template.php');
require_once(__DIR__ . '/TemplateStore.php');
require_once(__DIR__ . '/TemplateUtility.php');
require_once(__DIR__ . '/TextAlign.php');
require_once(__DIR__ . '/TextColor.php');
require_once(__DIR__ . '/ThirdMedia.php');
require_once(__DIR__ . '/ThirdMediaLink.php');
require_once(__DIR__ . '/ThirdPartyPlugins.php');
require_once(__DIR__ . '/TocUtility.php');
require_once(__DIR__ . '/Toggle.php');
require_once(__DIR__ . '/Tooltip.php');
require_once(__DIR__ . '/References.php');
require_once(__DIR__ . '/Reference.php');
require_once(__DIR__ . '/Underline.php');
require_once(__DIR__ . '/Unit.php');
require_once(__DIR__ . '/Url.php');
require_once(__DIR__ . '/UrlManagerBestEndPage.php');
require_once(__DIR__ . '/XhtmlUtility.php');
require_once(__DIR__ . '/XmlDocument.php');
require_once(__DIR__ . '/XmlUtility.php');


/**
 * Class url static
 * List of static utilities
 */
class PluginUtility
{

    const DOKU_DATA_DIR = '/dokudata/pages';
    const DOKU_CACHE_DIR = '/dokudata/cache';

    /**
     * Key in the data array between the handle and render function
     */
    const STATE = "state";
    const PAYLOAD = "payload"; // The html or text
    const ATTRIBUTES = "attributes";
    // The context is generally the parent tag but it may be also the grandfather.
    // It permits to determine the HTML that is outputted
    const CONTEXT = 'context';
    const TAG = "tag";

    /**
     * The name of the hidden/private namespace
     * where the icon and other artifactory are stored
     */
    const COMBOSTRAP_NAMESPACE_NAME = "combostrap";

    const PARENT = "parent";
    const POSITION = "position";

    /**
     * Class to center an element
     */
    const CENTER_CLASS = "mx-auto";


    const EDIT_SECTION_TARGET = 'section';
    const EXIT_MESSAGE = "errorAtt";
    const EXIT_CODE = "exit_code";
    const DISPLAY = "display";

    /**
     * The URL base of the documentation
     */
    static $URL_APEX;


    /**
     * @var string - the plugin base name (ie the directory)
     * ie $INFO_PLUGIN['base'];
     * This is a constant because it permits code analytics
     * such as verification of a path
     */
    const PLUGIN_BASE_NAME = "combo";

    /**
     * The name of the template plugin
     */
    const TEMPLATE_STRAP_NAME = "strap";

    /**
     * @var array
     */
    static $INFO_PLUGIN;

    static $PLUGIN_LANG;

    /**
     * The plugin name
     * (not the same than the base as it's not related to the directory
     * @var string
     */
    public static $PLUGIN_NAME;
    /**
     * @var mixed the version
     */
    private static $VERSION;


    /**
     * Initiate the static variable
     * See the call after this class
     */
    static function init()
    {

        $pluginInfoFile = __DIR__ . '/../plugin.info.txt';
        self::$INFO_PLUGIN = confToHash($pluginInfoFile);
        self::$PLUGIN_NAME = 'ComboStrap';
        global $lang;
        self::$PLUGIN_LANG = $lang[self::PLUGIN_BASE_NAME];
        self::$URL_APEX = "https://" . parse_url(self::$INFO_PLUGIN['url'], PHP_URL_HOST);
        self::$VERSION = self::$INFO_PLUGIN['version'];

    }

    /**
     * @param $inputExpression
     * @return false|int 1|0
     * returns:
     *    - 1 if the input expression is a pattern,
     *    - 0 if not,
     *    - FALSE if an error occurred.
     */
    static function isRegularExpression($inputExpression)
    {

        $regularExpressionPattern = "/(\\/.*\\/[gmixXsuUAJ]?)/";
        return preg_match($regularExpressionPattern, $inputExpression);

    }

    /**
     * Return a mode from a tag (ie from a {@link Plugin::getPluginComponent()}
     * @param $tag
     * @return string
     *
     * A mode is just a name for a class
     * Example: $Parser->addMode('listblock',new Doku_Parser_Mode_ListBlock());
     */
    public static function getModeFromTag($tag)
    {
        return "plugin_" . self::getComponentName($tag);
    }

    /**
     * @param $tag
     * @return string
     *
     * Create a lookahead pattern for a container tag used to enter in a mode
     */
    public static function getContainerTagPattern($tag)
    {
        // this pattern ensure that the tag
        // `accordion` will not intercept also the tag `accordionitem`
        // where:
        // ?: means non capturing group (to not capture the last >)
        // (\s.*?): is a capturing group that starts with a space
        $pattern = "(?:\s.*?>|>)";
        return '<' . $tag . $pattern . '(?=.*?<\/' . $tag . '>)';
    }

    /**
     * This pattern allows space after the tag name
     * for an end tag
     * As XHTML (https://www.w3.org/TR/REC-xml/#dt-etag)
     * @param $tag
     * @return string
     */
    public static function getEndTagPattern($tag)
    {
        return "</$tag\s*>";
    }

    /**
     * @param $tag
     * @return string
     *
     * Create a open tag pattern without lookahead.
     * Used for
     * @link https://dev.w3.org/html5/html-author/#void-elements-0
     */
    public static function getVoidElementTagPattern($tag)
    {
        return '<' . $tag . '.*?>';
    }


    /**
     * Take an array  where the key is the attribute name
     * and return a HTML tag string
     *
     * The attribute name and value are escaped
     *
     * @param $attributes - combo attributes
     * @return string
     * @deprecated to allowed background and other metadata, use {@link TagAttributes::toHtmlEnterTag()}
     */
    public static function array2HTMLAttributesAsString($attributes)
    {

        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
        return $tagAttributes->toHTMLAttributeString();

    }

    /**
     *
     * Parse the attributes part of a match
     *
     * Example:
     *   line-numbers="value"
     *   line-numbers='value'
     *
     * This value may be in:
     *   * configuration value
     *   * as well as in the match of a {@link SyntaxPlugin}
     *
     * @param $string
     * @return array
     *
     * To parse a match, use {@link PluginUtility::getTagAttributes()}
     *
     *
     */
    public static function parseAttributes($string)
    {

        $parameters = array();

        // Rules
        //  * name may be alone (ie true boolean attribute)
        //  * a name may get a `-`
        //  * there may be space every everywhere when the value is enclosed with a quote
        //  * there may be no space in the value and between the equal sign when the value is not enclosed
        //
        // /i not case sensitive
        $attributePattern = '\s*([-\w]+)\s*(?:=(\s*[\'"]([^`"]*)[\'"]\s*|[^\s]*))?';
        $result = preg_match_all('/' . $attributePattern . '/i', $string, $matches);
        if ($result != 0) {
            foreach ($matches[1] as $key => $parameterKey) {

                // group 3 (ie the value between quotes)
                $value = $matches[3][$key];
                if ($value == "") {
                    // check the value without quotes
                    $value = $matches[2][$key];
                }
                // if there is no value, this is a boolean
                if ($value == "") {
                    $value = true;
                } else {
                    $value = hsc($value);
                }
                $parameters[hsc(strtolower($parameterKey))] = $value;
            }
        }
        return $parameters;

    }

    public static function getTagAttributes($match, $knownTypes = null): array
    {
        return self::getQualifiedTagAttributes($match, false, "", $knownTypes);
    }

    /**
     * Return the attribute of a tag
     * Because they are users input, they are all escaped
     * @param $match
     * @param $hasThirdValue - if true, the third parameter is treated as value, not a property and returned in the `third` key
     * use for the code/file/console where they accept a name as third value
     * @param $keyThirdArgument - if a third argument is found, return it with this key
     * @param array|null $knownTypes
     * @return array
     */
    public static function getQualifiedTagAttributes($match, $hasThirdValue, $keyThirdArgument, array $knownTypes = null): array
    {

        $match = PluginUtility::getPreprocessEnterTag($match);

        // Suppress the tag name (ie until the first blank)
        $spacePosition = strpos($match, " ");
        if (!$spacePosition) {
            // No space, meaning this is only the tag name
            return array();
        }
        $match = trim(substr($match, $spacePosition));
        if ($match == "") {
            return array();
        }

        // Do we have a type as first argument ?
        $attributes = array();
        $spacePosition = strpos($match, " ");
        if ($spacePosition) {
            $nextArgument = substr($match, 0, $spacePosition);
        } else {
            $nextArgument = $match;
        }

        $isType = !strpos($nextArgument, "=");
        if ($knownTypes !== null) {
            if (!in_array($nextArgument, $knownTypes)) {
                $isType = false;
            }
        }
        if ($isType) {

            $attributes["type"] = $nextArgument;
            // Suppress the type
            $match = substr($match, strlen($nextArgument));
            $match = trim($match);

            // Do we have a value as first argument ?
            if (!empty($hasThirdValue)) {
                $spacePosition = strpos($match, " ");
                if ($spacePosition) {
                    $nextArgument = substr($match, 0, $spacePosition);
                } else {
                    $nextArgument = $match;
                }
                if (!strpos($nextArgument, "=") && !empty($nextArgument)) {
                    $attributes[$keyThirdArgument] = $nextArgument;
                    // Suppress the third argument
                    $match = substr($match, strlen($nextArgument));
                    $match = trim($match);
                }
            }
        }

        // Parse the remaining attributes
        $parsedAttributes = self::parseAttributes($match);

        // Merge
        $attributes = array_merge($attributes, $parsedAttributes);;

        return $attributes;

    }

    /**
     * @param array $styleProperties - an array of CSS properties with key, value
     * @return string - the value for the style attribute (ie all rules where joined with the comma)
     */
    public static function array2InlineStyle(array $styleProperties)
    {
        $inlineCss = "";
        foreach ($styleProperties as $key => $value) {
            $inlineCss .= "$key:$value;";
        }
        // Suppress the last ;
        if ($inlineCss[strlen($inlineCss) - 1] == ";") {
            $inlineCss = substr($inlineCss, 0, -1);
        }
        return $inlineCss;
    }

    /**
     * @param $tag
     * @return string
     * Create a pattern used where the tag is not a container.
     * ie
     * <br/>
     * <icon/>
     * This is generally used with a subtition plugin
     * and a {@link Lexer::addSpecialPattern} state
     * where the tag is just replaced
     */
    public static function getEmptyTagPattern($tag): string
    {

        /**
         * A tag should start with the tag
         * `(?=[/ ]{1})` - a space or the / (lookahead) => to allow allow tag name with minus character
         * `(?![^/]>)` - it's not a normal tag (ie a > with the previous character that is not /)
         * `[^>]*` then until the > is found (dokuwiki capture greedy, don't use the point character)
         * then until the close `/>` character
         */
        return '<' . $tag . '(?=[/ ]{1})(?![^/]>)[^>]*\/>';
    }

    /**
     * Just call this function from a class like that
     *     getTageName(get_called_class())
     * to get the tag name (ie the component plugin)
     * of a syntax plugin
     *
     * @param $get_called_class
     * @return string
     */
    public static function getTagName($get_called_class)
    {
        list(/* $t */, /* $p */, /* $n */, $c) = explode('_', $get_called_class, 4);
        return (isset($c) ? $c : '');
    }

    /**
     * Just call this function from a class like that
     *     getAdminPageName(get_called_class())
     * to get the page name of a admin plugin
     *
     * @param $get_called_class
     * @return string - the admin page name
     */
    public static function getAdminPageName($get_called_class)
    {
        $names = explode('_', $get_called_class);
        $names = array_slice($names, -2);
        return implode('_', $names);
    }

    public static function getNameSpace()
    {
        // No : at the begin of the namespace please
        return self::PLUGIN_BASE_NAME . ':';
    }

    /**
     * @param $get_called_class - the plugin class
     * @return array
     */
    public static function getTags($get_called_class)
    {
        $elements = array();
        $elementName = PluginUtility::getTagName($get_called_class);
        $elements[] = $elementName;
        $elements[] = strtoupper($elementName);
        return $elements;
    }

    /**
     * Render a text
     * @param $pageContent
     * @return string|null
     */
    public static function render($pageContent)
    {
        return RenderUtility::renderText2XhtmlAndStripPEventually($pageContent, false);
    }


    /**
     * This method will takes attributes
     * and process the plugin styling attribute such as width and height
     * to put them in a style HTML attribute
     * @param TagAttributes $attributes
     */
    public static function processStyle(&$attributes)
    {
        // Style
        $styleAttributeName = "style";
        if ($attributes->hasComponentAttribute($styleAttributeName)) {
            $properties = explode(";", $attributes->getValueAndRemove($styleAttributeName));
            foreach ($properties as $property) {
                list($key, $value) = explode(":", $property);
                if ($key != "") {
                    $attributes->addStyleDeclarationIfNotSet($key, $value);
                }
            }
        }


        /**
         * Border Color
         * For background color, see {@link TagAttributes::processBackground()}
         * For text color, see {@link TextColor}
         */

        if ($attributes->hasComponentAttribute(ColorRgb::BORDER_COLOR)) {
            $colorValue = $attributes->getValueAndRemove(ColorRgb::BORDER_COLOR);
            $attributes->addStyleDeclarationIfNotSet(ColorRgb::BORDER_COLOR, ColorRgb::createFromString($colorValue)->toCssValue());
            self::checkDefaultBorderColorAttributes($attributes);
        }


    }

    /**
     * Return the name of the requested script
     */
    public
    static function getRequestScript()
    {
        $scriptPath = null;
        $testPropertyValue = self::getPropertyValue("SCRIPT_NAME");
        if (defined('DOKU_UNITTEST') && $testPropertyValue != null) {
            return $testPropertyValue;
        }
        if (array_key_exists("DOCUMENT_URI", $_SERVER)) {
            $scriptPath = $_SERVER["DOCUMENT_URI"];
        }
        if ($scriptPath == null && array_key_exists("SCRIPT_NAME", $_SERVER)) {
            $scriptPath = $_SERVER["SCRIPT_NAME"];
        }
        if ($scriptPath == null) {
            msg("Unable to find the main script", LogUtility::LVL_MSG_ERROR);
        }
        $path_parts = pathinfo($scriptPath);
        return $path_parts['basename'];
    }

    /**
     *
     * @param $name
     * @param $default
     * @return string - the value of a query string property or if in test mode, the value of a test variable
     * set with {@link self::setTestProperty}
     * This is used to test script that are not supported by the dokuwiki test framework
     * such as css.php
     */
    public
    static function getPropertyValue($name, $default = null)
    {
        global $INPUT;
        $value = $INPUT->str($name);
        if ($value == null && defined('DOKU_UNITTEST')) {
            global $COMBO;
            $value = $COMBO[$name];
        }
        if ($value == null) {
            return $default;
        } else {
            return $value;
        }

    }

    /**
     * Create an URL to the documentation website
     * @param $canonical - canonical id or slug
     * @param $label -  the text of the link
     * @param bool $withIcon - used to break the recursion with the message in the {@link Icon}
     * @return string - an url
     */
    public
    static function getDocumentationHyperLink($canonical, $label, $withIcon = true, $tooltip = ""): string
    {
        /** @noinspection SpellCheckingInspection */

        $xhtmlIcon = "";
        if ($withIcon) {

            /**
             * We don't include it as an external resource via url
             * because it then make a http request for every logo
             * in the configuration page and makes it really slow
             * TODO: when we have made a special fetch ajax with cache
             * for application resource, we can serve it statically
             */
            $path = Site::getComboImagesDirectory()->resolve("logo.svg");
            try {
                $tagAttributes = TagAttributes::createEmpty(SvgImageLink::CANONICAL);
                $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ICON_TYPE);
                $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, "20");
                $cache = new CacheMedia($path, $tagAttributes);
                if (!$cache->isCacheUsable()) {
                    $xhtmlIcon = SvgDocument::createSvgDocumentFromPath($path)
                        ->setShouldBeOptimized(true)
                        ->getXmlText($tagAttributes);
                    $cache->storeCache($xhtmlIcon);
                }
                $xhtmlIcon = FileSystems::getContent($cache->getFile());
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The logo ($path) is not valid and could not be added to the documentation link. Error: {$e->getMessage()}");
            }

        }
        $urlApex = self::$URL_APEX;
        $path = str_replace(":", "/", $canonical);
        if (empty($tooltip)) {
            $title = $label;
        } else {
            $title = $tooltip;
        }
        $htmlToolTip = "";
        if (!empty($tooltip)) {
            $dataAttributeNamespace = Bootstrap::getDataNamespace();
            $htmlToolTip = "data{$dataAttributeNamespace}-toggle=\"tooltip\"";
        }
        return "$xhtmlIcon<a href=\"$urlApex/$path\" title=\"$title\" $htmlToolTip style=\"text-decoration:none;\">$label</a>";
    }

    /**
     * An utility function to not search every time which array should be first
     * @param array $inlineAttributes - the component inline attributes
     * @param array $defaultAttributes - the default configuration attributes
     * @return array - a merged array
     */
    public
    static function mergeAttributes(array $inlineAttributes, array $defaultAttributes = array())
    {
        return array_merge($defaultAttributes, $inlineAttributes);
    }

    /**
     * A pattern for a container tag
     * that needs to catch the content
     *
     * Use as a special pattern (substition)
     *
     * The {@link \syntax_plugin_combo_math} use it
     * @param $tag
     * @return string - a pattern
     */
    public
    static function getLeafContainerTagPattern($tag)
    {
        return '<' . $tag . '.*?>.*?<\/' . $tag . '>';
    }

    /**
     * Return the content of a tag
     * <math>Content</math>
     * @param $match
     * @return string the content
     */
    public
    static function getTagContent($match)
    {
        // From the first >
        $start = strpos($match, ">");
        if ($start == false) {
            LogUtility::msg("The match does not contain any opening tag. Match: {$match}", LogUtility::LVL_MSG_ERROR);
            return "";
        }
        $match = substr($match, $start + 1);
        // If this is the last character, we get a false
        if ($match == false) {
            LogUtility::msg("The match does not contain any closing tag. Match: {$match}", LogUtility::LVL_MSG_ERROR);
            return "";
        }

        $end = strrpos($match, "</");
        if ($end == false) {
            LogUtility::msg("The match does not contain any closing tag. Match: {$match}", LogUtility::LVL_MSG_ERROR);
            return "";
        }

        return substr($match, 0, $end);
    }

    /**
     *
     * Check if a HTML tag was already added for a request
     * The request id is just the timestamp
     * An indicator array should be provided
     * @return string
     */
    public
    static function getRequestId()
    {

        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            // since php 5.4
            $requestTime = $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            // DokuWiki test framework use this
            $requestTime = $_SERVER['REQUEST_TIME'];
        }
        $keyPrefix = 'combo_';

        global $ID;
        return $keyPrefix . hash('crc32b', $_SERVER['REMOTE_ADDR'] . $_SERVER['REMOTE_PORT'] . $requestTime . $ID);

    }

    /**
     * Get the page id
     * If the page is a sidebar, it will not return the id of the sidebar
     * but the one of the page
     * Return the main/requested page id
     * (Not the sidebar)
     * @return string|null - null in test
     */
    public
    static function getRequestedWikiId(): ?string
    {
        global $ID;
        global $INFO;
        $callingId = $ID;
        // If the component is in a sidebar, we don't want the ID of the sidebar
        // but the ID of the page.
        if ($INFO !== null) {
            $callingId = $INFO['id'];
        }
        /**
         * This is the case with event triggered
         * before DokuWiki such as
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        if ($callingId == null) {
            global $_REQUEST;
            if (isset($_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE])) {
                $callingId = $_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
            }
        }

        return $callingId;

    }

    /**
     * Encode special HTML characters to entity
     * (ie escaping)
     *
     * This is used to transform text that may be interpreted as HTML
     * into a text
     *   * that will not be interpreted as HTML
     *   * that may be added in html attribute
     *
     * For instance:
     *  * text that should go in attribute with special HTML characters (such as title)
     *  * text that we don't create (to prevent HTML injection)
     *
     * Example:
     *
     * <script>...</script>
     * to
     * "&lt;script&gt;...&lt;/hello&gt;"
     *
     *
     * @param $text
     * @return string
     */
    public
    static function htmlEncode($text): string
    {
        /**
         * See https://stackoverflow.com/questions/46483/htmlentities-vs-htmlspecialchars/3614344
         *
         * Not {@link htmlentities } htmlentities($text, ENT_QUOTES);
         * Otherwise we get `Error while loading HTMLError: Entity 'hellip' not defined`
         * when loading HTML with {@link XmlDocument}
         *
         * See also {@link PluginUtility::htmlDecode()}
         *
         * Without ENT_QUOTES
         * <h4 class="heading-combo">
         * is encoded as
         * &gt;h4 class="heading-combo"&lt;
         * and cannot be added in a attribute because of the quote
         * This is used for {@link Tooltip}
         */
        return htmlspecialchars($text, ENT_XHTML | ENT_QUOTES);

    }

    public
    static function xmlEncode($text)
    {
        /**
         * {@link htmlentities }
         */
        return htmlentities($text, ENT_XML1);
    }


    /**
     * Add a class
     * @param $classValue
     * @param array $attributes
     */
    public
    static function addClass2Attributes($classValue, array &$attributes)
    {
        self::addAttributeValue("class", $classValue, $attributes);
    }

    /**
     * Add a style property to the attributes
     * @param $property
     * @param $value
     * @param array $attributes
     * @deprecated use {@link TagAttributes::addStyleDeclarationIfNotSet()} instead
     */
    public
    static function addStyleProperty($property, $value, array &$attributes)
    {
        if (isset($attributes["style"])) {
            $attributes["style"] .= ";$property:$value";
        } else {
            $attributes["style"] = "$property:$value";
        }

    }

    /**
     * Add default border attributes
     * to see a border
     * Doc
     * https://combostrap.com/styling/color#border_color
     * @param TagAttributes $tagAttributes
     */
    private
    static function checkDefaultBorderColorAttributes(&$tagAttributes)
    {
        /**
         * border color was set without the width
         * setting the width
         */
        if (!(
            $tagAttributes->hasStyleDeclaration("border")
            ||
            $tagAttributes->hasStyleDeclaration("border-width")
        )
        ) {
            $tagAttributes->addStyleDeclarationIfNotSet("border-width", "1px");
        }
        /**
         * border color was set without the style
         * setting the style
         */
        if (!
        (
            $tagAttributes->hasStyleDeclaration("border")
            ||
            $tagAttributes->hasStyleDeclaration("border-style")
        )
        ) {
            $tagAttributes->addStyleDeclarationIfNotSet("border-style", "solid");

        }
        if (!$tagAttributes->hasStyleDeclaration("border-radius")) {
            $tagAttributes->addStyleDeclarationIfNotSet("border-radius", ".25rem");
        }

    }

    public
    static function getConfValue($confName, $defaultValue = null)
    {
        global $conf;
        $value = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][$confName];
        if ($value === null || trim($value) === "") {
            return $defaultValue;
        }
        return $value;
    }

    /**
     * @param $match
     * @return null|string - return the tag name or null if not found
     */
    public
    static function getTag($match)
    {

        // Trim to start clean
        $match = trim($match);

        // Until the first >
        $pos = strpos($match, ">");
        if ($pos == false) {
            LogUtility::msg("The match does not contain any tag. Match: {$match}", LogUtility::LVL_MSG_ERROR);
            return null;
        }
        $match = substr($match, 0, $pos);

        // Suppress the <
        if ($match[0] == "<") {
            $match = substr($match, 1);
        } else {
            LogUtility::msg("This is not a text tag because it does not start with the character `>`");
        }

        // Suppress the tag name (ie until the first blank)
        $spacePosition = strpos($match, " ");
        if (!$spacePosition) {
            // No space, meaning this is only the tag name
            return $match;
        } else {
            return substr($match, 0, $spacePosition);
        }

    }


    /**
     * @param string $string add a command into HTML
     */
    public
    static function addAsHtmlComment($string)
    {
        print_r('<!-- ' . self::htmlEncode($string) . '-->');
    }

    public
    static function getResourceBaseUrl()
    {
        return DOKU_URL . 'lib/plugins/' . PluginUtility::PLUGIN_BASE_NAME . '/resources';
    }


    public
    static function getComponentName($tag)
    {
        return strtolower(PluginUtility::PLUGIN_BASE_NAME) . "_" . $tag;
    }

    public
    static function addAttributeValue($attribute, $value, array &$attributes)
    {
        if (array_key_exists($attribute, $attributes) && $attributes[$attribute] !== "") {
            $attributes[$attribute] .= " {$value}";
        } else {
            $attributes[$attribute] = "{$value}";
        }
    }

    /**
     * Plugin Utility is available to all plugin,
     * this is a convenient way to the the snippet manager
     * @return SnippetManager
     */
    public
    static function getSnippetManager(): SnippetManager
    {
        return SnippetManager::getOrCreate();
    }


    /**
     * Function used in a render
     * @param $data - the data from {@link PluginUtility::handleAndReturnUnmatchedData()}
     * @return string
     */
    public
    static function renderUnmatched($data): string
    {
        /**
         * Attributes
         */
        if (isset($data[PluginUtility::ATTRIBUTES])) {
            $attributes = $data[PluginUtility::ATTRIBUTES];
        } else {
            $attributes = [];
        }
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
        $display = $tagAttributes->getValue(Display::DISPLAY);
        if ($display !== "none") {
            $payload = $data[self::PAYLOAD];
            $previousTagDisplayType = $data[self::CONTEXT];
            if ($previousTagDisplayType !== Call::INLINE_DISPLAY) {
                $payload = ltrim($payload);
            }
            return PluginUtility::htmlEncode($payload);
        } else {
            return "";
        }
    }

    public
    static function renderUnmatchedXml($data)
    {
        $payload = $data[self::PAYLOAD];
        $previousTagDisplayType = $data[self::CONTEXT];
        if ($previousTagDisplayType !== Call::INLINE_DISPLAY) {
            $payload = ltrim($payload);
        }
        return PluginUtility::xmlEncode($payload);

    }

    /**
     * Function used in a handle function of a syntax plugin for
     * unmatched context
     * @param $tagName
     * @param $match
     * @param \Doku_Handler $handler
     * @return array
     */
    public
    static function handleAndReturnUnmatchedData($tagName, $match, \Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $sibling = $callStack->previous();
        $context = null;
        if (!empty($sibling)) {
            $context = $sibling->getDisplay();
        }
        return array(
            PluginUtility::STATE => DOKU_LEXER_UNMATCHED,
            PluginUtility::PAYLOAD => $match,
            PluginUtility::CONTEXT => $context
        );
    }

    public
    static function setConf($key, $value, $namespace = 'plugin')
    {
        global $conf;
        if ($namespace !== null) {
            $conf[$namespace][PluginUtility::PLUGIN_BASE_NAME][$key] = $value;
        } else {
            $conf[$key] = $value;
        }

    }

    /**
     * Utility methodPreprocess a start tag to be able to extract the name
     * and the attributes easily
     *
     * It will delete:
     *   * the characters <> and the /> if present
     *   * and trim
     *
     * It will remain the tagname and its attributes
     * @param $match
     * @return false|string|null
     */
    private
    static function getPreprocessEnterTag($match)
    {
        // Until the first >
        $pos = strpos($match, ">");
        if ($pos == false) {
            LogUtility::msg("The match does not contain any tag. Match: {$match}", LogUtility::LVL_MSG_WARNING);
            return null;
        }
        $match = substr($match, 0, $pos);


        // Trim to start clean
        $match = trim($match);

        // Suppress the <
        if ($match[0] == "<") {
            $match = substr($match, 1);
        }

        // Suppress the / for a leaf tag
        if ($match[strlen($match) - 1] == "/") {
            $match = substr($match, 0, strlen($match) - 1);
        }
        return $match;
    }

    /**
     * Retrieve the tag name used in the text document
     * @param $match
     * @return false|string|null
     */
    public
    static function getSyntaxTagNameFromMatch($match)
    {
        $preprocessMatch = PluginUtility::getPreprocessEnterTag($match);

        // Tag name (ie until the first blank)
        $spacePosition = strpos($match, " ");
        if (!$spacePosition) {
            // No space, meaning this is only the tag name
            return $preprocessMatch;
        } else {
            return trim(substr(0, $spacePosition));
        }

    }

    /**
     * @param \Doku_Renderer_xhtml $renderer
     * @param $position
     * @param $name
     */
    public
    static function startSection($renderer, $position, $name)
    {


        if (empty($position)) {
            LogUtility::msg("The position for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }
        if (empty($name)) {
            LogUtility::msg("The name for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }

        /**
         * New Dokuwiki Version
         * for DokuWiki Greebo and more recent versions
         */
        if (defined('SEC_EDIT_PATTERN')) {
            $renderer->startSectionEdit($position, array('target' => self::EDIT_SECTION_TARGET, 'name' => $name));
        } else {
            /**
             * Old version
             */
            /** @noinspection PhpParamsInspection */
            $renderer->startSectionEdit($position, self::EDIT_SECTION_TARGET, $name);
        }
    }

    /**
     * Add an enter call to the stack
     * @param \Doku_Handler $handler
     * @param $tagName
     * @param array $callStackArray
     */
    public
    static function addEnterCall(
        \Doku_Handler &$handler,
        $tagName,
        $callStackArray = array()
    )
    {
        $pluginName = PluginUtility::getComponentName($tagName);
        $handler->addPluginCall(
            $pluginName,
            $callStackArray,
            DOKU_LEXER_ENTER,
            null,
            null
        );
    }

    /**
     * Add an end call dynamically
     * @param \Doku_Handler $handler
     * @param $tagName
     * @param array $callStackArray
     */
    public
    static function addEndCall(\Doku_Handler $handler, $tagName, $callStackArray = array())
    {
        $pluginName = PluginUtility::getComponentName($tagName);
        $handler->addPluginCall(
            $pluginName,
            $callStackArray,
            DOKU_LEXER_END,
            null,
            null
        );
    }

    /**
     * General Debug
     */
    public
    static function isDebug()
    {
        global $conf;
        return $conf["allowdebug"] === 1;

    }


    /**
     *
     * See also dev.md file
     */
    public static function isDevOrTest()
    {
        if (self::isDev()) {
            return true;
        }
        return self::isTest();
    }

    /**
     * Is this a dev environment (ie laptop where the dev is working)
     * @return bool
     */
    public static function isDev(): bool
    {
        global $_SERVER;
        if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
            return true;
        }
        if ($_SERVER["COMPUTERNAME"] === "NICO") {
            return true;
        }
        return false;
    }

    public static function getInstructions($markiCode)
    {
        return p_get_instructions($markiCode);
    }

    public static function getInstructionsWithoutRoot($markiCode)
    {
        return RenderUtility::getInstructionsAndStripPEventually($markiCode);
    }

    public static function isTest()
    {
        return defined('DOKU_UNITTEST');
    }


    public static function getCacheManager(): CacheManager
    {
        return CacheManager::getOrCreate();
    }

    public static function getModeFromPluginName($name)
    {
        return "plugin_$name";
    }

    public static function isCi(): bool
    {
        // https://docs.travis-ci.com/user/environment-variables/#default-environment-variables
        return getenv("CI") === "true";
    }

    public static function htmlDecode($int): string
    {
        return htmlspecialchars_decode($int, ENT_XHTML | ENT_QUOTES);
    }

    /**
     * Tells if the process is to output a page
     * @return bool
     */
    public static function isRenderingRequestedPageProcess(): bool
    {

        global $ID;
        if (empty($ID)) {
            // $ID is null
            // case on "/lib/exe/mediamanager.php"
            return false;
        }

        $page = Page::createPageFromId($ID);
        if (!$page->exists()) {
            return false;
        }

        /**
         * No metadata for bars
         */
        if ($page->isSecondarySlot()) {
            return false;
        }
        return true;

    }

    /**
     * @throws ExceptionCombo
     */
    public static function renderInstructionsToXhtml($callStackHeaderInstructions): ?string
    {
        return RenderUtility::renderInstructionsToXhtml($callStackHeaderInstructions);
    }

    /**
     */
    public static function getCurrentSlotId()
    {
        global $ID;
        $slot = $ID;
        if ($slot === null) {
            if (!PluginUtility::isTest()) {
                LogUtility::msg("The slot could not be identified (global ID is null)");
            }
            return RenderUtility::DEFAULT_SLOT_ID_FOR_TEST;
        }
        return $slot;
    }


}

PluginUtility::init();
