<?php


use ComboStrap\DateTag;
use ComboStrap\PipelineTag;
use ComboStrap\PluginUtility;
use ComboStrap\PrismTags;
use ComboStrap\XmlTagProcessing;


/**
 *
 *
 */
class syntax_plugin_combo_xmlprotectedtag extends DokuWiki_Syntax_Plugin
{


    private function getTags(): array
    {
        $tags = self::TAGS;
        if ($this->getConf(PrismTags::CONF_FILE_ENABLE)) {
            $tags[] = PrismTags::FILE_TAG;
        }
        return $tags;
    }

    function getType(): string
    {
        /**
         * You can't write in a code block
         */
        return 'protected';
    }

    /**
     * How DokuWiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        /**
         * Should be less than the code syntax plugin
         * which is 200
         **/
        return 199;
    }

    const TAGS = [
        PrismTags::CONSOLE_TAG,
        PipelineTag::TAG, // protected inline deprecated
        DateTag::TAG // protected inline deprecated
    ];

    function connectTo($mode)
    {


        foreach ($this->getTags() as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }


    function postConnect()
    {
        foreach ($this->getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
        }
    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        return XmlTagProcessing::handleStatic($match, $state, $pos, $handler, $this);

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        return XmlTagProcessing::renderStatic($format, $renderer, $data, $this);
    }


}

