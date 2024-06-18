<?php


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\ArrayUtility;
use ComboStrap\ButtonTag;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\DropDownTag;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\ThirdPartyPlugins;
use ComboStrap\Web\UrlEndpoint;

if (!defined('DOKU_INC')) die();

/**
 *
 * A link pattern to take over the link of Dokuwiki
 * and transform it as a bootstrap link
 *
 * The handle of the move of link is to be found in the
 * admin action {@link action_plugin_combo_linkmove}
 *
 * popular [[ wiki ]] syntax for linking notes
 * and makes it easy to build personal wikis,
 * team knowledge bases,
 * or something like a Second Brain or a Zettelkasten.
 *
 */
class syntax_plugin_combo_link extends DokuWiki_Syntax_Plugin
{
    const TAG = 'link';
    const COMPONENT = 'combo_link';

    /**
     * Disable the link component
     */
    const CONF_DISABLE_LINK = "disableLink";

    /**
     * The link Tag
     * a or p
     */
    const LINK_TAG = "linkTag";

    /**
     * Do the link component allows to be spawn on multilines
     */
    const CLICKABLE_ATTRIBUTE = "clickable";
    public const ATTRIBUTE_LABEL = 'label';
    /**
     * The key of the array for the handle cache
     */
    public const MARKUP_REF_ATTRIBUTE = 'ref';

    public const ATTRIBUTE_IMAGE_IN_LABEL = 'image-in-label';

    /**
     * A link may have a title or not
     * ie
     * [[path:page]]
     * [[path:page|title]]
     * are valid
     *
     * Get the content until one of this character is found:
     *   * |
     *   * or ]]
     *   * or \n (No line break allowed, too much difficult to debug)
     *   * and not [ (for two links on the same line)
     */
    public const ENTRY_PATTERN_SINGLE_LINE = "\[\[[^\|\]]*(?=[^\n\[]*\]\])";
    public const EXIT_PATTERN = "\]\]";


    /**
     * Dokuwiki Link pattern ter info
     * Found in {@link \dokuwiki\Parsing\ParserMode\Internallink}
     */
    const SPECIAL_PATTERN = "\[\[.*?\]\](?!\])";

    /**
     * The link title attribute (ie popup)
     */
    const TITLE_ATTRIBUTE = "title";
    const STRETCHED_LINK = "stretched-link";
    const CANONICAL = "link";


    /**
     * Parse the match of a syntax {@link DokuWiki_Syntax_Plugin} handle function
     * @param $match
     * @return string[] - an array with the attributes constant `ATTRIBUTE_xxxx` as key
     *
     * Code adapted from  {@link Doku_Handler::internallink()}
     */
    public static function parse($match): array
    {

        // Strip the opening and closing markup
        $linkString = preg_replace(array('/^\[\[/', '/]]$/u'), '', $match);

        // Split title from URL
        $linkArray = explode('|', $linkString, 2);

        // Id
        $attributes[self::MARKUP_REF_ATTRIBUTE] = trim($linkArray[0]);


        // Text or image
        if (!isset($linkArray[1])) {
            $attributes[self::ATTRIBUTE_LABEL] = null;
        } else {
            // An image in the title
            if (preg_match('/^{{[^}]+}}$/', $linkArray[1])) {
                // If the title is an image, convert it to an array containing the image details
                $attributes[self::ATTRIBUTE_IMAGE_IN_LABEL] = Doku_Handler_Parse_Media($linkArray[1]);
            } else {
                $attributes[self::ATTRIBUTE_LABEL] = $linkArray[1];
            }
        }

        return $attributes;

    }


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    function getAllowedTypes(): array
    {
        return array('substition', 'formatting', 'disabled');
    }

