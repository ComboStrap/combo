<?php


namespace ComboStrap;


class Mermaid
{

    public const CLASS_NAME = "mermaid";

    public static function addSnippet()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetId = \syntax_plugin_combo_mermaid::TAG;
        $snippetManager->attachJavascriptSnippetForBar($snippetId);
        $snippetManager->attachTagsForBar($snippetId)->setTags(
            array(
                "script" =>
                    [
                        array(
                            "src" => "https://cdn.jsdelivr.net/npm/mermaid@8.12.1/dist/mermaid.min.js",
                            "integrity" => "sha256-51Oz+q3qIYwzBL0k7JLyk158Ye4XqprPU0/9DUcZMQQ=",
                            "crossorigin" => "anonymous"
                        )
                    ],

            )
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
}
