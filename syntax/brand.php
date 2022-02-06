<?php


// must be run within Dokuwiki
use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboNotFound;
use ComboStrap\Icon;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_brand extends DokuWiki_Syntax_Plugin
{

    const TAG = "brand";
    const CANONICAL = self::TAG;


    public const ICON_ATTRIBUTE = "icon";
    public const WIDGET_ATTRIBUTE = "widget";
    public const URL_ATTRIBUTE = "url";

    /**
     * Class needed
     * https://getbootstrap.com/docs/5.1/components/navbar/#image-and-text
     */
    const BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS = "d-inline-block align-text-top";

    /**
     * @throws ExceptionCombo
     */
    public static function mixBrandButtonToTagAttributes(TagAttributes $tagAttributes, BrandButton $brandButton)
    {
        $brandLinkAttributes = $brandButton->getLinkAttributes();
        $urlAttribute = syntax_plugin_combo_brand::URL_ATTRIBUTE;
        $url = $tagAttributes->getValueAndRemoveIfPresent($urlAttribute);
        if ($url !== null) {
            $page = Page::createPageFromRequestedPage();
            $relativePath = str_replace(":", "/", $page->getDokuwikiId());
            $url = TemplateUtility::renderStringTemplateFromDataArray($url, ["path" => $relativePath]);
            $tagAttributes->addHtmlAttributeValue("href", $url);
        }
        $tagAttributes->mergeWithCallStackArray($brandLinkAttributes->toCallStackArray());
    }


    /**
     * An utility constructor to be sure that we build the brand button
     * with the same data in the handle and render function
     * @throws ExceptionCombo
     */
    private static function createBrandButtonFromAttributes(TagAttributes $brandAttributes): BrandButton
    {
        $channelName = $brandAttributes->getValue(TagAttributes::TYPE_KEY, Brand::CURRENT_BRAND);
        $widget = $brandAttributes->getValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_BUTTON_VALUE);
        $icon = $brandAttributes->getValue(self::ICON_ATTRIBUTE, BrandButton::ICON_SOLID_VALUE);
        $brandButton = BrandButton::createBrandButton($channelName)
            ->setWidget($widget)
            ->setIconType($icon);

        $width = $brandAttributes->getValueAsInteger(Dimension::WIDTH_KEY);
        if ($width !== null) {
            $brandButton->setWidth($width);
        }
        $title = $brandAttributes->getValue(syntax_plugin_combo_link::TITLE_ATTRIBUTE);
        if ($title !== null) {
            $brandButton->setLinkTitle($title);
        }
        $color = $brandAttributes->getValue(ColorRgb::PRIMARY_VALUE);
        if ($color !== null) {
            $brandButton->setPrimaryColor($color);
        }
        $secondaryColor = $brandAttributes->getValue(ColorRgb::SECONDARY_VALUE);
        if ($secondaryColor !== null) {
            $brandButton->setSecondaryColor($secondaryColor);
        }
        return $brandButton;
    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
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
    function getPType(): string
    {
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    function getAllowedTypes(): array
    {
        return array('baseonly', 'formatting', 'substition', 'protected', 'disabled');
    }

    function getSort(): int
    {
        return 201;
    }

    public
    function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
    }


    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {

        /**
         * The empty tag pattern should be before the container pattern
         */
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

        $pattern = PluginUtility::getContainerTagPattern(self::getTag());
        $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::getTag() . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {


        switch ($state) {

            case DOKU_LEXER_SPECIAL :
            case DOKU_LEXER_ENTER :

                /**
                 * The returned array if any error
                 */
                $returnedArray[PluginUtility::STATE] = $state;

                /**
                 * Context
                 */
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $context = null;
                if ($parent !== false) {
                    $context = $parent->getTagName();
                }

                /**
                 * Default parameters, type definition and parsing
                 */
                $defaultParameters[TagAttributes::TYPE_KEY] = Brand::CURRENT_BRAND;
                /**
                 * Every brand name is allowed
                 * (
                 *   null means no type verification
                 *   during the {@link TagAttributes::createFromTagMatch()} parsing
                 * )
                 */
                $knownTypes = null;

                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultParameters, $knownTypes);


                /**
                 * Brand Object creation
                 */
                $brandName = $tagAttributes->getValue(TagAttributes::TYPE_KEY);
                try {
                    $widget = $tagAttributes->getValue(self::WIDGET_ATTRIBUTE);
                    if ($widget === null && $context === syntax_plugin_combo_menubar::TAG) {
                        $tagAttributes->addComponentAttributeValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_LINK_VALUE);
                    }
                    $brandButton = self::createBrandButtonFromAttributes($tagAttributes);

                } catch (ExceptionCombo $e) {
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "Error while reading the brand data for the brand ($brandName). Error: {$e->getMessage()}";
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    return $returnedArray;
                }
                /**
                 * Link
                 */
                try {
                    self::mixBrandButtonToTagAttributes($tagAttributes, $brandButton);
                } catch (ExceptionCombo $e) {
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "Error while getting the link data for the the brand ($brandName). Error: {$e->getMessage()}";
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    return $returnedArray;
                }

                if ($context === syntax_plugin_combo_menubar::TAG) {
                    $tagAttributes->addHtmlAttributeValue("accesskey", "h");
                    $tagAttributes->addClassName("navbar-brand");
                }

                // Width does not apply to link (otherwise the link got a max-width of 30)
                $tagAttributes->removeComponentAttributeIfPresent(Dimension::WIDTH_KEY);
                syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $tagAttributes);


                /**
                 * Logo
                 */
                if($brandButton->hasIcon()) {
                    try {
                        syntax_plugin_combo_brand::addIconInCallStack($callStack, $brandButton);
                    } catch (ExceptionCombo $e) {

                        if ($brandButton->getBrand() === Brand::CURRENT_BRAND) {

                            $documentationLink = PluginUtility::getDocumentationHyperLink("logo", "documentation");
                            LogUtility::msg("A svg logo icon is not installed on your website. Check the corresponding $documentationLink.", LogUtility::LVL_MSG_INFO);

                        } else {

                            LogUtility::msg("The brand icon returns an error. Error: {$e->getMessage()}");

                        }

                    }
                }

                /**
                 * End of link
                 */
                if ($state === DOKU_LEXER_SPECIAL) {
                    syntax_plugin_combo_link::addExitLinkTagInCallStack($callStack);
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $openTagAttributes = TagAttributes::createFromCallStackArray($openTag->getAttributes());

                /**
                 * Old syntax
                 * An icon could be inside
                 * If this is the case, we delete the added icon
                 * in the enter phase
                 * @since 2022-01-25
                 */
                $markupIconImageFound = false;
                $textFound = false;
                $callStack->moveToEnd();
                while ($actualCall = $callStack->previous()) {
                    $tagName = $actualCall->getTagName();
                    if (in_array($tagName, [syntax_plugin_combo_icon::TAG, syntax_plugin_combo_media::TAG])) {
                        // is it a added call / no content
                        // or is it an icon from the markup
                        if ($actualCall->getCapturedContent() === null) {
                            if ($markupIconImageFound) {
                                // if the markup has an icon we delete it
                                $callStack->deleteActualCallAndPrevious();
                            }
                            break;
                        }
                        if ($textFound && $openTag->getContext() === syntax_plugin_combo_menubar::TAG) {
                            // if text and icon
                            $actualCall->addClassName(self::BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS);
                        }
                        $primary = $openTagAttributes->getValue(ColorRgb::PRIMARY_VALUE);
                        if ($primary !== null && $tagName === syntax_plugin_combo_icon::TAG) {
                            try {
                                $brandButton = self::createBrandButtonFromAttributes($openTagAttributes);
                                $actualCall->addAttribute(ColorRgb::COLOR, $brandButton->getTextColor());
                            } catch (ExceptionCombo $e) {
                                LogUtility::msg("Error while trying to set the icon color on exit. Error: {$e->getMessage()}");
                            }
                        }

                        $markupIconImageFound = true;
                    }
                    if ($actualCall->getState() === DOKU_LEXER_UNMATCHED) {
                        $textFound = true;
                    }
                }


                $callStack->moveToEnd();
                syntax_plugin_combo_link::addExitLinkTagInCallStack($callStack);
                return array(
                    PluginUtility::STATE => $state
                );


        }
        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_SPECIAL:
                case DOKU_LEXER_ENTER:
                    /**
                     * Any error
                     */
                    $errorMessage = $data[PluginUtility::EXIT_MESSAGE];
                    if (!empty($errorMessage)) {
                        LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        $renderer->doc .= "<span class=\"text-warning\">{$errorMessage}</span>";
                        return false;
                    }

                    /**
                     * Add the Icon / CSS / Javascript snippet
                     * It should happen only in rendering
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    try {
                        $brandButton = self::createBrandButtonFromAttributes($tagAttributes);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The brand could not be build. Error: {$e->getMessage()}");
                        return false;
                    }
                    try {
                        $style = $brandButton->getStyle();
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The style of the {$this->getType()} button ($brandButton) could not be determined. Error: {$e->getMessage()}");
                        return false;
                    }
                    $snippetId = $brandButton->getStyleScriptIdentifier();
                    PluginUtility::getSnippetManager()->attachCssSnippetForSlot($snippetId, $style);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                default:

            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    public
    static function getTag(): string
    {
        return self::TAG;
    }

    /**
     *
     * @throws ExceptionCombo
     */
    public
    static function addIconInCallStack(CallStack $callStack, BrandButton $brandButton)
    {

        if (!$brandButton->hasIcon()) {
            return;
        }
        $iconAttributes = $brandButton->getIconAttributes();

        $callStack->appendCallAtTheEnd(
            Call::createComboCall(
                syntax_plugin_combo_icon::TAG,
                DOKU_LEXER_SPECIAL,
                $iconAttributes
            ));
    }

}

