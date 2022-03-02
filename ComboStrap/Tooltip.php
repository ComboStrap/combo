<?php


namespace ComboStrap;


use syntax_plugin_combo_button;
use syntax_plugin_combo_link;
use syntax_plugin_combo_tooltip;

class Tooltip
{


    public const TOOLTIP_ATTRIBUTE = "tooltip";
    public const CALLSTACK = "callstack";
    public const POSITION_ATTRIBUTE = "position";

    public static function processTooltip(TagAttributes &$tagAttributes)
    {

        $tooltip = $tagAttributes->getValueAndRemove(self::TOOLTIP_ATTRIBUTE);
        if ($tooltip === null) {
            return;
        }

        if(!is_array($tooltip)){
            LogUtility::msg("The tooltip value ($tooltip) is not an array.");
            return;
        }

        /**
         * Tooltip
         */
        $dataAttributeNamespace = Bootstrap::getDataNamespace();


        /**
         * Old tooltip syntax
         */
        $title = $tooltip[syntax_plugin_combo_tooltip::TEXT_ATTRIBUTE];
        if ($title === null) {

            $callStack = $tooltip[Tooltip::CALLSTACK];
            if ($callStack !== null) {
                try {
                    $title = PluginUtility::renderInstructionsToXhtml($callStack);
                } catch (ExceptionCombo $e) {
                    $title = LogUtility::wrapInRedForHtml("Error while rendering the tooltip. Error: {$e->getMessage()}");
                }

                /**
                 * New Syntax
                 * (The new syntax add the attributes to the previous element
                 */
                $tagAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-html", "true");

            }
        }




        if (empty($title)) {
            $title = LogUtility::wrapInRedForHtml("The tooltip is empty");
        }
        $tagAttributes->addOutputAttributeValue("title", $title);

        /**
         * Snippet
         */
        Tooltip::addToolTipSnippetIfNeeded();
        $tagAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-toggle", "tooltip");

        /**
         * Position
         */
        $position = $tooltip[Tooltip::POSITION_ATTRIBUTE];
        if ($position === null) {
            $position = "top";
        }
        $tagAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-placement", "${position}");


        /**
         * Keyboard user and assistive technology users
         * If not button or link (ie span), add tabindex to make the element focusable
         * in order to see the tooltip
         * Not sure, if this is a good idea
         *
         * Arbitrary HTML elements (such as <span>s) can be made focusable by adding the tabindex="0" attribute
         */
        $logicalTag = $tagAttributes->getLogicalTag();
        if (!in_array($logicalTag, [syntax_plugin_combo_link::TAG, syntax_plugin_combo_button::TAG])) {
            $tagAttributes->addOutputAttributeValue("tabindex", "0");
        }


    }

    /**
     * tooltip is used also in page protection
     */
    public
    static function addToolTipSnippetIfNeeded()
    {
        PluginUtility::getSnippetManager()->attachInternalJavascriptForSlot(syntax_plugin_combo_tooltip::TAG);
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(syntax_plugin_combo_tooltip::TAG);
    }
}
