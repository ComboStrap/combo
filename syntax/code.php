<?php


use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\Html;
use ComboStrap\PluginUtility;
use ComboStrap\Prism;
use ComboStrap\TagAttributes;
use ComboStrap\XmlTagProcessing;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Class syntax_plugin_combo_code
 *
 * Implementation of https://developer.mozilla.org/en-US/docs/Web/HTML/Element/code
 * with Prism
 *
 * Support <a href="https://github.github.com/gfm/#fenced-code-blocks">Github code block</a>
 *
 * The original code markdown code block is the {@link syntax_plugin_combo_preformatted}
 *
 * Mdx Syntax: https://mdxjs.com/guides/syntax-highlighting/
 * Rehype Plugin used by Floating-ui: https://rehype-pretty-code.netlify.app/
 */
class syntax_plugin_combo_code extends DokuWiki_Syntax_Plugin
{

    /**
     * Enable or disable the code component
     */
    const CONF_CODE_ENABLE = 'codeEnable';


    /**
     * The tag of the ui component
     */
    const CODE_TAG = "code";
    const FILE_PATH_KEY = "file-path";


    function getType()
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
    function getPType()
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
    function getAllowedTypes()
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


    function connectTo($mode)
    {

        if ($this->getConf(self::CONF_CODE_ENABLE)) {
            $pattern = XmlTagProcessing::getContainerTagPattern(self::CODE_TAG);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }


    function postConnect()
    {
        if ($this->getConf(self::CONF_CODE_ENABLE)) {
            $this->Lexer->addExitPattern('</' . self::CODE_TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
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

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $tagAttributes = PluginUtility::getQualifiedTagAttributes($match, true, self::FILE_PATH_KEY, [], true);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes
                );

            case DOKU_LEXER_UNMATCHED :

                $data = PluginUtility::handleAndReturnUnmatchedData(self::CODE_TAG, $match, $handler);
                /**
                 * Attribute are send for the
                 * export of code functionality
                 * and display = none
                 */
                $callStack = CallStack::createFromHandler($handler);
                $parentTag = $callStack->moveToParent();
                $tagAttributes = $parentTag->getAttributes();
                $data[PluginUtility::ATTRIBUTES] = $tagAttributes;
                return $data;


            case DOKU_LEXER_EXIT :
                /**
                 * Tag Attributes are passed
                 * because it's possible to not display a code with the display attributes = none
                 */
                $callStack = CallStack::createFromHandler($handler);
                Dimension::addScrollToggleOnClickIfNoControl($callStack);

                $callStack->moveToEnd();
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
                );


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
    function render($format, Doku_Renderer $renderer, $data)
    {


        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data [PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::CODE_TAG);
                    Prism::htmlEnter($renderer, $this, $attributes);
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $display = $attributes->getValue("display");
                    if ($display != "none") {
                        // Delete the eol at the beginning and end
                        // otherwise we get a big block
                        $payload = trim($data[PluginUtility::PAYLOAD], "\n\r");
                        $renderer->doc .= Html::encode($payload);
                    }
                    break;

                case DOKU_LEXER_EXIT :
                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    Prism::htmlExit($renderer, $attributes);
                    break;

            }
            return true;
        } else if ($format == 'code') {

            /**
             * The renderer to download the code
             * @var Doku_Renderer_code $renderer
             */
            $state = $data [PluginUtility::STATE];
            if ($state == DOKU_LEXER_UNMATCHED) {

                $attributes = $data[PluginUtility::ATTRIBUTES];
                $text = $data[PluginUtility::PAYLOAD];
                $filename = $attributes[self::FILE_PATH_KEY];
                $language = strtolower($attributes["type"]);
                $renderer->code($text, $language, $filename);

            }
        }

        // unsupported $mode
        return false;

    }


}

