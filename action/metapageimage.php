<?php


use ComboStrap\FileSystems;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MarkupPath;
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
     * saved in {@link \ComboStrap\Metadata}
     * if the frontmatter is not used
     *
     * @param $event
     */
    function metadataPageImages($event)
    {
        $dokuwikiId = $event->data["page"];
        $page = MarkupPath::createPageFromId($dokuwikiId);
        $pageImagesMeta = PageImages::createForPage($page);
        $pageImages = $pageImagesMeta->getValueAsPageImages();
        if ($pageImages === null) {
            return;
        }
        foreach ($pageImages as $pageImage) {
            /**
             * {@link Doku_Renderer_metadata::_recordMediaUsage()}
             */
            $dokuPath = $pageImage->getImagePath();
            $event->data[MetadataDokuWikiStore::CURRENT_METADATA]['relation']['media'][$dokuPath->getWikiId()] = FileSystems::exists($dokuPath);
        }


    }

}
