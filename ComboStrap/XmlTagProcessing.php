<?php

namespace ComboStrap;


use ComboStrap\Tag\AdTag;
use ComboStrap\Tag\BackgroundTag;
use ComboStrap\Tag\BarTag;
use ComboStrap\Tag\BoxTag;
use ComboStrap\Tag\FollowTag;
use ComboStrap\Tag\MermaidTag;
use ComboStrap\Tag\ShareTag;
use ComboStrap\Tag\SubscribeTag;
use ComboStrap\Tag\TableTag;
use ComboStrap\Tag\WebCodeTag;
use ComboStrap\TagAttribute\Hero;
use Doku_Handler;
use Doku_Renderer;
use Doku_Renderer_metadata;
use Doku_Renderer_xhtml;
use DokuWiki_Syntax_Plugin;
use Mpdf\Tag\Sub;
use renderer_plugin_combo_analytics;
use renderer_plugin_combo_xml;
use syntax_plugin_combo_code;


class XmlTagProcessing
{


    /**
     * The start tag pattern does not allow > or /
     * in the data to not compete with the empty tag pattern (ie <empty/>
     */
    public const START_TAG_PATTERN = '<[\w-]+[^/>]*>';


    public static function renderStaticExitXhtml(TagAttributes $tagAttributes, Doku_Renderer_xhtml $renderer, array $data, DokuWiki_Syntax_Plugin $plugin): bool
    {
        $logicalTag = $tagAttributes->getLogicalTag();
        switch ($logicalTag) {
            case BlockquoteTag::TAG:
                BlockquoteTag::renderExitXhtml($tagAttributes, $renderer, $data);
                return true;
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
            case PermalinkTag::TAG:
            case IconTag::TAG:
            case MermaidTag::LOGICAL_TAG:
                return true;
            case DropDownTag::TAG:
                $renderer->doc .= DropDownTag::renderExitXhtml();
                return true;
            case HeadingTag::LOGICAL_TAG:
                $context = $data[PluginUtility::CONTEXT];
                $renderer->doc .= HeadingTag::renderClosingTag($tagAttributes, $context);
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
            case SectionTag::TAG:
                $renderer->doc .= SectionTag::renderExitXhtml();
                return true;
            case TabsTag::TAG:
                $renderer->doc .= TabsTag::renderExitXhtml($tagAttributes, $data);
                return true;
            case PanelTag::PANEL_LOGICAL_MARKUP:
                $renderer->doc .= PanelTag::renderExitXhtml($data);
                return true;
            case BackgroundTag::LOGICAL_TAG:
                $renderer->doc .= BackgroundTag::renderExitSpecialHtml($data);
                return true;
            case WebCodeTag::TAG:
                $renderer->doc .= WebCodeTag::renderExit($tagAttributes, $data);
                return true;
            case ShareTag::MARKUP:
                $renderer->doc .= ShareTag::renderExit();
                return true;
            case FollowTag::MARKUP:
                $renderer->doc .= FollowTag::renderExit();
                return true;
            default:
                LogUtility::warning("The exit tag (" . $logicalTag . ") was not processed.");
                return false;
        }

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

                return self::handleStaticEnter($match, $pos, $handler, $plugin);

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

                return self::handleStaticExit($match, $pos, $handler, $plugin);


            default:
                throw new ExceptionRuntimeInternal("Should not happen");
        }
    }

    public static function renderStaticEnterXhtml(TagAttributes $tagAttributes, Doku_Renderer_xhtml $renderer, array $data, DokuWiki_Syntax_Plugin $plugin): bool
    {

        $context = $data[PluginUtility::CONTEXT];
        $pos = $data[PluginUtility::POSITION];
        $logicalTag = $tagAttributes->getLogicalTag();
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
            case SectionTag::TAG:
                $renderer->doc .= SectionTag::renderEnterXhtml($tagAttributes);
                return true;
            case TabsTag::TAG:
                $renderer->doc .= TabsTag::renderEnterXhtml($tagAttributes, $data);
                return true;
            case PanelTag::PANEL_LOGICAL_MARKUP:
                $renderer->doc .= PanelTag::renderEnterXhtml($tagAttributes, $data);
                return true;
            case PermalinkTag::TAG:
                $renderer->doc .= PermalinkTag::renderEnterSpecialXhtml($data);
                return true;
            case HrTag::TAG:
                $renderer->doc .= HrTag::render($tagAttributes);
                return true;
            case IconTag::TAG:
                $renderer->doc .= IconTag::renderEnterTag($tagAttributes);
                return true;
            case BackgroundTag::LOGICAL_TAG:
                $renderer->doc .= BackgroundTag::renderEnterTag();
                return true;
            case MermaidTag::LOGICAL_TAG:
                $renderer->doc .= MermaidTag::renderEnter($tagAttributes);
                return true;
            case WebCodeTag::TAG:
                return true;
            case ShareTag::MARKUP:
                $renderer->doc .= ShareTag::renderSpecialEnter($tagAttributes, DOKU_LEXER_ENTER);
                return true;
            case FollowTag::MARKUP:
                $renderer->doc .= FollowTag::renderSpecialEnterNode($tagAttributes, DOKU_LEXER_ENTER);
                return true;
            case TableTag::TAG:
                TableTag::renderEnterXhtml($tagAttributes, $renderer);
                return true;
            default:
                LogUtility::warning("The enter tag (" . $logicalTag . ") was not processed.");
                return false;
        }
    }

    public static function handleStaticEnter(string $match, int $pos, Doku_Handler $handler, DokuWiki_Syntax_Plugin $plugin): array
    {
        // context data
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        // Normalize Trim and delete eol to start clean
        $match = trim($match);
        $match = str_replace("\n"," ",$match);

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
            case TabsTag::TAG:
                $knownTypes = [TabsTag::ENCLOSED_PILLS_TYPE, TabsTag::ENCLOSED_TABS_TYPE, TabsTag::PILLS_TYPE, TabsTag::TABS_TYPE];
                break;
            case PanelTag::PANEL_MARKUP:
            case PanelTag::TAB_PANEL_MARKUP:
                $logicalTag = PanelTag::PANEL_LOGICAL_MARKUP;
                break;
            case PermalinkTag::TAG:
                $knownTypes = PermalinkTag::getKnownTypes();
                $defaultAttributes = [TagAttributes::TYPE_KEY => PermalinkTag::GENERATED_TYPE];
                break;
            case BackgroundTag::MARKUP_LONG:
            case BackgroundTag::MARKUP_SHORT:
                $logicalTag = BackgroundTag::LOGICAL_TAG;
                break;
            case MermaidTag::MARKUP_MERMAID:
            case WebCodeTag::TAG:
                $logicalTag = Tag\WebCodeTag::TAG;
                $defaultAttributes = WebCodeTag::getDefaultAttributes();
                break;
            case MermaidTag::MARKUP_SEQUENCE_DIAGRAM:
            case MermaidTag::MARKUP_CLASS_DIAGRAM:
            case MermaidTag::MARKUP_FLOWCHART:
            case MermaidTag::MARKUP_GANTT:
            case MermaidTag::MARKUP_ERD:
            case MermaidTag::MARKUP_JOURNEY:
            case MermaidTag::MARKUP_PIECHART:
            case MermaidTag::MARKUP_STATE_DIAGRAM:
                $logicalTag = MermaidTag::LOGICAL_TAG;
                break;
            case ShareTag::MARKUP:
                $knownTypes = ShareTag::getKnownTypes();
                break;
            case FollowTag::MARKUP:
                $knownTypes = FollowTag::getKnownTypes();
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
            case PanelTag::PANEL_LOGICAL_MARKUP:
                $returnedArray = PanelTag::handleEnter($tagAttributes, $handler, $markupTag);
                break;
            case PermalinkTag::TAG:
                $returnedArray = PermalinkTag::handleEnterSpecial($tagAttributes, DOKU_LEXER_ENTER, $handler);
                break;
            case IconTag::TAG:
                $returnedArray = IconTag::handleEnter($tagAttributes, $handler);
                break;
            case BackgroundTag::LOGICAL_TAG:
                BackgroundTag::handleEnterAndSpecial($tagAttributes);
                break;
        }

        /**
         * Common default
         */
        $defaultReturnedArray[PluginUtility::STATE] = DOKU_LEXER_ENTER;
        $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
        $defaultReturnedArray[PluginUtility::MARKUP_TAG] = $markupTag;
        $defaultReturnedArray[PluginUtility::POSITION] = $pos;
        $defaultReturnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();

        return array_merge($defaultReturnedArray, $returnedArray);

    }

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
                        return self::renderStaticEnterXhtml($tagAttributes, $renderer, $data, $plugin);
                    case DOKU_LEXER_UNMATCHED:

                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;

                    case DOKU_LEXER_EXIT:

                        return XmlTagProcessing::renderStaticExitXhtml($tagAttributes, $renderer, $data, $plugin);
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                if (!in_array($state, [DOKU_LEXER_ENTER, DOKU_LEXER_SPECIAL])) {
                    return true;
                }
                return XmlTagProcessing::renderStaticEnterSpecialMetadata($tagAttributes, $renderer, $data, $plugin);
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
                return false;
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

    public static function handleStaticExit(string $match, int $pos, Doku_Handler $handler, DokuWiki_Syntax_Plugin $plugin): array
    {
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
            case TabsTag::TAG:
                $returnedArray = TabsTag::handleExit($handler);
                break;
            case PanelTag::PANEL_MARKUP:
            case PanelTag::TAB_PANEL_MARKUP:
                $logicalTag = PanelTag::PANEL_LOGICAL_MARKUP;
                $returnedArray = PanelTag::handleExit($handler, $pos, $markupTag, $match);
                break;
            case PermalinkTag::TAG:
                PermalinkTag::handeExit($handler);
                break;
            case IconTag::TAG:
                $returnedArray = IconTag::handleExit($handler);
                break;
            case BackgroundTag::MARKUP_SHORT:
            case BackgroundTag::MARKUP_LONG:
                $logicalTag = BackgroundTag::LOGICAL_TAG;
                $returnedArray = BackgroundTag::handleExit($handler);
                break;
            case MermaidTag::MARKUP_SEQUENCE_DIAGRAM:
            case MermaidTag::MARKUP_CLASS_DIAGRAM:
            case MermaidTag::MARKUP_FLOWCHART:
            case MermaidTag::MARKUP_GANTT:
            case MermaidTag::MARKUP_ERD:
            case MermaidTag::MARKUP_JOURNEY:
            case MermaidTag::MARKUP_PIECHART:
            case MermaidTag::MARKUP_STATE_DIAGRAM:
                $logicalTag = MermaidTag::LOGICAL_TAG;
                MermaidTag::handleExit($handler);
                break;
            case MermaidTag::MARKUP_MERMAID: // bug
            case WebCodeTag::TAG:
                $logicalTag = WebCodeTag::TAG;
                $returnedArray = WebCodeTag::handleExit($handler);
                break;
        }
        /**
         * Common exit attributes
         */
        $defaultReturnedArray[PluginUtility::STATE] = DOKU_LEXER_EXIT;
        $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
        $defaultReturnedArray[PluginUtility::MARKUP_TAG] = $markupTag;
        return array_merge($defaultReturnedArray, $returnedArray);
    }

    /**
     * @param $tag
     * @return string
     *
     * Create a lookahead pattern for a container tag used to enter in a mode
     */
    public static function getContainerTagPattern($tag): string
    {
        // this pattern ensure that the tag
        // `accordion` will not intercept also the tag `accordionitem`
        // where:
        // ?: means non capturing group (to not capture the last >)
        // (\s.*?): is a capturing group that starts with a space
        $pattern = "(?:\s.*?>|>)";
        return '<' . $tag . $pattern . '(?=.*?<\/' . $tag . '>)';
    }

    public static function handleStaticEmptyTag(string $match, int $state, int $pos, Doku_Handler $handler, DokuWiki_Syntax_Plugin $plugin): array
    {
        /**
         * Logical Tag Building
         */
        $logicalTag = PluginUtility::getMarkupTag($match);
        $defaultAttributes = [];
        $knownTypes = [];
        $allowAnyFirstBooleanAttributesAsType = false;
        switch ($logicalTag) {
            case SearchTag::TAG:
                $defaultAttributes = array(
                    'ajax' => true,
                    'autocomplete' => false
                );
                break;
            case PageImageTag::MARKUP:
                $knownTypes = PageImageTag::TYPES;
                $defaultAttributes = PageImageTag::getDefaultAttributes();
                break;
            case ShareTag::MARKUP:
                $knownTypes = ShareTag::getKnownTypes();
                break;
            case FollowTag::MARKUP:
                $knownTypes = FollowTag::getKnownTypes();
                break;
            case BrandListTag::MARKUP:
                $knownTypes = BrandButton::TYPE_BUTTONS;
                $defaultAttributes = [TagAttributes::TYPE_KEY => BrandButton::TYPE_BUTTON_BRAND];
                break;
            case BrandTag::MARKUP:
                $defaultAttributes = [TagAttributes::TYPE_KEY => Brand::CURRENT_BRAND];
                $allowAnyFirstBooleanAttributesAsType = true;
                break;
            case PermalinkTag::TAG:
                $knownTypes = PermalinkTag::getKnownTypes();
                $defaultAttributes = [TagAttributes::TYPE_KEY => PermalinkTag::GENERATED_TYPE];
                break;
            case BreadcrumbTag::MARKUP_BLOCK:
                $logicalTag = BreadcrumbTag::LOGICAL_TAG;
                $knownTypes = BreadcrumbTag::TYPES;
                $defaultAttributes = BreadcrumbTag::getDefaultBlockAttributes();
                break;
        }
        $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes, $allowAnyFirstBooleanAttributesAsType)
            ->setLogicalTag($logicalTag);

        /**
         * Calculate extra returned key in the table
         */
        $returnedArray = [];
        switch ($logicalTag) {
            case IconTag::TAG:
                $returnedArray = IconTag::handleSpecial($tagAttributes, $handler);
                break;
            case PageImageTag::MARKUP:
                $returnedArray = PageImageTag::handle($tagAttributes, $handler);
                break;
            case BrandTag::MARKUP:
                $returnedArray = BrandTag::handleSpecialEnter($tagAttributes, $handler);
                break;
            case CacheTag::MARKUP:
                $returnedArray = CacheTag::handle($tagAttributes);
                break;
            case BackgroundTag::MARKUP_SHORT:
            case BackgroundTag::MARKUP_LONG:
                BackgroundTag::handleEnterAndSpecial($tagAttributes);
                $callStack = CallStack::createFromHandler($handler);
                $returnedArray = BackgroundTag::setAttributesToParentAndReturnData($callStack, $tagAttributes, $state);
                break;
            case DateTag::TAG:
                DateTag::handleEnterAndSpecial();
                break;
            case PermalinkTag::TAG:
                $returnedArray = PermalinkTag::handleEnterSpecial($tagAttributes, $state, $handler);
                break;
            case BreadcrumbTag::MARKUP_BLOCK:
                $returnedArray = BreadcrumbTag::handleEnter($tagAttributes);
                break;
            case HrTag::TAG:
                /**
                 * Block tag
                 */
                $returnedArray = [PluginUtility::DISPLAY => HrTag::getDisplay()];
                break;
        }

        /**
         * Common default
         * {@link PluginUtility::DISPLAY} should be set on handle
         */
        $defaultReturnedArray[PluginUtility::STATE] = $state;
        $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
        $defaultReturnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();

        return array_merge($defaultReturnedArray, $returnedArray);
    }

    public static function renderStaticEmptyTag(string $format, Doku_Renderer $renderer, array $data, DokuWiki_Syntax_Plugin $plugin): bool
    {

        $tag = $data[PluginUtility::TAG];
        $attributes = $data[PluginUtility::ATTRIBUTES];
        $state = DOKU_LEXER_SPECIAL;
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes)->setLogicalTag($tag);
        switch ($format) {
            case "xhtml":
                /** @var Doku_Renderer_xhtml $renderer */
                switch ($tag) {
                    case HrTag::TAG:
                        $renderer->doc .= HrTag::render($tagAttributes);
                        break;
                    case SearchTag::TAG:
                        $renderer->doc .= SearchTag::render($tagAttributes);
                        break;
                    case IconTag::TAG:
                        $renderer->doc .= IconTag::renderEmptyTag($tagAttributes);
                        break;
                    case BreadcrumbTag::LOGICAL_TAG:
                        $renderer->doc .= BreadcrumbTag::render($tagAttributes);
                        break;
                    case PageImageTag::MARKUP:
                        $renderer->doc .= PageImageTag::render($tagAttributes, $data);
                        break;
                    case ShareTag::MARKUP:
                        $renderer->doc .= ShareTag::renderSpecialEnter($tagAttributes, $state);
                        break;
                    case BrandListTag::MARKUP:
                        $renderer->doc .= BrandListTag::render($tagAttributes);
                        break;
                    case BrandTag::MARKUP:
                        $renderer->doc .= BrandTag::render($tagAttributes, $state, $data);
                        break;
                    case CacheTag::MARKUP:
                        $renderer->doc .= CacheTag::renderXhtml($data);
                        break;
                    case BackgroundTag::MARKUP_LONG:
                    case BackgroundTag::MARKUP_SHORT:
                        $renderer->doc .= BackgroundTag::renderExitSpecialHtml($data);
                        break;
                    case DateTag::TAG:
                        $renderer->doc .= DateTag::renderHtml($tagAttributes);
                        return true;
                    case PermalinkTag::TAG:
                        $renderer->doc .= PermalinkTag::renderEnterSpecialXhtml($data);
                        return true;
                    case QualityTag::MARKUP_TAG:
                        $renderer->doc .= QualityTag::renderXhtml($tagAttributes);
                        return true;
                    case FollowTag::MARKUP:
                        $renderer->doc .= FollowTag::renderSpecialEnterNode($tagAttributes, DOKU_LEXER_SPECIAL);
                        return true;
                    case MediaMarkup::TAG:
                        $renderer->doc .= MediaMarkup::renderSpecial($data, $renderer);
                        return true;
                    case SubscribeTag::LOGICAL_TAG:
                        $renderer->doc .= SubscribeTag::renderEnterXhtml($tagAttributes);
                        return true;
                    case AdTag::MARKUP:
                        $renderer->doc .= AdTag::render($tagAttributes);
                        return true;
                    default:
                        LogUtility::errorIfDevOrTest("The empty tag (" . $tag . ") was not processed.");
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                switch ($tag) {
                    case IconTag::TAG:
                        IconTag::metadata($renderer, $tagAttributes);
                        return true;
                    case CacheTag::MARKUP:
                        CacheTag::metadata($data);
                        return true;
                    case MediaMarkup::TAG:
                        MediaMarkup::metadata($data, $renderer);
                        return true;
                }
                break;
            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

        }
        // unsupported $mode
        return false;
    }

    private static function renderStaticEnterSpecialMetadata(TagAttributes $tagAttributes, Doku_Renderer_metadata $renderer, array $data, DokuWiki_Syntax_Plugin $plugin): bool
    {
        $logicalTag = $tagAttributes->getLogicalTag();
        switch ($logicalTag) {
            case HeadingTag::LOGICAL_TAG:
                HeadingTag::processHeadingEnterMetadata($data, $renderer);
                return true;
            case IconTag::TAG:
                IconTag::metadata($renderer, $tagAttributes);
                return true;
            case BackgroundTag::LOGICAL_TAG:
                BackgroundTag::renderMeta($data, $renderer);
                return true;
        }
        return false;
    }
}

