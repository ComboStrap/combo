<?php

use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\Page;
use ComboStrap\Metadata;
use ComboStrap\PageImages;
use ComboStrap\PluginUtility;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Handle the move of a image
 */
class action_plugin_combo_imgmove extends DokuWiki_Action_Plugin
{
    const CANONICAL = "move";

    /**
     * As explained https://www.dokuwiki.org/plugin:move
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PLUGIN_MOVE_HANDLERS_REGISTER', 'BEFORE', $this, 'handle_move', array());


        $controller->register_hook('PLUGIN_MOVE_MEDIA_RENAME', 'AFTER', $this, 'pageImageUpdate', array());
    }

    /**
     * Update the metadatas
     * @param Doku_Event $event
     * @param $params
     */
    function pageImageUpdate(Doku_Event $event, $params)
    {

        $affectedPagesId = $event->data["affected_pages"];
        $sourceImageId = $event->data["src_id"];
        $targetImageId = $event->data["dst_id"];
        foreach ($affectedPagesId as $affectedPageId) {
            $affectedPage = Page::createPageFromId($affectedPageId);
            $store = MetadataDokuWikiStore::createForPage($affectedPage);
            $affectedPage->setReadStore($store);
            $pageImages = PageImages::createForPage($affectedPage);
            $removedPageImage = null;

            $removedPageImage = $pageImages->removeIfExists($sourceImageId);
            if ($removedPageImage === null) {
                // This is a move of an image in the markup
                continue;
            }
            try {
                $pageImages
                    ->addImage($targetImageId, $removedPageImage->getUsages())
                    ->persist();
            } catch (ExceptionCombo $e) {
                LogUtility::log2file($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
            }


        }

    }

    /**
     * Handle the move of a image
     * @param Doku_Event $event
     * @param $params
     */
    function handle_move(Doku_Event $event, $params)
    {
        /**
         * The handlers is the name of the component (ie refers to the {@link syntax_plugin_combo_media} handler)
         * and 'move_combo_img' to the below method
         */
        $event->data['handlers'][syntax_plugin_combo_media::COMPONENT] = array($this, 'move_combo_img');
        $event->data['handlers'][syntax_plugin_combo_frontmatter::COMPONENT] = array($this, 'move_combo_frontmatter_img');

    }

    /**
     *
     * @param $match
     * @param $state
     * @param $pos
     * @param $plugin
     * @param helper_plugin_move_handler $handler
     */
    public function move_combo_img($match, $state, $pos, $plugin, helper_plugin_move_handler $handler)
    {
        /**
         * The original move method
         * is {@link helper_plugin_move_handler::media()}
         * Rewrite the media links match
         * from {@link syntax_plugin_combo_media}
         */
        $handler->media($match, $state, $pos);

    }

    /**
     *
     * @param $match
     * @param $state
     * @param $pos
     * @param $plugin
     * @param helper_plugin_move_handler $handler
     * @return string
     */
    public function move_combo_frontmatter_img($match, $state, $pos, $plugin, helper_plugin_move_handler $handler)
    {
        /**
         * The original move method
         * is {@link helper_plugin_move_handler::media()}
         */
        $page = Page::createPageFromId("move-fake-id");
        try {
            $metadataFrontmatterStore = MetadataFrontmatterStore::createFromFrontmatter($page, $match);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The frontmatter could not be loaded. " . $e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
            return $match;
        }
        $pageImagesObject = Metadata::createForPage($page)
            ->setStore($metadataFrontmatterStore);
        $images = $pageImagesObject->getValues();
        if ($images === null) {
            return $match;
        }

        try {

            foreach ($images as $image) {
                $path = $image->getImage()->getPath();
                if (!($path instanceof DokuPath)) {
                    continue;
                }
                $imageId = $path->toAbsolutePath()->toString();
                $before = $imageId;
                $this->moveImage($imageId, $handler);
                if ($before != $imageId) {
                    $pageImagesObject->removeIfExists($before);
                    $pageImagesObject->addImage($imageId, $image->getUsages());
                }
            }

            $pageImagesObject->sendToStore();

        } catch (ExceptionCombo $e) {
            // Could not resolve the image, image does not exist, ... return the data without modification
            if (PluginUtility::isDevOrTest()) {
                throw new ExceptionComboRuntime($e->getMessage(), $e->getCanonical(), 0, $e);
            } else {
                LogUtility::log2file($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
            }
            return $match;
        }

        /**
         * All good,
         * We don't modify the file system metadata for the page
         * because the handler does not give it unfortunately
         */
        return $metadataFrontmatterStore->toFrontmatterString($page);

    }

    /**
     * Move a single image and update the JSon
     * @param $relativeOrAbsoluteWikiId
     * @param helper_plugin_move_handler $handler
     * @throws ExceptionCombo on bad argument
     */
    private function moveImage(&$relativeOrAbsoluteWikiId, helper_plugin_move_handler $handler)
    {
        try {
            $newId = $handler->resolveMoves($relativeOrAbsoluteWikiId, "media");
            $relativeOrAbsoluteWikiId = DokuPath::IdToAbsolutePath($newId);
        } catch (Exception $e) {
            throw new ExceptionCombo("A move error has occurred while trying to move the image ($relativeOrAbsoluteWikiId). The target resolution function send the following error message: " . $e->getMessage(), self::CANONICAL);
        }
    }


}
