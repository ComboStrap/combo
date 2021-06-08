<?php

use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 * Add the heading numbering snippet
 */
class action_plugin_combo_headingnumbering extends DokuWiki_Action_Plugin
{

    const SNIPPET_ID = "heading-numbering";


    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_heading_numbering', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function _heading_numbering($event)
    {

        PluginUtility::getSnippetManager()->upsertCssSnippetForRequest(
            self::SNIPPET_ID
        );

    }


}
