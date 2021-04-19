<?php


namespace ComboStrap;


use syntax_plugin_combo_preformatted;
use TestRequest;

require_once(__DIR__ . '/LogUtility.php');
require_once(__DIR__ . '/FsWikiUtility.php');
require_once(__DIR__ . '/Icon.php');
require_once(__DIR__ . '/StringUtility.php');
require_once(__DIR__ . '/ColorUtility.php');
require_once(__DIR__ . '/RenderUtility.php');
require_once(__DIR__ . '/SnippetManager.php');
require_once(__DIR__ . '/Resources.php');
require_once(__DIR__ . '/Animation.php');
require_once(__DIR__ . '/Position.php');
require_once(__DIR__ . '/Bootstrap.php');
require_once(__DIR__ . '/Shadow.php');
require_once(__DIR__ . '/TagAttributes.php');
require_once(__DIR__ . '/File.php');


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
    const CONTENT = 'content';
    const TAG = "tag";

    /**
     * The name of the hidden/private namespace
     * where the icon and other artifactory are stored
     */
    const COMBOSTRAP_NAMESPACE_NAME = "combostrap";

    /**
     * List of inline components
     * Used to manage white space before an unmatched string.
     * The syntax tree of Dokuwiki (ie {@link \Doku_Handler::$calls})
     * has only data and no class, for now, we create this
     * lists manually because this is a hassle to retrieve this information from {@link \DokuWiki_Syntax_Plugin::getType()}
     */
    const PRESERVE_LEFT_WHITE_SPACE_COMPONENTS = array(
        /**
         * The inline of combo
         */
        \syntax_plugin_combo_link::TAG,
        \syntax_plugin_combo_icon::TAG,
        \syntax_plugin_combo_inote::TAG,
        \syntax_plugin_combo_button::TAG,
        \syntax_plugin_combo_tooltip::TAG,
        /**
         * Formatting https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
         * Comes from the {@link \dokuwiki\Parsing\ParserMode\Formatting} class
         */
        "strong",
        "emphasis",
        "underline",
        "monospace",
        "subscript",
        "superscript",
        "deleted",
        "footnote",
        /**
         * Others
         */
        "acronym"
    );

    const PARENT = "parent";
    const POSITION = "position";

    /**
     * Class to center an element
     */
    const CENTER_CLASS = "mx-auto";
    const RIGHT_CLASS = "float-right";

    const EDIT_SECTION_TARGET = 'section';


    /**
     * The URL base of the documentation
     */
    static $URL_BASE;


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
        self::$URL_BASE = "https://" . parse_url(self::$INFO_PLUGIN['url'], PHP_URL_HOST);

        PluginUtility::initSnippetManager();

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
     * @param $tag
     * @return string
     *
     * A mode is just a name for a class
     * Example: $Parser->addMode('listblock',new Doku_Parser_Mode_ListBlock());
     */
    public static function getModeForComponent($tag)
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
     * @param $tag
     * @return string
     *
     * Create a open tag pattern without lookahead.
     * Used for https://dev.w3.org/html5/html-author/#void-elements-0
     */
    public static function getVoidElementTagPattern($tag)
    {
        return '<' . $tag . '.*?>';
    }


    /**
     * Process the internal attributes and makes them
     * HTML compatible
     * @param $attributes
     */
    public static function array2HTMLAttributesAsArray(&$attributes)
    {
        /**
         * Temporary code to Migration to array to the new one
         * The attribute with the new format first
         * and the older one at the end
         */
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
        $tagAttributes->process();
        $attributes = $tagAttributes->toCallStackArray();


    }

    /**
     * Take an array  where the key is the attribute name
     * and return a HTML tag string
     *
     * The attribute name and value are escaped
     *
     * @param $attributes - combo attributes
     * @return string
     */
    public static function array2HTMLAttributesAsString($attributes)
    {

        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
        return $tagAttributes->toHTMLAttributeString();

    }

    /**
     *
     * Example:
     *   line-numbers="value"
     *   line-numbers='value'
     * @param $string
     * @return array
     *
     * Parse a string to HTML attribute
     */
    public static function parse2HTMLAttributes($string)
    {

        $parameters = array();

        // /i not case sensitive
        $attributePattern = "\\s*([-\w]+)\\s*=\\s*[\'\"]{1}([^\`\"]*)[\'\"]{1}\\s*";
        $result = preg_match_all('/' . $attributePattern . '/i', $string, $matches);
        if ($result != 0) {
            foreach ($matches[1] as $key => $parameterKey) {
                $parameters[hsc(strtolower($parameterKey))] = hsc($matches[2][$key]);
            }
        }
        return $parameters;

    }

    public static function getTagAttributes($match)
    {
        return self::getQualifiedTagAttributes($match, false, "");
    }

    /**
     * Return the attribute of a tag
     * Because they are users input, they are all escaped
     * @param $match
     * @param $hasThirdValue - if true, the third parameter is treated as value, not a property and returned in the `third` key
     * use for the code/file/console where they accept a name as third value
     * @param $keyThirdArgument - if a third argument is found, return it with this key
     * @return array
     */
    public static function getQualifiedTagAttributes($match, $hasThirdValue, $keyThirdArgument)
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
        if (!strpos($nextArgument, "=")) {
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
        $parsedAttributes = self::parse2HTMLAttributes($match);

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
    public static function getEmptyTagPattern($tag)
    {
        return '<' . $tag . '.*?/>';
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
        return RenderUtility::renderText2Xhtml($pageContent);
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
                    $attributes->addStyleDeclaration($key, $value);
                }
            }
        }

        // Skin
        $skinAttributes = "skin";
        if ($attributes->hasComponentAttribute($skinAttributes)) {
            $skinValue = $attributes->getValueAndRemove($skinAttributes);
            if ($attributes->hasComponentAttribute("type")) {
                $type = $attributes->getValueAndRemove("type");
                if (isset(ColorUtility::$colors[$type])) {
                    $color = ColorUtility::$colors[$type];
                    switch ($skinValue) {
                        case "contained":
                            $attributes->addStyleDeclaration(ColorUtility::COLOR, $color[ColorUtility::COLOR]);
                            $attributes->addStyleDeclaration(ColorUtility::BACKGROUND_COLOR, $color[ColorUtility::BACKGROUND_COLOR]);
                            $attributes->addStyleDeclaration(ColorUtility::BORDER_COLOR, $color[ColorUtility::BORDER_COLOR]);
                            Shadow::addMediumElevation($attributes);
                            break;
                        case "filled":
                        case "solid":
                            $attributes->addStyleDeclaration(ColorUtility::COLOR, $color[ColorUtility::COLOR]);
                            $attributes->addStyleDeclaration(ColorUtility::BACKGROUND_COLOR, $color[ColorUtility::BACKGROUND_COLOR]);
                            $attributes->addStyleDeclaration(ColorUtility::BORDER_COLOR, $color[ColorUtility::BORDER_COLOR]);
                            break;
                        case "outline":
                            $primaryColor = $color[ColorUtility::COLOR];
                            if ($primaryColor === "#fff") {
                                $primaryColor = $color[ColorUtility::BACKGROUND_COLOR];
                            }
                            $attributes->addStyleDeclaration(ColorUtility::COLOR, $primaryColor);
                            $attributes->addStyleDeclaration(ColorUtility::BACKGROUND_COLOR, "transparent");
                            $borderColor = $color[ColorUtility::BACKGROUND_COLOR];
                            if ($attributes->hasStyleDeclaration(ColorUtility::BORDER_COLOR)) {
                                // Color in the `border` attribute
                                // takes precedence in the `border-color` if located afterwards
                                // We don't take the risk
                                $borderColor = $attributes->getAndRemoveStyleDeclaration(ColorUtility::BORDER_COLOR);
                            }
                            $attributes->addStyleDeclaration("border", "1px solid " . $borderColor);

                            break;
                        case "text":
                            $primaryColor = $color[ColorUtility::COLOR];
                            if ($primaryColor === "#fff") {
                                $primaryColor = $color[ColorUtility::BACKGROUND_COLOR];
                            }
                            $attributes->addStyleDeclaration(ColorUtility::COLOR, $primaryColor);
                            $attributes->addStyleDeclaration(ColorUtility::BACKGROUND_COLOR, "transparent");
                            $attributes->addStyleDeclaration(ColorUtility::BORDER_COLOR, "transparent");
                            break;
                    }
                }
            }
        }

        // Color
        $colorAttributes = ["color", "background-color", "border-color"];
        foreach ($colorAttributes as $colorAttribute) {
            if ($attributes->hasComponentAttribute($colorAttribute)) {
                $colorValue = $attributes->getValueAndRemove($colorAttribute);
                $gradientPrefix = 'gradient-';
                if (strpos($colorValue, $gradientPrefix) === 0) {
                    $mainColorValue = substr($colorValue, strlen($gradientPrefix));
                    $attributes->addStyleDeclaration('background-image', 'linear-gradient(to top,#fff 0,' . self::getColorValue($mainColorValue) . ' 100%)');
                    $attributes->addStyleDeclaration('background-color', 'unset!important');
                } else {
                    $attributes->addStyleDeclaration($colorAttribute, self::getColorValue($colorValue));
                }

                if ($colorAttribute == "border-color") {
                    self::checkDefaultBorderColorAttributes($attributes);
                }


            }
        }

        $widthName = TagAttributes::WIDTH_KEY;
        if ($attributes->hasComponentAttribute($widthName)) {

            $widthValue = trim($attributes->getValueAndRemove($widthName));
            if ($widthValue == "fit") {
                $widthValue = "fit-content";
            }

            if (in_array($attributes->getLogicalTag(), TagAttributes::NATURAL_SIZING_ELEMENT)) {
                /**
                 * For an image, if the max-width is bigger than the screen, the image is out of the screen
                 */
                if (is_numeric($widthValue)) {
                    $qualifiedWidthValue = TagAttributes::toPixelLengthIfNoSpecified($widthValue);
                    $widthSinceBreakpoint = RasterImageLink::BREAKPOINTS["lg"];
                    foreach (RasterImageLink::BREAKPOINTS as $breakpoint) {
                        if ($widthValue < $breakpoint) {
                            $widthSinceBreakpoint = $breakpoint;
                            break;
                        }
                    }
                    /**
                     * Media CSS declaration
                     * cannot be inline
                     * We inject then dynamically a rule
                     * max-width applies only for screen bigger than the width
                     */
                    $onTheFlyClass = "dynamic-width-$widthValue";
                    $styleDeclaration ="@media (min-width: ${widthSinceBreakpoint}px) { .$onTheFlyClass { max-width: $qualifiedWidthValue } } .$onTheFlyClass { max-width:100% }";
                    $attributes->addClassName($onTheFlyClass);
                    PluginUtility::getSnippetManager()->attachCssSnippetForBar($onTheFlyClass,$styleDeclaration);
                }
            } else {
                $attributes->addStyleDeclaration('max-width', TagAttributes::toPixelLengthIfNoSpecified($widthValue));
            }

        }

        $heightName = TagAttributes::HEIGHT_KEY;
        if ($attributes->hasComponentAttribute($heightName)) {
            $heightValue = trim($attributes->getValueAndRemove($heightName));


            if (in_array($attributes->getLogicalTag(), TagAttributes::NATURAL_SIZING_ELEMENT)) {
                // A element with a natural height is responsive, we set only the max-height
                // the height would make it non-responsive
                $attributes->addStyleDeclaration("max-height", $heightValue);
            } else {
                // Without the height value, a block display will collapse
                $attributes->addStyleDeclaration("height", $heightValue);
            }
            /**
             * Overflow auto means that positioning element on the edge with the
             * will clip them with the {@link Position::processPosition()} position attribute
             *
             * if (!array_key_exists("overflow", $attributes)) {
             *        $styleProperties["overflow"] = "auto";
             * }
             */
        }

        $textAlign = "text-align";
        if ($attributes->hasComponentAttribute($textAlign)) {
            $textAlignValue = trim($attributes->getValueAndRemove($textAlign));
            $attributes->addStyleDeclaration($textAlign, $textAlignValue);
        }

        Shadow::process($attributes);


    }

    /**
     * Return a combostrap value to a web color value
     * @param string $color a color value
     * @return string
     */
    public
    static function getColorValue($color)
    {
        if ($color[0] == "#") {
            $colorValue = $color;
        } else {
            // Custom Css variable
            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
            switch ($bootstrapVersion) {
                case Bootstrap::BootStrapFiveMajorVersion:
                    $colorValue = "bs-" . $color;
                    break;
                default:
                    $colorValue = $color;
                    break;
            }
            $colorValue = "var(--" . $colorValue . ")";

        }
        return $colorValue;
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
     * @param $text -  the text of the link
     * @param bool $withIcon - used to break the recursion with the message in the {@link Icon}
     * @return string - an url
     */
    public
    static function getUrl($canonical, $text, $withIcon = true)
    {
        /** @noinspection SpellCheckingInspection */

        $icon = "";
        if ($withIcon) {

            /**
             * The best would be
             */
            //$icon = "<img src=\"https://combostrap.com/_media/logo.svg\" width='40px'/>";
            $icon = "<object type=\"image/svg+xml\" data=\"https://combostrap.com/_media/logo.svg\" style=\"max-width: 16px\"></object>";

        }
        return $icon . ' <a href="' . self::$URL_BASE . '/' . str_replace(":", "/", $canonical) . '" title="' . $text . '">' . $text . '</a>';
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
     * @return string
     */
    public
    static function getPageId()
    {
        global $ID;
        global $INFO;
        $callingId = $ID;
        // If the component is in a sidebar, we don't want the ID of the sidebar
        // but the ID of the page.
        if ($INFO != null) {
            $callingId = $INFO['id'];
        }
        return $callingId;
    }

    /**
     * Transform special HTML characters to entity
     * Example:
     * <hello>world</hello>
     * to
     * "&lt;hello&gt;world&lt;/hello&gt;"
     *
     * @param $text
     * @return string
     */
    public
    static function htmlEncode($text)
    {
        return htmlspecialchars($text, ENT_QUOTES);
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
     * @param TagAttributes $attributes
     */
    public
    static function processAlignAttributes(&$attributes)
    {
        // The class shortcut
        $align = TagAttributes::ALIGN_KEY;
        if ($attributes->hasComponentAttribute($align)) {
            $alignValue = $attributes->getValueAndRemove($align);
            switch ($alignValue) {
                case "center":
                    $attributes->addClassName(PluginUtility::CENTER_CLASS);
                    break;
                case "right":
                    $attributes->addClassName(PluginUtility::RIGHT_CLASS);

                    // position relative and z-index are needed to put the float above
                    $attributes->addStyleDeclaration("position", "relative!important");
                    $attributes->addStyleDeclaration("z-index", 1);
                    break;
            }

            /**
             * For inline element,
             * center should be a block
             * (svg is not a block by default for instance)
             * ! this should not be the case for flex block such as a row !
             */
            if (in_array($attributes->getLogicalTag(), TagAttributes::INLINE_LOGICAL_ELEMENTS)) {
                $attributes->addClassName("d-block");
            }
        }
    }

    /**
     * Process the attributes that have an impact on the class
     * @param TagAttributes $attributes
     */
    public
    static function processSpacingAttributes(&$attributes)
    {

        // Spacing is just a class
        $spacing = "spacing";
        if ($attributes->hasComponentAttribute($spacing)) {

            $spacingValue = $attributes->getValueAndRemove($spacing);

            $spacingNames = preg_split("/\s/", $spacingValue);
            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
            foreach ($spacingNames as $spacingClass) {
                if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion) {

                    // The sides r and l has been renamed to e and s
                    // https://getbootstrap.com/docs/5.0/migration/#utilities-2
                    //

                    // https://getbootstrap.com/docs/5.0/utilities/spacing/
                    // By default, we consider tha there is no size and breakpoint
                    $sizeAndBreakPoint = "";
                    $propertyAndSide = $spacingClass;

                    $minusCharacter = "-";
                    $minusLocation = strpos($spacingClass, $minusCharacter);
                    if ($minusLocation !== false) {
                        // There is no size or break point
                        $sizeAndBreakPoint = substr($spacingClass, $minusLocation + 1);
                        $propertyAndSide = substr($spacingClass, 0, $minusLocation);
                    }
                    $propertyAndSide = str_replace("r", "e", $propertyAndSide);
                    $propertyAndSide = str_replace("l", "s", $propertyAndSide);
                    if (empty($sizeAndBreakPoint)) {
                        $spacingClass = $propertyAndSide;
                    } else {
                        $spacingClass = $propertyAndSide . $minusCharacter . $sizeAndBreakPoint;
                    }

                }
                $attributes->addClassName($spacingClass);
            }
        }

    }

    /**
     * Add a style property to the attributes
     * @param $property
     * @param $value
     * @param array $attributes
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
            $tagAttributes->addStyleDeclaration("border-width", "1px");
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
            $tagAttributes->addStyleDeclaration("border-style", "solid");

        }
        if (!$tagAttributes->hasStyleDeclaration("border-radius")) {
            $tagAttributes->addStyleDeclaration("border-radius", ".25rem");
        }

    }

    public
    static function getConfValue($confName, $defaultValue = null)
    {
        global $conf;
        if (isset($conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][$confName])) {
            return $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][$confName];
        } else {
            return $defaultValue;
        }
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
     * The collapse attribute are the same
     * for all component except a link
     * @param TagAttributes $attributes
     */
    public
    static function processCollapse(&$attributes)
    {

        $collapse = "collapse";
        if ($attributes->hasComponentAttribute($collapse)) {
            $targetId = $attributes->getValueAndRemove($collapse);
            $attributes->addComponentAttributeValue('data-toggle', "collapse");
            $attributes->addComponentAttributeValue('data-target', $targetId);
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

    /**
     * @param $TAG - the name of the tag that should correspond to the name of the css file in the style directory
     * @return string - a inline style element to inject in the page or blank if no file exists
     */
    public
    static function getTagStyle($TAG)
    {
        $script = self::getCssRules($TAG);
        if (!empty($script)) {
            return "<style>" . $script . "</style>";
        } else {
            return "";
        }

    }


    /**
     * Utility function to disable preformatted
     * @param $mode
     * @return bool
     */
    public
    static function disablePreformatted($mode)
    {
        if (
            $mode == 'preformatted'
            ||
            $mode == PluginUtility::getModeForComponent(syntax_plugin_combo_preformatted::TAG)
        ) {
            return false;
        } else {
            return true;
        }
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
    static function getSnippetManager()
    {
        return SnippetManager::get();
    }

    public
    static function initSnippetManager()
    {
        SnippetManager::init();
    }

    /**
     * Function used in a render
     * @param $data - the data from {@link PluginUtility::handleAndReturnUnmatchedData()}
     * @return string
     */
    public
    static function renderUnmatched($data)
    {

        $payload = $data[self::PAYLOAD];
        $context = $data[self::CONTEXT];
        if (!in_array($context, self::PRESERVE_LEFT_WHITE_SPACE_COMPONENTS)) {
            $payload = ltrim($payload);
        }
        return PluginUtility::htmlEncode($payload);
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
    static function handleAndReturnUnmatchedData($tagName, $match, \Doku_Handler $handler)
    {
        $tag = new Tag($tagName, array(), DOKU_LEXER_UNMATCHED, $handler);
        $sibling = $tag->getPreviousSibling();
        $context = null;
        if (!empty($sibling)) {
            $context = $sibling->getName();
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
        if ($namespace != null) {
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

    public
    static function startSection($renderer, $position, $name)
    {
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


}

PluginUtility::init();
