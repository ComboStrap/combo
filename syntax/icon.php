<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\ConditionalLength;
use ComboStrap\Dimension;
use ComboStrap\IconTag;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\FetcherSvg;
use ComboStrap\FileSystems;
use ComboStrap\Icon;
use ComboStrap\IconDownloader;
use ComboStrap\LogUtility;
use ComboStrap\MediaMarkup;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SvgImageLink;
use ComboStrap\TagAttributes;
use ComboStrap\Tooltip;
use ComboStrap\XmlTagProcessing;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!!!!!!!!! The component name must be the name of the php file !!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 */
class syntax_plugin_combo_icon extends DokuWiki_Syntax_Plugin
{
    const CANONICAL = IconTag::TAG;


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    public function getAllowedTypes(): array
    {
        // You can't put anything in a icon
        return array('formatting');
    }

    public function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
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
     * @see Doku_Parser_Mode::getSort()
     * the mode with the lowest sort number will win out
     * the lowest in the tree must have the lowest sort number
     * No idea why it must be low but inside a teaser, it will work
     * https://www.dokuwiki.org/devel:parser#order_of_adding_modes_important
     */
    function getSort()
    {
        return 10;
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
         * The content is used to add a {@link syntax_plugin_combo_tooltip}
         */
        $entryPattern = XmlTagProcessing::getContainerTagPattern(IconTag::TAG);
        $this->Lexer->addEntryPattern($entryPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . IconTag::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
    }


    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $contextArray = IconTag::handleEnter($tagAttributes, $handler);
                $contextArray[PluginUtility::STATE] = $state;
                return $contextArray;
            case DOKU_LEXER_EXIT:
                $contextArray = IconTag::handleExit($handler);
                $contextArray[PluginUtility::STATE] = $state;
                return $contextArray;
        }

        return array();

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

        switch ($format) {

            case 'xhtml':
                {
                    /** @var Doku_Renderer_xhtml $renderer */
                    $state = $data[PluginUtility::STATE];
                    switch ($state) {


                        case DOKU_LEXER_ENTER:

                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                            $tooltip = $tagAttributes->getValueAndRemoveIfPresent(Tooltip::TOOLTIP_ATTRIBUTE);
                            if ($tooltip !== null) {
                                /**
                                 * If there is a tooltip, we need
                                 * to start with a span to wrap the svg with it
                                 */


                                $tooltipTag = TagAttributes::createFromCallStackArray([Tooltip::TOOLTIP_ATTRIBUTE => $tooltip])
                                    ->addClassName(syntax_plugin_combo_tooltip::TOOLTIP_CLASS_INLINE_BLOCK);
                                $renderer->doc .= $tooltipTag->toHtmlEnterTag("span");
                            }
                            /**
                             * Print the icon
                             */
                            $renderer->doc .= IconTag::render($tagAttributes);
                            /**
                             * Close the span if we are in a tooltip context
                             */
                            if ($tooltip !== null) {
                                $renderer->doc .= "</span>";
                            }

                            break;
                        case DOKU_LEXER_EXIT:

                            break;
                    }

                }
                break;
            case 'metadata':
                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                $tagAttribute = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                IconTag::metadata($renderer, $tagAttribute);
                break;

        }
        return true;
    }


}
