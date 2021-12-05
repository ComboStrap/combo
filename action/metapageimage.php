<?php


use ComboStrap\Metadata;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageImages;

/**
 * Class action_plugin_combo_metapageimage
 *
 */
class action_plugin_combo_metapageimage
{


    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'metadataPageImages', array());


    }

    /**
     * Trick to advertise the image
     * saved in {@link \ComboStrap\PageImages}
     * if the frontmatter is not used
     *
     * @param $event
     */
    function metadataPageImages($event)
    {
        $dokuwikiId = $event->data["page"];
        $page = Page::createPageFromId($dokuwikiId);
        $pageImages = PageImages::createForPage($page);
        foreach ($pageImages->getAll() as $pageImage){
            /**
             * {@link Doku_Renderer_metadata::_recordMediaUsage()}
             */
            $dokuPath = $pageImage->getImage()->getPath();
            $event->data[MetadataDokuWikiStore::CURRENT_METADATA]['relation']['media'][$dokuPath->getDokuwikiId()] = $dokuPath->exists();
        }


    }

}