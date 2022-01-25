<?php


// must be run within Dokuwiki
use ComboStrap\BrandButton;
use ComboStrap\CallStack;
use ComboStrap\ColorUtility;
use ComboStrap\Dimension;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboNotFound;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_brand extends DokuWiki_Syntax_Plugin
{

    const TAG = "brand";
    const CANONICAL = self::TAG;

    /**
     * True if an icon was added in the brand
     * Allows the deprecation of the old syntax
     * where icon was not an attribute of brand
     */
    const ICON_FOUND_ATTRIBUTE = "icon-found";
    public const ICON_ATTRIBUTE = "icon";
    public const WIDGET_ATTRIBUTE = "widget";
    public const URL_ATTRIBUTE = "url";

    /**
     * An utility constructor to be sure that we build the brand button
     * with the same data in the handle and render function
     * @throws ExceptionCombo
     */
    private static function createBrandButtonFromAttributes(TagAttributes $brandAttributes): BrandButton
    {
        $channelName = $brandAttributes->getValue(TagAttributes::TYPE_KEY);
        $widget = $brandAttributes->getValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_BUTTON_VALUE);
        $icon = $brandAttributes->getValue(self::ICON_ATTRIBUTE, BrandButton::ICON_SOLID_VALUE);
        $width = $brandAttributes->getValueAsInteger(Dimension::WIDTH_KEY);
        return (BrandButton::createBrandButton($channelName))
            ->setIcon($icon)
            ->setWidth($width)
            ->setWidget($widget);
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
    function getPType()
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

    public function accepts($mode): bool
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
                $defaultParameters["title"] = Site::getTitle();
                $defaultParameters[TagAttributes::TYPE_KEY] = BrandButton::CURRENT_BRAND;
                try {
                    $knownTypes = BrandButton::getBrandNames();
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("Error while retrieving the brand names ({$e->getMessage()}", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    /**
                     * null means no type verification during the {@link TagAttributes::createFromTagMatch()}
                     * parsing
                     */
                    $knownTypes = null;
                }
                $linkAttributes = TagAttributes::createFromTagMatch($match, $defaultParameters, $knownTypes);


                /**
                 * Brand Object creation
                 */
                $brandName = $linkAttributes->getValue(TagAttributes::TYPE_KEY);
                try {
                    $widget = $linkAttributes->getValue(self::WIDGET_ATTRIBUTE);
                    if ($widget === null && $context === syntax_plugin_combo_menubar::TAG) {
                        $linkAttributes->addComponentAttributeValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_LINK_VALUE);
                    }
                    $brandButton = self::createBrandButtonFromAttributes($linkAttributes);


                } catch (ExceptionCombo $e) {
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "Error while reading the brand data for the brand ($brandName). Error: {$e->getMessage()}";
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    return $returnedArray;
                }
                /**
                 * Link
                 */
                try {
                    $linkAttributes = $brandButton->getLinkAttributes();
                } catch (ExceptionCombo $e) {
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "Error while getting the link data for the the brand ($brandName). Error: {$e->getMessage()}";
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    return $returnedArray;
                }

                if ($context === syntax_plugin_combo_menubar::TAG) {
                    $linkAttributes->addHtmlAttributeValue("accesskey", "h");
                    $linkAttributes->addClassName("navbar-brand");
                }
                syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $linkAttributes);
                if ($state === DOKU_LEXER_SPECIAL) {
                    syntax_plugin_combo_link::addExitLinkTagInCallStack($callStack);
                }

                /**
                 * Logo
                 */
                try {
                    $iconAttributes = $brandButton->getIconAttributes();
                } catch (ExceptionComboNotFound $e) {

                    if ($brandButton->getName() === BrandButton::CURRENT_BRAND) {
                        $documentationLink = PluginUtility::getDocumentationHyperLink("logo", "documentation");
                        LogUtility::msg("A svg logo icon is not installed on your website. Check the corresponding $documentationLink.");
                    } else {
                        LogUtility::msg("The brand icon returns an error. Error: {$e->getMessage()}");
                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $linkAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * Icon ?
                 */
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $iconFound = false;
                while ($actualCall = $callStack->next()) {
                    $tagName = $actualCall->getTagName();
                    if ($tagName === syntax_plugin_combo_icon::TAG) {
                        $iconFound = true;
                        break;
                    }
                }
                $openingCall->addAttribute(self::ICON_FOUND_ATTRIBUTE, $iconFound);

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
                    $brandName = $tagAttributes->getValue(TagAttributes::TYPE_KEY);
                    if ($brandName === BrandButton::CURRENT_BRAND) {
                        return true;
                    }

                    try {
                        $socialChannel = self::createBrandButtonFromAttributes($tagAttributes);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The social channel could not be build. Error: {$e->getMessage()}");
                        return false;
                    }
                    try {
                        $style = $socialChannel->getStyle();
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The style of the share button ($socialChannel) could not be determined. Error: {$e->getMessage()}");
                        return false;
                    }
                    $snippetId = $socialChannel->getStyleScriptIdentifier();
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

}