    /**
     * @param string $mode
     * @return bool
     * Accepts inside
     */
    public function accepts($mode): bool
    {
        /**
         * To avoid that the description if it contains a link
         * will be taken by the links mode
         *
         * For instance, [[https://hallo|https://hallo]] will send https://hallo
         * to the external link mode
         */
        $linkModes = [
            "externallink",
            "locallink",
            "internallink",
            "interwikilink",
            "emaillink",
            "emphasis", // double slash can not be used inside to preserve the possibility to write an URL in the description
            //"emphasis_open", // italic use // and therefore take over a link as description which is not handy when copying a tweet
            //"emphasis_close",
            //"acrnonym"
        ];
        if (in_array($mode, $linkModes)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * @see Doku_Parser_Mode::getSort()
     * The mode with the lowest sort number will win out
     */
    function getSort()
    {
        /**
         * It should be less than the number
         * at {@link \dokuwiki\Parsing\ParserMode\Internallink::getSort}
         * and the like
         *
         * For whatever reason, the number below should be less than 100,
         * otherwise on windows with DokuWiki Stick, the link syntax may be not taken
         * into account
         */
        return 99;
    }


    function connectTo($mode)
    {

        if (!$this->getConf(self::CONF_DISABLE_LINK, false)
            &&
            $mode !== PluginUtility::getModeFromPluginName(ThirdPartyPlugins::IMAGE_MAPPING_NAME)
        ) {

            $pattern = self::ENTRY_PATTERN_SINGLE_LINE;
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

        }

    }

    public function postConnect()
    {
        if (!$this->getConf(self::CONF_DISABLE_LINK, false)) {
            $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }
    }


    /**
     * The handler for an internal link
     * based on `internallink` in {@link Doku_Handler}
     * The handler call the good renderer in {@link Doku_Renderer_xhtml} with
     * the parameters (ie for instance internallink)
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $parsedArray = self::parse($match);
                $htmlAttributes = TagAttributes::createEmpty(self::TAG);

                /**
                 * The markup ref needs to be passed to the
                 * instructions stack (because we support link with a variable as markup ref
                 * via a {@link syntax_plugin_combo_fragment} in a {@link syntax_plugin_combo_iterator}
                 *
                 * The variable is replaced in the {@link syntax_plugin_combo_link::render()}
                 * at runtime while rendering
                 */
                $markupRef = $parsedArray[self::MARKUP_REF_ATTRIBUTE];
                if ($markupRef !== null) {
                    /**
                     * If the Rel is a wiki link, we make the path absolute and not relative
                     * (this is for the {@link \ComboStrap\FetcherPageBundler)}}
                     * otherwise the links are not good and are seen as non-existent
                     */
                    try {
                        $markupRefObject = MarkupRef::createLinkFromRef($markupRef);
                        $scheme = $markupRefObject->getSchemeType();
                        if ($scheme === MarkupRef::WIKI_URI) {

                            /**
                             * Properties?
                             */
                            $newProperties = [];
                            $url = $markupRefObject->getUrl();
                            foreach (array_keys($url->getQueryProperties()) as $propertyName) {
                                if ($propertyName === "id") {
                                    continue;
                                }
                                $queryPropertyValue = $url->getQueryPropertyValue($propertyName);
                                if (is_array($queryPropertyValue)) {
                                    foreach ($queryPropertyValue as $arrayValue) {
                                        $newProperties[] = $propertyName . "=" . $arrayValue;
                                    }
                                } else {
                                    $newProperties[] = $propertyName . "=" . $queryPropertyValue;
                                }
                            }
                            $markupRef = $markupRefObject->getPath()->toAbsoluteId();
                            if (count($newProperties) > 0) {
                                $queryString = implode("&", $newProperties);
                                $markupRef .= "?" . $queryString;
                            }
                            try {
                                $markupRef .= "#" . $url->getFragment();
                            } catch (ExceptionNotFound $e) {
                                // no fragment
                            }
                        }
                    } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound $e) {
                        // not a valid ref
                    }
                    $htmlAttributes->addComponentAttributeValue(self::MARKUP_REF_ATTRIBUTE, $markupRef);
                }


                /**
                 * Extra HTML attribute
                 */
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $parentName = "";
                if ($parent !== false) {

                    /**
                     * Button Link
                     * Getting the attributes
                     */
                    $parentName = $parent->getTagName();
                    if ($parentName == ButtonTag::MARKUP_LONG) {
                        $htmlAttributes->mergeWithCallStackArray($parent->getAttributes());
                    }

                    /**
                     * Searching Clickable parent
                     */
                    $maxLevel = 3;
                    $level = 0;
                    while (
                        $parent != false &&
                        !$parent->hasAttribute(self::CLICKABLE_ATTRIBUTE) &&
                        $level < $maxLevel
                    ) {
                        $parent = $callStack->moveToParent();
                        $level++;
                    }
                    if ($parent !== false) {
                        if ($parent->getAttribute(self::CLICKABLE_ATTRIBUTE)) {
                            $htmlAttributes->addClassName(self::STRETCHED_LINK);
                            $parent->addClassName("position-relative");
                            $parent->removeAttribute(self::CLICKABLE_ATTRIBUTE);
                        }
                    }

                }
                $returnedArray[PluginUtility::STATE] = $state;
                $returnedArray[PluginUtility::ATTRIBUTES] = $htmlAttributes->toCallStackArray();
                $returnedArray[PluginUtility::CONTEXT] = $parentName;
                return $returnedArray;

            case DOKU_LEXER_UNMATCHED:

                $data = PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);
                /**
                 * Delete the separator `|` between the ref and the description if any
                 */
                $tag = CallStack::createFromHandler($handler);
                $parent = $tag->moveToParent();
                if ($parent->getTagName() == self::TAG) {
                    if (strpos($match, '|') === 0) {
                        $data[PluginUtility::PAYLOAD] = substr($match, 1);
                    }
                }
                return $data;

