<?php

require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\BrandButton;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionRuntime;
use ComboStrap\Icon;
use ComboStrap\IconDownloader;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\ShareTag;
use ComboStrap\TagAttributes;
use ComboStrap\XmlTagProcessing;


/**
 *
 * See also:
 * Mobile sharing: https://web.dev/web-share/
 * https://twitter.com/addyosmani/status/1490055796704497665?s=21
 */
class syntax_plugin_combo_share extends DokuWiki_Syntax_Plugin
{


    const TAG = "share";
    const CANONICAL = self::TAG;


    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
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
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    function getAllowedTypes(): array
    {
        return array('substition', 'formatting', 'disabled');
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {


        /**
         * Container
         */
        $entryPattern = XmlTagProcessing::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($entryPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $returnArray = array(
            PluginUtility::STATE => $state,
        );
        switch ($state) {

            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_SPECIAL:

                $defaultAttributes = [];
                $types = [];
                $shareAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $types)
                    ->setLogicalTag(self::TAG);


                /**
                 * Return the data to add the snippet style in rendering
                 */
                $returnArray[PluginUtility::ATTRIBUTES] = $shareAttributes->toCallStackArray();
                return $returnArray;


            case DOKU_LEXER_EXIT:

                return $returnArray;

            case DOKU_LEXER_UNMATCHED:

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


        }
        return $returnArray;

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
        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_SPECIAL:
                case DOKU_LEXER_ENTER:
                    $shareAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $renderer->doc .= ShareTag::render($shareAttributes, $state);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</a>";
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


}

