<?php


namespace ComboStrap;


class Display
{

    public const DISPLAY = "display";
    public const DISPLAY_NONE_VALUE = "none";
    public const DISPLAY_NONE_IF_EMPTY_VALUE = "none-if-empty";

    public static function processDisplay(TagAttributes &$tagAttributes)
    {

        $display = $tagAttributes->getValueAndRemove(self::DISPLAY);
        if ($display !== null) {
            $value = strtolower($display);
            switch ($value) {
                case self::DISPLAY_NONE_VALUE:
                    $tagAttributes->addStyleDeclarationIfNotSet("display", "none");
                    return;
                case self::DISPLAY_NONE_IF_EMPTY_VALUE:
                    try {
                        $id = $tagAttributes->getId();
                    } catch (ExceptionNotFound $e) {
                        $id = $tagAttributes->getDefaultGeneratedId();
                        $tagAttributes->setId($id);
                    }
                    $css = "#$id:empty {  display: none; }";
                    ExecutionContext::getActualOrCreateFromEnv()
                        ->getSnippetSystem()
                        ->attachCssInternalStyleSheet("display-none-if-empty-$id", $css);
                    return;

            }
        }
    }

}