            case DOKU_LEXER_EXIT:

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                $openingAttributes = $openingTag->getAttributes();
                $openingPosition = $openingTag->getKey();

                $callStack->moveToEnd();
                $previousCall = $callStack->previous();
                $previousCallPosition = $previousCall->getKey();
                $previousCallContent = $previousCall->getCapturedContent();

                /**
                 * Link label
                 * is set if there is no content
                 * between enter and exit node
                 */
                $linkLabel = "";
                if (
                    $openingPosition == $previousCallPosition // ie [[id]]
                    ||
                    ($openingPosition == $previousCallPosition - 1 && $previousCallContent == "|") // ie [[id|]]
                ) {
                    // There is no name
                    $markupRef = $openingTag->getAttribute(self::MARKUP_REF_ATTRIBUTE);
                    if ($markupRef !== null) {
                        try {
                            $linkLabel = LinkMarkup::createFromRef($markupRef)
                                ->getDefaultLabel();
                        } catch (ExceptionCompile $e) {
                            LogUtility::error("No default Label can be defined. Error while parsing the markup ref ($markupRef). Error: {$e->getMessage()}", self::CANONICAL, $e);
                        }

                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingAttributes,
                    PluginUtility::PAYLOAD => $linkLabel,
                    PluginUtility::CONTEXT => $openingTag->getContext()
                );
        }
        return true;


    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        // The data
        $state = $data[PluginUtility::STATE];
        switch ($format) {
            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */
                /**
                 * Cache problem may occurs while releasing
                 */
                if (isset($data[PluginUtility::ATTRIBUTES])) {
                    $callStackAttributes = $data[PluginUtility::ATTRIBUTES];
                } else {
                    $callStackAttributes = $data;
                }

                PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::TAG);

                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        $tagAttributes = TagAttributes::createFromCallStackArray($callStackAttributes, self::TAG);

                        $markupRef = $tagAttributes->getValueAndRemove(self::MARKUP_REF_ATTRIBUTE);
                        if ($markupRef === null) {
                            $message = "Internal Error: A link reference was not found";
                            LogUtility::internalError($message);
                            $renderer->doc .= LogUtility::wrapInRedForHtml($message);
                            return false;
                        }
                        try {
                            $markupRef = syntax_plugin_combo_variable::replaceVariablesWithValuesFromContext($markupRef);
                            $markupLink = LinkMarkup::createFromRef($markupRef);
                            $markupAttributes = $markupLink->toAttributes();
                        } catch (ExceptionCompile $e) {
                            // uncomment to get the original error stack trace in dev
                            // and see where the exception comes from
                            // Don't forget to comment back
//                            if (PluginUtility::isDevOrTest()) {
//                                throw new ExceptionRuntime("Error on markup ref", self::TAG, 0, $e);
//                            }

                            /**
                             * Error. Example: unknown inter-wiki ...
                             * We still create the a to be xhtml compliante
                             */
                            $url = UrlEndpoint::createSupportUrl();
                            $markupAttributes = TagAttributes::createEmpty()
                                ->addOutputAttributeValue("href", $url->toString())
                                ->addClassName(LinkMarkup::getHtmlClassNotExist());
                            $renderer->doc .= $markupAttributes->toHtmlEnterTag("a") . $e->getMessage();

                            LogUtility::warning($e->getMessage(), "link", $e);
                            return false;

                        }
                        // markup attributes is leading because it has already output attribute such as href
                        $markupAttributes->mergeWithCallStackArray($tagAttributes->toCallStackArray());
                        $tagAttributes = $markupAttributes;


