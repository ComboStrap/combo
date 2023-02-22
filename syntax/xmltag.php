<?php


require_once(__DIR__ . '/../vendor/autoload.php');

// must be run within Dokuwiki
use ComboStrap\BackgroundAttribute;
use ComboStrap\BarTag;
use ComboStrap\BlockquoteTag;
use ComboStrap\BoxTag;
use ComboStrap\ButtonTag;
use ComboStrap\CallStack;
use ComboStrap\CardTag;
use ComboStrap\CarrouselTag;
use ComboStrap\ColorRgb;
use ComboStrap\HeadingTag;
use ComboStrap\JumbotronTag;
use ComboStrap\MasonryTag;
use ComboStrap\NoteTag;
use ComboStrap\PageExplorerTag;
use ComboStrap\PrismTags;
use ComboStrap\ContainerTag;
use ComboStrap\DateTag;
use ComboStrap\DropDownTag;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\GridTag;
use ComboStrap\Hero;
use ComboStrap\LogUtility;
use ComboStrap\PipelineTag;
use ComboStrap\PluginUtility;
use ComboStrap\Skin;
use ComboStrap\Spacing;
use ComboStrap\TagAttributes;


/**
 * The xml tag (non-empty) pattern
 */
class syntax_plugin_combo_xmltag extends DokuWiki_Syntax_Plugin
{
    /**
     * Should be the same than the last name of the class
     */
    const TAG = "xmltag";

