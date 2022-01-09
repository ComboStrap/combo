<?php


use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;

/**
 * Class action_plugin_combo_metasync
 * Sync the meta of Dokuwiki with the {@link MetadataDokuWikiStore}
 */
class action_plugin_combo_metasync
{


    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         *
         * Found in {@link p_render_metadata()}
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'handleSync', array());



    }

    function handleSync(&$event, $param)
    {

        $id = $event->data["page"];
        $page = Page::createPageFromId($id);
        $store = MetadataDokuWikiStore::getOrCreateFromResource($page);
        $result = $event->result;
        $store->setData($result);

    }


}
