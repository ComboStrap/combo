<?php

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * Can we use the parser cache
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{

    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'cacheUsed', array());

    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     * The path are initialized in {@link init_paths}
     */
    function cacheuUsed(Doku_Event $event, $params)
    {

    }


}