    public static function renderStatic(string $format, Doku_Renderer $renderer, array $data, DokuWiki_Syntax_Plugin $plugin): bool
    {
        $logicalTag = $data[PluginUtility::TAG];
        $attributes = $data[PluginUtility::ATTRIBUTES];
        $context = $data[PluginUtility::CONTEXT];
        $state = $data[PluginUtility::STATE];
        $pos = $data[PluginUtility::POSITION];
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes)->setLogicalTag($logicalTag);
        switch ($format) {
            case "xhtml":
                /** @var Doku_Renderer_xhtml $renderer */
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        switch ($logicalTag) {
                            case BlockquoteTag::TAG:
                                $renderer->doc .= BlockquoteTag::renderEnterXhtml($tagAttributes, $data, $renderer);
                                return true;
                            case BoxTag::TAG:
                                $renderer->doc .= BoxTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case ButtonTag::LOGICAL_TAG:
                                $renderer->doc .= ButtonTag::renderEnterXhtml($tagAttributes, $plugin, $data);
                                return true;
                            case CardTag::LOGICAL_TAG:
                                $renderer->doc .= CardTag::renderEnterXhtml($tagAttributes, $renderer, $data);
                                return true;
                            case BarTag::LOGICAL_TAG:
                                $renderer->doc .= BarTag::renderEnterXhtml($tagAttributes, $data);
                                return true;
                            case CarrouselTag::TAG:
                                $renderer->doc .= CarrouselTag::renderEnterXhtml($tagAttributes, $data);
                                return true;
                            case PrismTags::CONSOLE_TAG:
                            case PrismTags::FILE_TAG:
                                PrismTags::processEnterXhtml($tagAttributes, $plugin, $renderer);
                                return true;
                            case ContainerTag::TAG:
                                $renderer->doc .= ContainerTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case GridTag::LOGICAL_TAG:
                                $renderer->doc .= GridTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case PipelineTag::TAG:
                                $renderer->doc .= PipelineTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case DateTag::TAG:
                                $renderer->doc .= DateTag::renderHtml($tagAttributes);
                                return true;
                            case DropDownTag::TAG:
                                $renderer->doc .= DropDownTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case HeadingTag::LOGICAL_TAG:
                                HeadingTag::processRenderEnterXhtml($context, $tagAttributes, $renderer, $pos);
                                return true;
                            case NoteTag::TAG_INOTE:
                                $renderer->doc .= NoteTag::renderEnterInlineNote($tagAttributes);
                                return true;
                            case JumbotronTag::TAG:
                                $renderer->doc .= JumbotronTag::renderEnterXhtml($tagAttributes);
                                return true;
                            case MasonryTag::LOGICAL_TAG:
                                $renderer->doc .= MasonryTag::renderEnterTag();
                                return true;
                            case PageExplorerTag::LOGICAL_TAG:
                                $renderer->doc .= PageExplorerTag::renderEnterTag($tagAttributes, $data);
                                return true;
                            default:
                                LogUtility::errorIfDevOrTest("The tag (" . $logicalTag . ") was not processed.");
                                return false;
                        }
                    case DOKU_LEXER_UNMATCHED:

                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;

                    case DOKU_LEXER_EXIT:

                        switch ($logicalTag) {
                            case BlockquoteTag::TAG:
                                BlockquoteTag::renderExitXhtml($tagAttributes, $renderer, $data);
                                break;
                            case BoxTag::TAG:
                                $renderer->doc .= BoxTag::renderExitXhtml($tagAttributes);
                                return true;
                            case ButtonTag::LOGICAL_TAG:
                                $renderer->doc .= ButtonTag::renderExitXhtml($data);
                                return true;
                            case CardTag::LOGICAL_TAG:
                                CardTag::handleExitXhtml($data, $renderer);
                                return true;
                            case BarTag::LOGICAL_TAG:
                                $renderer->doc .= BarTag::renderExitXhtml($data);
                                return true;
                            case CarrouselTag::TAG:
                                $renderer->doc .= CarrouselTag::renderExitXhtml();
                                return true;
                            case PrismTags::CONSOLE_TAG:
                            case PrismTags::FILE_TAG:
                                PrismTags::processExitXhtml($tagAttributes, $renderer);
                                return true;
                            case ContainerTag::TAG:
                                $renderer->doc .= ContainerTag::renderExitXhtml();
                                return true;
                            case GridTag::LOGICAL_TAG:
                                $renderer->doc .= GridTag::renderExitXhtml($tagAttributes);
                                return true;
                            case PipelineTag::TAG:
                            case DateTag::TAG:
                            case PageExplorerTag::LOGICAL_TAG:
                                return true;
                            case DropDownTag::TAG:
                                $renderer->doc .= DropDownTag::renderExitXhtml();
                                return true;
                            case HeadingTag::LOGICAL_TAG:
                                $renderer->doc .= HeadingTag::renderClosingTag($tagAttributes);
                                return true;
                            case NoteTag::TAG_INOTE:
                                $renderer->doc .= NoteTag::renderClosingInlineNote();
                                return true;
                            case JumbotronTag::TAG:
                                $renderer->doc .= JumbotronTag::renderExitHtml();
                                return true;
                            case MasonryTag::LOGICAL_TAG:
                                $renderer->doc .= MasonryTag::renderExitHtml();
                                return true;

                            default:
                                LogUtility::errorIfDevOrTest("The tag (" . $logicalTag . ") was not processed.");
                        }
                        return true;
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                switch ($logicalTag) {
                    case HeadingTag::LOGICAL_TAG:
                        HeadingTag::processHeadingMetadata($data, $renderer);
                        return true;
                }
                break;
            case 'xml':
                /** @var renderer_plugin_combo_xml $renderer */
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        switch ($logicalTag) {
                            default:
                            case BarTag::LOGICAL_TAG:
                                $renderer->doc .= "<$logicalTag>";
                                return true;
                        }
                    case DOKU_LEXER_UNMATCHED :
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        break;
                    case DOKU_LEXER_EXIT :
                        switch ($logicalTag) {
                            default:
                            case BarTag::LOGICAL_TAG:
                                $renderer->doc .= "</$logicalTag>";
                                return true;
                        }
                }
            case renderer_plugin_combo_analytics::RENDERER_FORMAT:
                /**
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                switch ($logicalTag) {
                    default:
                    case HeadingTag::LOGICAL_TAG:
                        HeadingTag::processMetadataAnalytics($data, $renderer);
                        return true;
                }

        }

        // unsupported $mode
        return false;
    }


    /**
     * Static because it handle inline and block tag
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @param DokuWiki_Syntax_Plugin $plugin
     * @return array
     */
    public static function handleStatic(string $match, int $state, int $pos, Doku_Handler $handler, DokuWiki_Syntax_Plugin $plugin): array
    {
        /**
         * Logical Tag Building
         */
        switch ($state) {

            case DOKU_LEXER_ENTER:

                // context data
                $executionContext = ExecutionContext::getActualOrCreateFromEnv();

                // Markup
                $markupTag = PluginUtility::getMarkupTag($match);
                $logicalTag = $markupTag;
                $defaultAttributes = [];
                $knownTypes = [];
                $allowAnyFirstBooleanAttributesAsType = false;

                // code block allow a second attribute value as file name
                $hasTwoBooleanAttribute = false;
                $secondBooleanAttribute = null;

                switch ($markupTag) {
                    case BlockquoteTag::TAG:
                        // Suppress the component name
                        $defaultAttributes = array("type" => BlockquoteTag::CARD_TYPE);
                        $knownTypes = [BlockquoteTag::TYPO_TYPE, BlockquoteTag::CARD_TYPE];;
                        break;
                    case BoxTag::TAG:
                        $defaultAttributes[BoxTag::HTML_TAG_ATTRIBUTE] = BoxTag::DEFAULT_HTML_TAG;
                        $defaultAttributes[BoxTag::LOGICAL_TAG_ATTRIBUTE] = BoxTag::LOGICAL_TAG_DEFAUT;
                        break;
                    case ButtonTag::MARKUP_SHORT:
                    case ButtonTag::MARKUP_LONG:
                        $logicalTag = ButtonTag::LOGICAL_TAG;
                        $knownTypes = ButtonTag::TYPES;
                        $defaultAttributes = array(
                            Skin::SKIN_ATTRIBUTE => Skin::FILLED_VALUE,
                            TagAttributes::TYPE_KEY => ColorRgb::PRIMARY_VALUE
                        );
                        break;
                    case CardTag::CARD_TAG:
                    case CardTag::TEASER_TAG:
                        $logicalTag = CardTag::LOGICAL_TAG;
                        break;
                    case BarTag::BAR_TAG:
                    case BarTag::SLIDE_TAG:
                        $logicalTag = BarTag::LOGICAL_TAG;
                        $defaultAttributes[Hero::ATTRIBUTE] = "sm";
                        break;
                    case PrismTags::CONSOLE_TAG:
                    case PrismTags::FILE_TAG:
                        $hasTwoBooleanAttribute = true;
                        $secondBooleanAttribute = syntax_plugin_combo_code::FILE_PATH_KEY;
                        $allowAnyFirstBooleanAttributesAsType = true;
                        break;
                    case ContainerTag::TAG:
                        $knownTypes = ContainerTag::CONTAINER_VALUES;
                        $defaultAttributes[TagAttributes::TYPE_KEY] = $executionContext->getConfig()->getValue(ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF, ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                        break;
                    case GridTag::ROW_TAG:
                    case GridTag::GRID_TAG:
                        $logicalTag = GridTag::LOGICAL_TAG;
                        $knownTypes = GridTag::KNOWN_TYPES;
                        break;
                    case HeadingTag::HEADING_TAG:
                    case HeadingTag::TITLE_TAG:
                        $logicalTag = HeadingTag::LOGICAL_TAG;
                        $knownTypes = HeadingTag::getAllTypes();
                        break;
                    case NoteTag::TAG_INOTE:
                        $defaultConfValue = $plugin->getConf(NoteTag::INOTE_CONF_DEFAULT_ATTRIBUTES_KEY);
                        $defaultAttributes = PluginUtility::parseAttributes($defaultConfValue);
                        if (!isset($defaultAttributes[TagAttributes::TYPE_KEY])) {
                            $defaultAttributes[TagAttributes::TYPE_KEY] = "info";
                        }
                        $knownTypes = NoteTag::KNOWN_TYPES;
                        break;
                    case JumbotronTag::TAG:
                        $defaultAttributes = JumbotronTag::getDefault();
                        break;
                    case MasonryTag::CARD_COLUMNS_TAG:
                    case MasonryTag::TEASER_COLUMNS_TAG:
                    case MasonryTag::MASONRY_TAG:
                        $logicalTag = MasonryTag::LOGICAL_TAG;
                        break;
                    case PageExplorerTag::NTOC_MARKUP:
                    case PageExplorerTag::PAGE_EXPLORER_MARKUP:
                        $logicalTag = PageExplorerTag::LOGICAL_TAG;
                        $defaultAttributes = [TagAttributes::TYPE_KEY => PageExplorerTag::LIST_TYPE];
                        $knownTypes = [PageExplorerTag::TYPE_TREE, PageExplorerTag::LIST_TYPE];
                        break;
                    case PageExplorerTag::INDEX_HOME_TAG:
                    case PageExplorerTag::INDEX_TAG:
                        $logicalTag = PageExplorerTag::LOGICAL_INDEX_TAG;
                        break;
                    case PageExplorerTag::NAMESPACE_ITEM_TAG:
                    case PageExplorerTag::NAMESPACE_LONG_TAG:
                    case PageExplorerTag::NAMESPACE_SHORT_TAG:
                        $logicalTag = PageExplorerTag::NAMESPACE_LOGICAL_TAG;
                        break;
                    case PageExplorerTag::PAGE_ITEM_TAG:
                    case PageExplorerTag::PAGE_TAG:
                        $logicalTag = PageExplorerTag::PAGE_LOGICAL_TAG;
                        break;
                }

                /**
                 * Build tag Attributes
                 */
                if (!$hasTwoBooleanAttribute) {
                    $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes, $allowAnyFirstBooleanAttributesAsType);
                } else {
                    $tagAttributes = TagAttributes::createEmpty();
                    $attributesArray = PluginUtility::getQualifiedTagAttributes($match, true, $secondBooleanAttribute, $knownTypes, $allowAnyFirstBooleanAttributesAsType);
                    foreach ($attributesArray as $key => $value) {
                        $tagAttributes->addComponentAttributeValue($key, $value);
                    }
                }
                $tagAttributes->setLogicalTag($logicalTag);

                /**
                 * Calculate extra returned key in the table
                 */
                $returnedArray = [];
                switch ($logicalTag) {
                    case BlockquoteTag::TAG:
                        $returnedArray = BlockquoteTag::handleEnter($handler);
                        break;
                    case BoxTag::TAG:
                        BoxTag::handleEnter($tagAttributes);
                        break;
                    case ButtonTag::LOGICAL_TAG:
                        $returnedArray = ButtonTag::handleEnter($tagAttributes, $handler);
                        break;
                    case CardTag::CARD_TAG:
                        $returnedArray = CardTag::handleEnter($tagAttributes, $handler);
                        break;
                    case BarTag::LOGICAL_TAG:
                        $returnedArray = BarTag::handleEnter($tagAttributes);
                        break;
                    case CarrouselTag::TAG:
                        $returnedArray = CarrouselTag::handleEnter($handler);
                        break;
                    case GridTag::LOGICAL_TAG:
                        GridTag::processEnter($tagAttributes, $handler, $match);
                        break;
                    case DateTag::TAG:
                        DateTag::handleEnterAndSpecial();
                        break;
                    case HeadingTag::LOGICAL_TAG:
                        $returnedArray = HeadingTag::handleEnter($handler, $tagAttributes, $markupTag);
                        break;
                }

                /**
                 * Common default
                 */
                $defaultReturnedArray[PluginUtility::STATE] = $state;
                $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
                $defaultReturnedArray[PluginUtility::POSITION] = $pos;
                $defaultReturnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();

                return array_merge($defaultReturnedArray, $returnedArray);

            case DOKU_LEXER_UNMATCHED :

                $data = PluginUtility::handleAndReturnUnmatchedData(null, $match, $handler);
                /**
                 * Attribute of parent are send for context
                 * (example `display = none`)
                 */
                $callStack = CallStack::createFromHandler($handler);
                $parentTag = $callStack->moveToParent();
                if ($parentTag !== false) {
                    $tagAttributes = $parentTag->getAttributes();
                    $data[PluginUtility::ATTRIBUTES] = $tagAttributes;
                }
                return $data;

            case DOKU_LEXER_EXIT :

                $markupTag = PluginUtility::getMarkupTag($match);
                $logicalTag = $markupTag;
                $returnedArray = [];
                switch ($markupTag) {
                    case BlockquoteTag::TAG:
                        $returnedArray = BlockquoteTag::handleExit($handler);
                        break;
                    case BoxTag::TAG:
                        $returnedArray = BoxTag::handleExit($handler);
                        break;
                    case ButtonTag::MARKUP_SHORT:
                    case ButtonTag::MARKUP_LONG:
                        $logicalTag = ButtonTag::LOGICAL_TAG;
                        $returnedArray = ButtonTag::handleExit($handler);
                        break;
                    case CardTag::CARD_TAG:
                    case CardTag::TEASER_TAG:
                        $logicalTag = CardTag::LOGICAL_TAG;
                        $returnedArray = CardTag::handleExit($handler, $pos, $match);
                        break;
                    case BarTag::BAR_TAG:
                    case BarTag::SLIDE_TAG:
                        $logicalTag = BarTag::LOGICAL_TAG;
                        $returnedArray = BarTag::handleExit($handler, $pos, $match);
                        break;
                    case CarrouselTag::TAG:
                        $returnedArray = CarrouselTag::handleExit($handler);
                        break;
                    case PrismTags::CONSOLE_TAG:
                    case PrismTags::FILE_TAG:
                        $returnedArray = PrismTags::handleExit($handler);
                        break;
                    case PipelineTag::TAG:
                        PipelineTag::processExit($handler);
                        break;
                    case GridTag::GRID_TAG:
                    case GridTag::ROW_TAG:
                        $logicalTag = GridTag::LOGICAL_TAG;
                        $returnedArray = GridTag::handleExit($handler);
                        break;
                    case DateTag::TAG:
                        DateTag::handleExit($handler);
                        break;
                    case HeadingTag::TITLE_TAG:
                    case HeadingTag::HEADING_TAG:
                        $logicalTag = HeadingTag::LOGICAL_TAG;
                        $returnedArray = HeadingTag::handleExit($handler);
                        break;
                    case MasonryTag::CARD_COLUMNS_TAG:
                    case MasonryTag::TEASER_COLUMNS_TAG:
                    case MasonryTag::MASONRY_TAG:
                        $logicalTag = MasonryTag::LOGICAL_TAG;
                        MasonryTag::handleExit($handler);
                        break;
                    case PageExplorerTag::NTOC_MARKUP:
                    case PageExplorerTag::PAGE_EXPLORER_MARKUP:
                        $logicalTag = PageExplorerTag::LOGICAL_TAG;
                        PageExplorerTag::handleExit($handler);
                        break;
                    case PageExplorerTag::INDEX_HOME_TAG:
                    case PageExplorerTag::INDEX_TAG:
                        $logicalTag = PageExplorerTag::LOGICAL_INDEX_TAG;
                        break;
                    case PageExplorerTag::NAMESPACE_ITEM_TAG:
                    case PageExplorerTag::NAMESPACE_LONG_TAG:
                    case PageExplorerTag::NAMESPACE_SHORT_TAG:
                        $logicalTag = PageExplorerTag::NAMESPACE_LOGICAL_TAG;
                        break;
                    case PageExplorerTag::PAGE_ITEM_TAG:
                    case PageExplorerTag::PAGE_TAG:
                        $logicalTag = PageExplorerTag::PAGE_LOGICAL_TAG;
                        break;
                    case PageExplorerTag::PARENT_TAG:
                        // nothing as the content is captured and deleted by page-explorer
                        break;
                }
                /**
                 * Common exit attributes
                 */
                $defaultReturnedArray[PluginUtility::STATE] = $state;
                $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
                return array_merge($defaultReturnedArray, $returnedArray);

            default:
                throw new ExceptionRuntimeInternal("Should not happen");
        }
    }


