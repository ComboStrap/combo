<?php


namespace ComboStrap;


class Mermaid
{


    const CANONICAL = "mermaid";
    public const CLASS_NAME = "mermaid";

    public static function addSnippet()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetId = \syntax_plugin_combo_mermaid::TAG;
        $snippetManager->attachInternalJavascriptForSlot($snippetId);
        $snippetManager->attachJavascriptLibraryForSlot(
            $snippetId,
            "https://cdn.jsdelivr.net/npm/mermaid@8.12.1/dist/mermaid.min.js",
            "sha256-51Oz+q3qIYwzBL0k7JLyk158Ye4XqprPU0/9DUcZMQQ="
        );
    }

    /**
     * The enter tag
     * @param $callStackAttributes
     * @return string
     */
    public static function enter($callStackAttributes): string
    {
        /**
         * This code is replaced at runtime by the diagram
         */
        $tagAttributes = TagAttributes::createFromCallStackArray($callStackAttributes);
        $tagAttributes->addClassName(Mermaid::CLASS_NAME);
        return $tagAttributes->toHtmlEnterTag("div");
    }

    /**
     * The closing tag
     * @return string
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

    public static function render($data, &$renderer)
    {
        $state = $data [PluginUtility::STATE];
        switch ($state) {
            case DOKU_LEXER_ENTER :
                Mermaid::addSnippet();
                $renderer->doc .= Mermaid::enter($data[PluginUtility::ATTRIBUTES]);
                break;

            case DOKU_LEXER_UNMATCHED :

                $renderer->doc .= Mermaid::sanitize($data[PluginUtility::PAYLOAD]);
                break;

            case DOKU_LEXER_EXIT :
                $renderer->doc .= Mermaid::close();
                break;

        }
    }

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
                return array(
                    PluginUtility::STATE => $state
                );


        }
        return array();
    }


}
