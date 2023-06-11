<?php


namespace ComboStrap\Tag;


use ComboStrap\CallStack;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Web\Sanitizer;
use ComboStrap\TagAttributes;

class MermaidTag
{


    const CANONICAL = "mermaid";
    public const CLASS_NAME = "mermaid";
    const LOGICAL_TAG = "mermaid";
    public const MARKUP_MERMAID = 'mermaid';
    public const MARKUP_SEQUENCE_DIAGRAM = 'sequence-diagram';
    const MARKUP_CONTENT_ATTRIBUTE = "mermaid-markup";
    const MERMAID_CODE = "mermaid";
    public const MARKUP_CLASS_DIAGRAM = 'class-diagram';
    public const MARKUP_FLOWCHART = 'flowchart';
    public const MARKUP_GANTT = 'gantt';
    public const MARKUP_ERD = 'erd';
    public const MARKUP_JOURNEY = 'journey';
    public const MARKUP_PIECHART = 'piechart';
    public const MARKUP_STATE_DIAGRAM = 'state-diagram';

    public static function addSnippet()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetId = self::CANONICAL;
        $snippetManager->attachJavascriptFromComponentId($snippetId);
        try {
            $snippetManager->attachRemoteJavascriptLibrary(
                $snippetId,
                "https://cdn.jsdelivr.net/npm/mermaid@10.2.3/dist/mermaid.min.js",
                "sha256-JFptYy4KzJ5OQP+Q9fubNf3cxpPPmZKqUOovyEONKrQ="
            );
        } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
            LogUtility::internalError("The url should be good", self::CANONICAL,$e);
        }
    }

    /**
     * The enter tag
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function renderEnter(TagAttributes $tagAttributes): string
    {

        $content = $tagAttributes->getValueAndRemoveIfPresent(self::MARKUP_CONTENT_ATTRIBUTE);

        $tagAttributes->addClassName(MermaidTag::CLASS_NAME);
        $html = $tagAttributes->toHtmlEnterTag("div");
        /**
         * The mermaid markup code is replaced at runtime by the diagram
         */
        MermaidTag::addSnippet();

        $html .= MermaidTag::sanitize($content);
        $html .= '</div>';
        return $html;

    }

    /**
     * The closing tag
     *
     * @return string
     * @deprecated
     */
    public static function close(): string
    {
        return "</div>";
    }

    /**
     * The content cannot be HTML escaped
     *
     * because
     * `->>` would become `→&gt;`
     * or  <br/> would not work
     *
     * There is a parameter
     * @param $content
     * @return mixed
     */
    public static function sanitize($content)
    {

        return Sanitizer::sanitize($content, " in a mermaid language", self::CANONICAL);

    }


    /**
     * @param $data
     * @param $renderer
     * @return void
     * @deprecated
     */
    public static function render($data, &$renderer)
    {
        $state = $data [PluginUtility::STATE];
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                $renderer->doc .= MermaidTag::renderEnter($tagAttributes);
                break;
            case DOKU_LEXER_UNMATCHED :
            case DOKU_LEXER_EXIT :
                break;

        }
    }

    public static function handleExit($handler){

        $callStack = CallStack::createFromHandler($handler);
        $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
        $contentCall = $callStack->next();
        $content = $contentCall->getCapturedContent();
        $openingCall->setAttribute(self::MARKUP_CONTENT_ATTRIBUTE, $content);
        $callStack->deleteAllCallsAfter($openingCall);

    }

    /**
     * @param $state
     * @param $match
     * @param $handler
     * @return array
     */
    public static function handle($state, $match, &$handler): array
    {
        switch ($state) {

            case DOKU_LEXER_ENTER :
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData("", $match, $handler);


            case DOKU_LEXER_EXIT :
                self::handleExit($handler);
                return array(
                    PluginUtility::STATE => $state
                );


        }
        return array();
    }


}
