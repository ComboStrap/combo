<?php


// must be run within Dokuwiki
use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\BrandTag;
use ComboStrap\IconTag;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\ExceptionCompile;
use ComboStrap\Icon;
use ComboStrap\IconDownloader;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;
use ComboStrap\Template;

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_brand extends DokuWiki_Syntax_Plugin
{

    const TAG = "brand";
    const CANONICAL = self::TAG;


    public static function addOpenLinkTagInCallStack(CallStack $callStack, TagAttributes $tagAttributes)
    {
        $linkArrayAttributes = $tagAttributes->toCallStackArray();
        $linkArrayAttributes[TagAttributes::TYPE_KEY] = $tagAttributes->getLogicalTag();
        $linkAttributes = TagAttributes::createFromCallStackArray($linkArrayAttributes);
        syntax_plugin_combo_link::addOpenLinkTagInCallStack($callStack, $linkAttributes);
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
                 * Tag building
                 */
                $knownTypes = Brand::getBrandNamesFromDictionary();
                $defaultAttributes = [TagAttributes::TYPE_KEY => Brand::CURRENT_BRAND];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes)
                    ->setLogicalTag(BrandTag::MARKUP);

                /**
                 * Extra properties
                 */
                $returnedArray = BrandTag::handle($tagAttributes, $handler);

                /**
                 * Common properties
                 */
                $returnedArray[PluginUtility::STATE] = $state;
                $returnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();
                return $returnedArray;

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
                    if (in_array($tagName, [IconTag::TAG, syntax_plugin_combo_media::TAG])) {


                        if ($textFound && $openTagContext === syntax_plugin_combo_menubar::TAG) {
                            // if text and icon
                            // We add it here because, if they are present, we don't add them later
                            // for all on raster image
                            $actualCall->addClassName(BrandTag::BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS);
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
                        if ($primary !== null && $tagName === IconTag::TAG) {
                            try {
                                $brandButton = BrandTag::createButtonFromAttributes($openTagAttributes);
                                $actualCall->addAttribute(ColorRgb::COLOR, $brandButton->getTextColor());
                            } catch (ExceptionCompile $e) {
                                LogUtility::msg("Error while trying to set the icon color on exit. Error: {$e->getMessage()}");
                            }
                        }

                        $markupIconImageFound = true;
                    }
                    if ($actualCall->getState() === DOKU_LEXER_UNMATCHED) {
                        $textFound = true;
                    }
                }
                $openTag->setPluginData(BrandTag::BRAND_IMAGE_FOUND_INDICATOR, $markupIconImageFound);
                $openTag->setPluginData(BrandTag::BRAND_TEXT_FOUND_INDICATOR, $textFound);

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
                    $renderer->doc .= BrandTag::render($tagAttributes, $state, $data);;
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
     * @throws ExceptionCompile
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
                IconTag::TAG,
                DOKU_LEXER_SPECIAL,
                $iconAttributes
            ));
    }

}