                        /**
                         * Extra styling
                         */
                        $parentTag = $data[PluginUtility::CONTEXT];
                        $htmlPrefix = "";
                        switch ($parentTag) {
                            /**
                             * Button link
                             */
                            case ButtonTag::MARKUP_LONG:
                                $tagAttributes->addOutputAttributeValue("role", "button");
                                ButtonTag::processButtonAttributesToHtmlAttributes($tagAttributes);
                                break;
                            case DropDownTag::TAG:
                                $tagAttributes->addClassName("dropdown-item");
                                break;
                            case syntax_plugin_combo_navbarcollapse::COMPONENT:
                                $tagAttributes->addClassName("navbar-link");
                                $htmlPrefix = '<div class="navbar-nav">';
                                break;
                            case syntax_plugin_combo_navbargroup::COMPONENT:
                                $tagAttributes->addClassName("nav-link");
                                $htmlPrefix = '<li class="nav-item">';
                                break;
                            default:
                            case syntax_plugin_combo_badge::TAG:
                            case syntax_plugin_combo_cite::TAG:
                            case syntax_plugin_combo_contentlistitem::DOKU_TAG:
                            case syntax_plugin_combo_preformatted::TAG:
                                break;

                        }

                        /**
                         * Add it to the rendering
                         */
                        $renderer->doc .= $htmlPrefix . $tagAttributes->toHtmlEnterTag("a");
                        break;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        break;
                    case DOKU_LEXER_EXIT:

                        // if there is no link name defined, we get the name as ref in the payload
                        // otherwise null string
                        $renderer->doc .= $data[PluginUtility::PAYLOAD] ?? '';

                        // Close the link
                        $renderer->doc .= "</a>";

