<?php

use ComboStrap\DokuPath;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/LinkUtility.php');

/**
 * Handle the move of a image
 */
class action_plugin_combo_imgmove extends DokuWiki_Action_Plugin
{

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
         *
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
        $jsonArray = syntax_plugin_combo_frontmatter::FrontMatterMatchToAssociativeArray($match);
        if ($jsonArray === null) {
            return $match;
        } else {
            if (!isset($jsonArray[Page::IMAGE_META_PROPERTY])) {
                return $match;
            }
            $image = $jsonArray[Page::IMAGE_META_PROPERTY];
            try {
                $newId = $handler->resolveMoves($image, "media");
            } catch (Exception $e) {
                LogUtility::msg("A move error has occurred while trying to move the image ($image). The target resolution function send the following error message: " . $e->getMessage(), LogUtility::LVL_MSG_ERROR);
                return $match;
            }

            $newPath = DokuPath::IdToAbsolutePath($newId);
            $jsonArray[Page::IMAGE_META_PROPERTY] = $newPath;
            $jsonEncode = json_encode($jsonArray, JSON_PRETTY_PRINT);
            if ($jsonEncode === false) {
                LogUtility::msg("A move error has occurred while trying to store the modified metadata as json (" . hsc(var_export($image, true)) . ")", LogUtility::LVL_MSG_ERROR);
                return $match;
            }
            $frontmatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
            $frontmatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
            $frontMatterAsString = <<<EOF
$frontmatterStartTag
$jsonEncode
$frontmatterEndTag
EOF;

            /**
             * All good,
             * modify the metadata
             */
            global $ID;
            if (isset($ID)) {
                p_set_metadata($ID, [Page::IMAGE_META_PROPERTY => $newPath]);
            }

            /**
             * Return the match modified
             */
            return $frontMatterAsString;

        }

    }


}
