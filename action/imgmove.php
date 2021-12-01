<?php

use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageImages;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');
require_once(__DIR__ . '/../ComboStrap/LinkUtility.php');

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
         *
         */
        $jsonArray = syntax_plugin_combo_frontmatter::frontMatterMatchToAssociativeArray($match);
        if ($jsonArray === null) {
            return $match;
        } else {

            if (!isset($jsonArray[PageImages::IMAGE_META_PROPERTY])) {
                return $match;
            }

            try {
                $images = $jsonArray[PageImages::IMAGE_META_PROPERTY];

                /**
                 * The id of the page is private in the handler, why not ?
                 * fuck
                 * We try what we can
                 */
                global $ID;
                $id = $ID;
                if ($ID === null) {

                    $id = "fake_id_for_move";
                }
                $fakePage = Page::createPageFromId($id);
                $oldPagesImages = PageImages::createFromPage($fakePage)
                    ->buildFromPersistentFormat($images);
                $newPagesImages = PageImages::createFromPage($fakePage);

                foreach ($oldPagesImages->getAll() as $oldPageImage) {
                    $imagePath = $oldPageImage->getImage()->getDokuPath()->getAbsolutePath();
                    $this->moveImage($imagePath, $handler);
                    $newPagesImages->addImage($imagePath, $oldPageImage->getUsages());
                }
                $jsonArray[PageImages::IMAGE_META_PROPERTY] = $newPagesImages->toPersistentValue();


            } catch (ExceptionCombo $e) {
                // Could not resolve the image, image does not exist, ... return the data without modification
                LogUtility::log2file($e->getMessage(),LogUtility::LVL_MSG_ERROR,$e->getCanonical());
                return $match;
            }

            $jsonEncode = \ComboStrap\Json::createFromArray($jsonArray)->toFrontMatterFormat();
            $frontmatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
            $frontmatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;

            /**
             * All good,
             * We don't modify the metadata for the page
             * because the handler does not give it unfortunately
             */

            /**
             * Return the match modified
             */
            return <<<EOF
$frontmatterStartTag
$jsonEncode
$frontmatterEndTag
EOF;

        }

    }

    /**
     * Move a single image and update the JSon
     * @param $value
     * @param helper_plugin_move_handler $handler
     * @throws ExceptionCombo on bad argument
     */
    private function moveImage(&$value, $handler)
    {
        try {
            $newId = $handler->resolveMoves($value, "media");
            $value = DokuPath::IdToAbsolutePath($newId);
        } catch (Exception $e) {
            throw new ExceptionCombo("A move error has occurred while trying to move the image ($value). The target resolution function send the following error message: " . $e->getMessage(), self::CANONICAL);
        }
    }


}
