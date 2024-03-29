<?php


namespace ComboStrap;


use ComboStrap\Api\ApiRouter;
use dokuwiki\Extension\Plugin;
use dokuwiki\Extension\SyntaxPlugin;


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


    const EXIT_MESSAGE = "exit_message";
    const EXIT_CODE = "exit_code";

    const DISPLAY = "display";
    const MARKUP_TAG = "markup-tag";


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
     * @var LocalPath
     */
    private static $PLUGIN_INFO_FILE;


    /**
     * Initiate the static variable
     * See the call after this class
     */
    static function init()
    {

        $pluginInfoFile = DirectoryLayout::getPluginInfoPath();
        self::$INFO_PLUGIN = confToHash($pluginInfoFile->toAbsoluteId());
        self::$PLUGIN_NAME = 'ComboStrap';
        global $lang;
        self::$PLUGIN_LANG = $lang[self::PLUGIN_BASE_NAME] ?? null;
        self::$URL_APEX = "https://" . parse_url(self::$INFO_PLUGIN['url'], PHP_URL_HOST);
        //self::$VERSION = self::$INFO_PLUGIN['version'];

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
        return ' < ' . $tag . ' .*?>';
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

    public static function getTagAttributes(string $match, array $knownTypes = [], bool $allowFirstBooleanAttributesAsType = false): array
    {
        return self::getQualifiedTagAttributes($match, false, "", $knownTypes, $allowFirstBooleanAttributesAsType);
    }

    /**
     * Return the attribute of a tag
     * Because they are users input, they are all escaped
     * @param $match
     * @param $hasThirdValue - if true, the third parameter is treated as value, not a property and returned in the `third` key
     * use for the code/file/console where they accept a name as third value
     * @param $keyThirdArgument - if a third argument is found, return it with this key
     * @param array|null $knownTypes
     * @param bool $allowFirstBooleanAttributesAsType
     * @return array
     */
    public static function getQualifiedTagAttributes($match, $hasThirdValue, $keyThirdArgument, array $knownTypes = [], bool $allowFirstBooleanAttributesAsType = false): array
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

        /**
         * Do we have a type as first argument ?
         */
        $attributes = array();
        $spacePosition = strpos($match, " ");
        if ($spacePosition) {
            $nextArgument = substr($match, 0, $spacePosition);
        } else {
            $nextArgument = $match;
        }

        $isBooleanAttribute = !strpos($nextArgument, "=");
        $isType = false;
        if ($isBooleanAttribute) {
            $possibleTypeLowercase = strtolower($nextArgument);
            if ($allowFirstBooleanAttributesAsType) {
                $isType = true;
                $nextArgument = $possibleTypeLowercase;
            } else {
                if (!empty($knownTypes) && in_array($possibleTypeLowercase, $knownTypes)) {
                    $isType = true;
                    $nextArgument = $possibleTypeLowercase;
                }
            }
        }
        if ($isType) {

            $attributes[TagAttributes::TYPE_KEY] = $nextArgument;
            /**
             * Suppress the type
             */
            $match = substr($match, strlen($nextArgument));
            $match = trim($match);

            /**
             * Do we have a value as first argument ?
             */
            if (!empty($hasThirdValue)) {
                $spacePosition = strpos($match, " ");
                if ($spacePosition) {
                    $nextArgument = substr($match, 0, $spacePosition);
                } else {
                    $nextArgument = $match;
                }
                if (!strpos($nextArgument, "=") && !empty($nextArgument)) {
                    $attributes[$keyThirdArgument] = $nextArgument;
                    /**
                     * Suppress the third argument
                     */
                    $match = substr($match, strlen($nextArgument));
                    $match = trim($match);
                }
            }
        }

        /**
         * Parse the remaining attributes
         */
        $parsedAttributes = self::parseAttributes($match);

        /**
         * Merge
         */
        $attributes = array_merge($attributes, $parsedAttributes);;

        return $attributes;

    }

    /**
     * @param $tag
     * @return string
     * Create a pattern used where the tag is not a container.
     * ie
     * <br/>
     *
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

    public static function getEmptyTagPatternGeneral(): string
    {

        return self::getEmptyTagPattern("[\w-]+");
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
    public static function render($pageContent): ?string
    {
        return MarkupRenderUtility::renderText2XhtmlAndStripPEventually($pageContent, false);
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
     * @deprecated use {@link ApiRouter::getRequestParameter()}
     */
    public
    static function getPropertyValue($name, $default = null)
    {
        global $INPUT;
        $value = $INPUT->str($name);
        if ($value == null && defined('DOKU_UNITTEST')) {
            global $COMBO;
            if ($COMBO !== null) {
                $value = $COMBO[$name];
            }
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
     * @param bool $withIcon - used to break the recursion with the message in the {@link IconDownloader}
     * @return string - an url
     */
    public
    static function getDocumentationHyperLink($canonical, $label, bool $withIcon = true, $tooltip = ""): string
    {

        $xhtmlIcon = "";
        if ($withIcon) {

            $logoPath = WikiPath::createComboResource("images:logo.svg");
            try {
                $fetchImage = FetcherSvg::createSvgFromPath($logoPath);
                $fetchImage->setRequestedType(FetcherSvg::ICON_TYPE)
                    ->setRequestedWidth(20);
                $xhtmlIcon = SvgImageLink::createFromFetcher($fetchImage)
                    ->renderMediaTag();
            } catch (ExceptionCompile $e) {
                /**
                 * We don't throw because this function
                 * is also used by:
                 *   * the log functionality to show link to the documentation creating a loop
                 *   * inside the configuration description crashing the page
                 */
                if (PluginUtility::isDevOrTest()) {
// shows errors in the html only on dev/test
                    $xhtmlIcon = "Error: {$e->getMessage()}";
                }
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
     *
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
     *
     * Return the requested wiki id (known also as page id)
     *
     * If the code is rendering a sidebar, it will not return the id of the sidebar
     * but the requested wiki id
     *
     * @return string
     * @throws ExceptionNotFound
     * @deprecated use {@link ExecutionContext::getRequestedPath()}
     */
    public static function getRequestedWikiId(): string
    {

        return ExecutionContext::getActualOrCreateFromEnv()->getRequestedPath()->getWikiId();

    }

    public static function xmlEncode($text)
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

    /**
     * @param $match
     * @return null|string - return the tag name or null if not found
     */
    public
    static function getMarkupTag($match): ?string
    {

        // Until the first >
        $pos = strpos($match, ">");
        if (!$pos) {
            LogUtility::msg("The match does not contain any tag. Match: {$match}", LogUtility::LVL_MSG_ERROR);
            return null;
        }
        $match = substr($match, 0, $pos);

        // if this is a empty tag with / at the end we delete it
        if ($match[strlen($match) - 1] == "/") {
            $match = substr($match, 0, -1);
        }

        // Suppress the <
        if ($match[0] == "<") {
            $match = substr($match, 1);
            // closing tag
            if ($match[0] == "/") {
                $match = substr($match, 1);
            }
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


    public
    static function getComponentName($tag): string
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
     * @return SnippetSystem
     */
    public
    static function getSnippetManager(): SnippetSystem
    {
        return SnippetSystem::getFromContext();
    }


    /**
     * Function used in a render
     * @param $data - the data from {@link PluginUtility::handleAndReturnUnmatchedData()}
     * @return string
     *
     *
     */
    public
    static function renderUnmatched($data): string
    {
        /**
         * Attributes
         */
        $attributes = $data[PluginUtility::ATTRIBUTES] ?? [];
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

        /**
         * Display
         */
        $display = $tagAttributes->getValueAndRemoveIfPresent(Display::DISPLAY);
        if ($display === "none") {
            return "";
        }

        $payload = $data[self::PAYLOAD] ?? null;
        $previousTagDisplayType = $data[self::CONTEXT] ?? null;
        if ($previousTagDisplayType !== Call::INLINE_DISPLAY) {
            // Delete the eol at the beginning and end
            // otherwise we get a big block
            $payload = ltrim($payload);
        }
        return Html::encode($payload);

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
        if (!$pos) {
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
            DOKU_LEXER_EXIT,
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
        $remoteAddr = $_SERVER["REMOTE_ADDR"] ?? null;
        if ($remoteAddr == "127.0.0.1") {
            return true;
        }
        $computerName = $_SERVER["COMPUTERNAME"] ?? null;
        if ($computerName === "NICO") {
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
        return MarkupRenderUtility::getInstructionsAndStripPEventually($markiCode);
    }

    public static function isTest(): bool
    {
        return defined('DOKU_UNITTEST');
    }


    public static function getCacheManager(): CacheManager
    {
        return CacheManager::getFromContextExecution();
    }

    public static function getModeFromPluginName($name)
    {
        return "plugin_$name";
    }

    public static function isCi(): bool
    {
        // https://docs.travis-ci.com/user/environment-variables/#default-environment-variables
        // https://docs.github.com/en/actions/learn-github-actions/variables#default-environment-variables
        return getenv("CI") === "true";
    }


    /**
     * @throws ExceptionCompile
     */
    public static function renderInstructionsToXhtml($callStackHeaderInstructions): ?string
    {
        return MarkupRenderUtility::renderInstructionsToXhtml($callStackHeaderInstructions);
    }

    /**
     * @deprecated for {@link ExecutionContext::getExecutingWikiId()}
     */
    public static function getCurrentSlotId(): string
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getExecutingWikiId();
    }


}

PluginUtility::init();
