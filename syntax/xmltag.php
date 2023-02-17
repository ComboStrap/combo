<?php


require_once(__DIR__ . '/../vendor/autoload.php');

// must be run within Dokuwiki
use ComboStrap\BlockquoteTag;
use ComboStrap\BoxTag;
use ComboStrap\ButtonTag;
use ComboStrap\CardTag;
use ComboStrap\ColorRgb;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Skin;
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
        $state = $data[PluginUtility::STATE];
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes)->setLogicalTag($logicalTag);
        switch ($format) {
            case "xhtml":
                /** @var Doku_Renderer_xhtml $renderer */
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        switch ($logicalTag) {
                            case BlockquoteTag::TAG:
                                $renderer->doc .= BlockquoteTag::renderEnterXhtml($tagAttributes, $data);
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
                            default:
                                LogUtility::errorIfDevOrTest("The empty tag (" . $logicalTag . ") was not processed.");
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
                            default:
                                LogUtility::errorIfDevOrTest("The tag (" . $logicalTag . ") was not processed.");
                        }
                        return true;
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                break;
        }
        // unsupported $mode
        return false;
    }


    /**
     * Static because it handle inline and block tag
     * @param $match
     * @param $state
     * @param $pos
     * @param Doku_Handler $handler
     * @return array
     */
    public static function handleStatic($match, $state, $pos, Doku_Handler $handler): array
    {
        /**
         * Logical Tag Building
         */
        switch ($state) {

            case DOKU_LEXER_ENTER:
                $markupTag = PluginUtility::getMarkupTag($match);
                $logicalTag = $markupTag;
                $defaultAttributes = [];
                $knownTypes = [];
                $allowAnyFirstBooleanAttributesAsType = false;
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
                }
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes, $allowAnyFirstBooleanAttributesAsType)
                    ->setLogicalTag($logicalTag);

                /**
                 * Calculate extra returned key in the table
                 */
                $returnedArray = [];
                switch ($markupTag) {
                    case BlockquoteTag::TAG:
                        $returnedArray = BlockquoteTag::handleEnter($handler);
                        break;
                    case BoxTag::TAG:
                        BoxTag::handleEnter($tagAttributes);
                        break;
                    case ButtonTag::MARKUP_SHORT:
                    case ButtonTag::MARKUP_LONG:
                        $returnedArray = ButtonTag::handleEnter($tagAttributes, $handler);
                        break;
                    case CardTag::CARD_TAG:
                        $returnedArray = CardTag::handleEnter($tagAttributes, $handler);
                        break;
                }

                /**
                 * Common default
                 */
                $defaultReturnedArray[PluginUtility::STATE] = $state;
                $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
                $defaultReturnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();

                return array_merge($defaultReturnedArray, $returnedArray);

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(null, $match, $handler);

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
                }
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
         * Icon had 'substition' and can still have other mpde inside (ie tooltip)
         * We choose substition then
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
        return self::handleStatic($match, $state, $pos, $handler);
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

