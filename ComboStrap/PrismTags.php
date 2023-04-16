<?php

namespace ComboStrap;

/**
 * The tag that wraps code or output
 *
 * Should output the `samp` tag ?
 * https://getbootstrap.com/docs/5.0/content/reboot/#sample-output
 */
class PrismTags
{

    /**
     * The tag of the ui component
     */
    public const CONSOLE_TAG = "console";
    /**
     * Enable or disable the file component
     */
    public const CONF_FILE_ENABLE = 'fileEnable';
    /**
     * The tag of the ui component
     */
    public const FILE_TAG = "file";

    public static function handleExit(\Doku_Handler $handler): array
    {
        /**
         * Tag Attributes are passed
         * because it's possible to not display a code with the display attributes = none
         */
        $callStack = CallStack::createFromHandler($handler);
        Dimension::addScrollToggleOnClickIfNoControl($callStack);

        $callStack->moveToEnd();
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        return array(PluginUtility::ATTRIBUTES => $openingTag->getAttributes());
    }

    public static function processEnterXhtml(TagAttributes $attributes, \DokuWiki_Syntax_Plugin $plugin, \Doku_Renderer_xhtml $renderer)
    {
        Prism::htmlEnter($renderer, $plugin, $attributes);
    }

    public static function processExitXhtml(TagAttributes $attributes, \Doku_Renderer_xhtml $renderer)
    {
        Prism::htmlExit($renderer, $attributes);
    }
}
