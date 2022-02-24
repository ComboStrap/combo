<?php


// must be run within Dokuwiki
use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\CacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\ExceptionCombo;
use ComboStrap\Icon;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\Template;
use ComboStrap\TemplateUtility;

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_brand extends DokuWiki_Syntax_Plugin
{

    const TAG = "brand";
    const CANONICAL = self::TAG;


    public const ICON_ATTRIBUTE = "icon";

    public const URL_ATTRIBUTE = "url";

    /**
     * Class needed
     * https://getbootstrap.com/docs/5.1/components/navbar/#image-and-text
     */
    const BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS = "d-inline-block align-text-top";

    const WIDGET_ATTRIBUTE = "widget";

    const BRAND_IMAGE_FOUND_INDICATOR = "brand_image_found";
    const BRAND_TEXT_FOUND_INDICATOR = "brand_text_found";


    public static function addOpenLinkTagInCallStack(CallStack $callStack, TagAttributes $tagAttributes)
    {
        $linkArrayAttributes = $tagAttributes->toCallStackArray();
        $linkArrayAttributes[TagAttributes::TYPE_KEY] = $tagAttributes->getLogicalTag();
        $linkAttributes = TagAttributes::createFromCallStackArray($linkArrayAttributes);
        syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $linkAttributes);
    }

    /**
     * @throws ExceptionCombo
     */
    public static function mixBrandButtonToTagAttributes(TagAttributes $tagAttributes, BrandButton $brandButton)
    {
        $brandLinkAttributes = $brandButton->getLinkAttributes();
        $urlAttribute = syntax_plugin_combo_brand::URL_ATTRIBUTE;
        $url = $tagAttributes->getValueAndRemoveIfPresent($urlAttribute);
        if ($url !== null) {
            $urlTemplate = Template::create($url);
            $variableDetected = $urlTemplate->getVariablesDetected();
            if (sizeof($variableDetected) === 1 && $variableDetected[0] === "path") {
                CacheManager::getOrCreate()->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                $page = Page::createPageFromRequestedPage();
                $relativePath = str_replace(":", "/", $page->getDokuwikiId());
                $url = $urlTemplate
                    ->set("path", $relativePath)
                    ->render();
            }
            $tagAttributes->addOutputAttributeValue("href", $url);
        }
        $tagAttributes->mergeWithCallStackArray($brandLinkAttributes->toCallStackArray());
    }


    /**
     * An utility constructor to be sure that we build the brand button
     * with the same data in the handle and render function
     * @throws ExceptionCombo
     */
    public static function createButtonFromAttributes(TagAttributes $brandAttributes, $type = BrandButton::TYPE_BUTTON_BRAND): BrandButton
    {
        $brandName = $brandAttributes->getValue(TagAttributes::TYPE_KEY, Brand::CURRENT_BRAND);
        $widget = $brandAttributes->getValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_BUTTON_VALUE);
        $icon = $brandAttributes->getValue(self::ICON_ATTRIBUTE, BrandButton::ICON_SOLID_VALUE);

        $brandButton = (new BrandButton($brandName, $type))
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
        $handle = $brandAttributes->getValue(syntax_plugin_combo_follow::HANDLE_ATTRIBUTE);
        if ($handle !== null) {
            $brandButton->setHandle($handle);
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

        $pattern = PluginUtility::getContainerTagPattern(self::getTag());
        $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

        /**
         * The empty tag pattern should be after the container pattern
         */
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

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
                if ($context === syntax_plugin_combo_menubar::TAG) {
                    $defaultWidget = BrandButton::WIDGET_LINK_VALUE;
                } else {
                    $defaultWidget = BrandButton::WIDGET_BUTTON_VALUE;
                }
                $defaultParameters[TagAttributes::TYPE_KEY] = Brand::CURRENT_BRAND;
                $defaultParameters[self::WIDGET_ATTRIBUTE] = $defaultWidget;
                $knownTypes = null;
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultParameters, $knownTypes)
                    ->setLogicalTag(self::TAG);


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
                $openTagContext = $openTag->getContext();
                /**
                 * Old syntax
                 * An icon/image could be already inside
                 * We go from end to start to
                 * see if there is also a text, if this is the case,
                 * there is a class added on the media
                 */
                $markupIconImageFound = false;
                $textFound = false;
                $callStack->moveToEnd();
                while ($actualCall = $callStack->previous()) {
                    $tagName = $actualCall->getTagName();
                    if (in_array($tagName, [syntax_plugin_combo_icon::TAG, syntax_plugin_combo_media::TAG])) {


                        if ($textFound && $openTagContext === syntax_plugin_combo_menubar::TAG) {
                            // if text and icon
                            // We add it here because, if they are present, we don't add them later
                            // for all on raster image
                            $actualCall->addClassName(self::BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS);
                        }

                        // is it a added call / no content
                        // or is it an icon from the markup
                        if ($actualCall->getCapturedContent() === null) {

                            // It's an added call
                            // No user icon, image can be found anymore
                            // exiting
                            break;
                        }

                        $primary = $openTagAttributes->getValue(ColorRgb::PRIMARY_VALUE);
                        if ($primary !== null && $tagName === syntax_plugin_combo_icon::TAG) {
                            try {
                                $brandButton = self::createButtonFromAttributes($openTagAttributes);
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
                $openTag->setPluginData(self::BRAND_IMAGE_FOUND_INDICATOR, $markupIconImageFound);
                $openTag->setPluginData(self::BRAND_TEXT_FOUND_INDICATOR, $textFound);

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

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    /**
                     * Brand Object creation
                     */
                    $brandName = $tagAttributes->getType();
                    try {
                        $brandButton = self::createButtonFromAttributes($tagAttributes);
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("Error while reading the brand data for the brand ($brandName). Error: {$e->getMessage()}");
                        return false;
                    }
                    /**
                     * Link
                     */
                    try {
                        self::mixBrandButtonToTagAttributes($tagAttributes, $brandButton);
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("Error while getting the link data for the the brand ($brandName). Error: {$e->getMessage()}");
                        return false;
                    }
                    $context = $data[PluginUtility::CONTEXT];
                    if ($context === syntax_plugin_combo_menubar::TAG) {
                        $tagAttributes->addOutputAttributeValue("accesskey", "h");
                        $tagAttributes->addClassName("navbar-brand");
                    }
                    // Width does not apply to link (otherwise the link got a max-width of 30)
                    $tagAttributes->removeComponentAttributeIfPresent(Dimension::WIDTH_KEY);
                    // Widget also
                    $tagAttributes->removeComponentAttributeIfPresent(self::WIDGET_ATTRIBUTE);
                    $renderer->doc .= $tagAttributes
                        ->setType(self::CANONICAL)
                        ->setLogicalTag(syntax_plugin_combo_link::TAG)
                        ->toHtmlEnterTag("a");


                    /**
                     * Logo
                     */
                    $brandImageFound = $data[self::BRAND_IMAGE_FOUND_INDICATOR];
                    if (!$brandImageFound && $brandButton->hasIcon()) {
                        try {
                            $iconAttributes = $brandButton->getIconAttributes();
                            $textFound = $data[self::BRAND_TEXT_FOUND_INDICATOR];
                            $name = $iconAttributes[\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE];
                            $iconAttributes = TagAttributes::createFromCallStackArray($iconAttributes);
                            if ($textFound && $context === syntax_plugin_combo_menubar::TAG) {
                                $iconAttributes->addClassName(self::BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS);
                            }
                            $renderer->doc .= Icon::create($name, $iconAttributes)
                                ->render();
                        } catch (ExceptionCombo $e) {

                            if ($brandButton->getBrand()->getName() === Brand::CURRENT_BRAND) {

                                $documentationLink = PluginUtility::getDocumentationHyperLink("logo", "documentation");
                                LogUtility::msg("A svg logo icon is not installed on your website. Check the corresponding $documentationLink.", LogUtility::LVL_MSG_INFO);

                            } else {

                                $renderer->doc .= "The brand icon returns an error. Error: {$e->getMessage()}";
                                // we don't return because the link is not closed

                            }

                        }
                    }

                    /**
                     * End of link
                     */
                    if ($state === DOKU_LEXER_SPECIAL) {
                        $renderer->doc .= "</a>";
                    }

                    /**
                     * Add the Icon / CSS / Javascript snippet
                     *
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    try {
                        $brandButton = self::createButtonFromAttributes($tagAttributes);
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
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($snippetId, $style);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</a>";
                    break;

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

