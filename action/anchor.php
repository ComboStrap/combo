<?php

use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\SnippetManager;
use ComboStrap\TplUtility;
use dokuwiki\Cache\CacheRenderer;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_anchor extends DokuWiki_Action_Plugin
{
    const ANCHOR = "anchor";


    /**
     * @var bool - to trace if the header output was called
     */
    private $headerOutputWasCalled = false;

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'anchor', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function anchor($event)
    {


        PluginUtility::getSnippetManager()->addHeadTagsOnce(self::ANCHOR,
            array("script" => [
                array("src" => "https://cdn.jsdelivr.net/npm/anchor-js/anchor.min.js")
            ]
            ));

        PluginUtility::getSnippetManager()->addJavascriptSnippetIfNeeded(self::ANCHOR);


    }


}