                        // Close the html wrapper element
                        $context = $data[PluginUtility::CONTEXT];
                        switch ($context) {
                            case syntax_plugin_combo_navbarcollapse::COMPONENT:
                                $renderer->doc .= '</div>';
                                break;
                            case syntax_plugin_combo_navbargroup::COMPONENT:
                                $renderer->doc .= '</li>';
                                break;
                        }

                }
                return true;
            case 'metadata':

                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        /**
                         * Keep track of the backlinks ie meta['relation']['references']
                         * @var Doku_Renderer_metadata $renderer
                         */
                        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                        $markupRef = $tagAttributes->getValue(self::MARKUP_REF_ATTRIBUTE);
                        if ($markupRef === null) {
                            LogUtility::internalError("The markup ref was not found for a link.");
                            return false;
                        }
                        try {
                            $type = MarkupRef::createLinkFromRef($markupRef)
                                ->getSchemeType();
                        } catch (ExceptionCompile $e) {
                            LogUtility::error("The markup ref could not be parsed. Error:{$e->getMessage()}");
                            return false;
                        }
                        $name = $tagAttributes->getValue(self::ATTRIBUTE_LABEL);

                        switch ($type) {
                            case MarkupRef::WIKI_URI:
                                /**
                                 * The relative link should be passed (ie the original)
                                 * Dokuwiki has a default description
                                 * We can't pass empty or the array(title), it does not work
                                 */
                                $descriptionToDelete = "b";
                                $renderer->internallink($markupRef, $descriptionToDelete);
                                $renderer->doc = substr($renderer->doc, 0, -strlen($descriptionToDelete));
                                break;
                            case MarkupRef::WEB_URI:
                                $renderer->externallink($markupRef, $name);
                                break;
                            case MarkupRef::LOCAL_URI:
                                $renderer->locallink($markupRef, $name);
                                break;
                            case MarkupRef::EMAIL_URI:
                                $renderer->emaillink($markupRef, $name);
                                break;
                            case MarkupRef::INTERWIKI_URI:
                                $interWikiSplit = preg_split("/>/", $markupRef);
                                $renderer->interwikilink($markupRef, $name, $interWikiSplit[0], $interWikiSplit[1]);
                                break;
                            case MarkupRef::WINDOWS_SHARE_URI:
                                $renderer->windowssharelink($markupRef, $name);
                                break;
                            case MarkupRef::VARIABLE_URI:
                                // No backlinks for link template
                                break;
                            default:
                                LogUtility::msg("The markup reference ({$markupRef}) with the type $type was not processed into the metadata");
                        }

                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        break;
                }
                break;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                if ($state === DOKU_LEXER_ENTER) {
                    /**
                     *
                     * @var renderer_plugin_combo_analytics $renderer
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $ref = $tagAttributes->getValue(self::MARKUP_REF_ATTRIBUTE);
                    try {
                        $markupRef = LinkMarkup::createFromRef($ref);
                    } catch (ExceptionCompile $e) {
                        LogUtility::error("Error while parsing the ref ($ref). Error: {$e->getMessage()}. No further analytics.");
                        return false;
                    }
                    $refType = $markupRef->getMarkupRef()->getSchemeType();


                    /**
                     * @param array $stats
                     * Calculate internal link statistics
                     */
                    $stats = &$renderer->stats;
                    switch ($refType) {

                        case MarkupRef::WIKI_URI:

                            /**
                             * Internal link count
                             */
                            if (!array_key_exists(renderer_plugin_combo_analytics::INTERNAL_LINK_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::INTERNAL_LINK_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::INTERNAL_LINK_COUNT]++;


                            /**
                             * Broken link ?
                             */
                            try {
                                $path = $markupRef->getMarkupRef()->getPath();
                                $linkedPage = MarkupPath::createPageFromPathObject($path);
                                if (!FileSystems::exists($path)) {
                                    $internalLinkBroken = $stats[renderer_plugin_combo_analytics::INTERNAL_LINK_BROKEN_COUNT] ?? 0;
                                    $stats[renderer_plugin_combo_analytics::INTERNAL_LINK_BROKEN_COUNT] = $internalLinkBroken + 1;
                                    $stats[renderer_plugin_combo_analytics::INFO][] = "The internal linked page `{$linkedPage}` does not exist";
                                }
                            } catch (ExceptionNotFound $e) {
                                // no local path
                            }

                            /**
                             * Calculate link distance
                             */
                            global $ID;
                            $id = $linkedPage->getWikiId();
                            $a = explode(':', getNS($ID));
                            $b = explode(':', getNS($id));
                            while (isset($a[0]) && $a[0] == $b[0]) {
                                array_shift($a);
                                array_shift($b);
                            }
                            $length = count($a) + count($b);
                            $stats[renderer_plugin_combo_analytics::INTERNAL_LINK_DISTANCE][] = $length;
                            break;

                        case MarkupRef::WEB_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::EXTERNAL_LINK_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::EXTERNAL_LINK_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::EXTERNAL_LINK_COUNT]++;
                            break;

                        case MarkupRef::LOCAL_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::LOCAL_LINK_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::LOCAL_LINK_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::LOCAL_LINK_COUNT]++;
                            break;

                        case MarkupRef::INTERWIKI_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::INTERWIKI_LINK_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::INTERWIKI_LINK_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::INTERWIKI_LINK_COUNT]++;
                            break;

                        case MarkupRef::EMAIL_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::EMAIL_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::EMAIL_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::EMAIL_COUNT]++;
                            break;

                        case MarkupRef::WINDOWS_SHARE_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::WINDOWS_SHARE_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::WINDOWS_SHARE_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::WINDOWS_SHARE_COUNT]++;
                            break;

                        case MarkupRef::VARIABLE_URI:

                            if (!array_key_exists(renderer_plugin_combo_analytics::TEMPLATE_LINK_COUNT, $stats)) {
                                $stats[renderer_plugin_combo_analytics::TEMPLATE_LINK_COUNT] = 0;
                            }
                            $stats[renderer_plugin_combo_analytics::TEMPLATE_LINK_COUNT]++;
                            break;

                        default:

                            LogUtility::msg("The link `{$ref}` with the type ($refType)  is not taken into account into the statistics");

                    }


                    break;
                }

        }

        return false;
    }


    /**
     * Utility function to add a link into the callstack
     * @param CallStack $callStack
     * @param TagAttributes $tagAttributes
     */
    public
    static function addOpenLinkTagInCallStack(CallStack $callStack, TagAttributes $tagAttributes)
    {
        $parent = $callStack->moveToParent();
        $context = "";
        $attributes = $tagAttributes->toCallStackArray();
        if ($parent !== false) {
            $context = $parent->getTagName();
            if ($context === ButtonTag::MARKUP_LONG) {
                // the link takes by default the data from the button
                $parentAttributes = $parent->getAttributes();
                if ($parentAttributes !== null) {
                    $attributes = ArrayUtility::mergeByValue($parentAttributes, $attributes);
                }
            }
        }
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_ENTER,
                $attributes,
                $context
            ));
    }

    public
    static function addExitLinkTagInCallStack(CallStack $callStack)
    {
        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_link::TAG,
                DOKU_LEXER_EXIT
            ));
    }
}

