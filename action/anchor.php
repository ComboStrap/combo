<?php


use ComboStrap\PluginUtility;
use ComboStrap\Site;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_anchor extends DokuWiki_Action_Plugin
{

    /**
     * To add hash tag to heading
     */
    const ANCHOR_LIBRARY_SNIPPET_ID = "anchor-library";
    /**
     * For styling on the anchor tag (ie a)
     */
    const ANCHOR_HTML_SNIPPET_ID = "anchor-branding";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        /**
         * DOKUWIKI_STARTED: Edit, preview, show mode
         *
         * See {@link \ComboStrap\SnippetManager::attachCssInternalStylesheetForRequest()}
         * for more explanation on the choice of the event
         */
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this, 'anchor', array());
    }

    /**
     *
     *
     * @param $event
     */
    function anchor($event)
    {

        /**
         * Anchor on id
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachJavascriptLibraryForRequest(
            self::ANCHOR_LIBRARY_SNIPPET_ID,
            "https://cdn.jsdelivr.net/npm/anchor-js@4.3.0/anchor.min.js",
            "sha256-LGOWMG4g6/zc0chji4hZP1d8RxR2bPvXMzl/7oPZqjs="
        );
        $snippetManager->attachJavascriptInternalInlineForRequest(self::ANCHOR_LIBRARY_SNIPPET_ID);

        /**
         * Default Link Color
         * Saturation and lightness comes from the
         * Note:
         *   * blue color of Bootstrap #0d6efd s: 98, l: 52
         *   * blue color of twitter #1d9bf0 s: 88, l: 53
         *   * reddit gray with s: 16, l : 31
         *   * the text is s: 11, l: 15
         * We choose the gray/tone rendering to be close to black
         * the color of the text
         */
        $primaryColor = Site::getPrimaryColor();
        if (Site::isBrandingColorInheritanceEnabled() && $primaryColor !== null) {

            $primaryColorText = Site::getPrimaryColorForText();
            $primaryColorHoverText = Site::getPrimaryColorTextHover();
            if ($primaryColorText !== null) {
                $aCss = <<<EOF
main a {
    color: {$primaryColorText->toRgbHex()};
}
main a:hover {
    color: {$primaryColorHoverText->toRgbHex()};
}
EOF;
    $snippetManager->attachCssInternalStylesheetForRequest(self::ANCHOR_HTML_SNIPPET_ID, $aCss);
}

        }


    }


}