    /**
     * The Syntax Type determines which syntax may be nested
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     * See https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType(): string
    {
        /**
         * Choice between container, formatting and substition
         *
         * Icon had 'substition' and can still have other mode inside (ie tooltip)
         * We choose substition then
         *
         * For heading, title, it was `baseonly` because
         * Heading disappear when a table is just before because the {@link HeadingTag::SYNTAX_TYPE}  was `formatting`
         * The table was then accepting it and was deleting it at completion because there was no end of cell character (ie `|`)
         *
         */
        return 'substition';
    }

    /**
     * @param string $mode
     * @return bool
     * Allowed type
     */
    public function accepts($mode): bool
    {
        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_headingwiki}
         */
        if ($mode == "header") {
            return false;
        }

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline (dokuwiki will not close an ongoing p)
     *  * 'block' - Block (dokuwiki does not not create p inside and close an open p)
     *  * 'stack' - Block (dokuwiki create p inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        /**
         * Ptype is the driver of the {@link \dokuwiki\Parsing\Handler\Block::process()}
         * that creates the P tag.
         *
         * Works with block and stack for now
         * Not with `normal` as if dokuwiki has created a p
         * and that is encounters a block, it will close the p inside the stack unfortunately
         * (You can try with {@link BlockquoteTag}
         *
         * For box, not stack, otherwise it creates p
         * and as box is used mostly for layout purpose, it breaks the
         * {@link \ComboStrap\Align} flex css attribute
         *
         * For Cardbody, block value was !important! as
         * it will not create an extra paragraph after it encounters a block
         *
         * For {@link \ComboStrap\GridTag},
         * not stack, otherwise you get extra p's
         * and it will fucked up the flex layout
         */
        return 'block';
    }

    /**
     * @return array the kind of plugin that are allowed inside (ie an array of
     * <a href="https://www.dokuwiki.org/devel:syntax_plugins#syntax_types">mode type</a>
     * ie
     * * array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * * array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    public function getAllowedTypes(): array
    {
        /**
         * Tweak: `paragraphs` is not in the allowed type
         */
        return array('container', 'formatting', 'substition', 'protected', 'disabled');
    }


    function getSort(): int
    {
        return 999;
    }


    function connectTo($mode)
    {

        // this pattern ensure that the tag
        // `accordion` will not intercept also the tag `accordionitem`
        // where:
        // ?: means non capturing group (to not capture the last >)
        // (\s.*?): is a capturing group that starts with a space
        $pattern = "(?:\s.*?>|>)";
        $pattern = '<[\w-]+.*?>';
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</[\w-]+>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        return self::handleStatic($match, $state, $pos, $handler, $this);
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

        return self::renderStatic($format, $renderer, $data, $this);

    }


}

