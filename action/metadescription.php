<?php

use ComboStrap\LogUtility;
use ComboStrap\Metadata;
use ComboStrap\Page;


/**
 * Take the metadata description
 *
 * To known more about description and [[docs:seo:seo|search engine optimization]], see:
 * [[https://developer.mozilla.org/en-US/docs/Learn/HTML/Introduction_to_HTML/The_head_metadata_in_HTML#Active_learning_The_descriptions_use_in_search_engines|Active learning: The description's use in search engines]].
 * [[https://developers.google.com/search/docs/beginner/seo-starter-guide#use-the-description-meta-tag|Description section of the Google SEO Starter guide]]
 */
class action_plugin_combo_metadescription extends DokuWiki_Action_Plugin
{

    const DESCRIPTION_META_KEY = 'description';
    const FACEBOOK_DESCRIPTION_PROPERTY = 'og:description';

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'description_modification', array());
    }

    /**
     * Add a meta-data description
     * @param $event
     * @param $param
     */
    function description_modification(&$event, $param)
    {

        global $ID;
        if (empty($ID)) {
            return;  // Admin call for instance
        }

        /**
         * Description
         * https://www.dokuwiki.org/devel:metadata
         */
        $page = Page::createPageFromId($ID);

        $description = $page->getDescriptionOrElseDokuWiki();
        if (empty($description)) {
            $this->sendDestInfo($ID);
            return;
        }

        // Add it to the meta
        Metadata::upsertMetaOnUniqueAttribute(
            $event->data['meta'],
            "name",
            [
                "name" => self::DESCRIPTION_META_KEY,
                "content" => $description
            ]
        );
        Metadata::upsertMetaOnUniqueAttribute(
            $event->data['meta'],
            "property",
            [
                "property" => self::FACEBOOK_DESCRIPTION_PROPERTY,
                "content" => $description
            ]
        );



    }

    /**
     * Just send a test info
     * @param $ID
     */
    public function sendDestInfo($ID)
    {
        if (defined('DOKU_UNITTEST')) {
            // When you make a admin test call, the page ID = start and there is no meta
            // When there is only an icon, there is also no meta
            global $INPUT;
            $showActions = ["show", ""]; // Empty for the test
            if (in_array($INPUT->str("do"), $showActions)) {
                LogUtility::msg("Page ($ID): The description should never be null when rendering the page", LogUtility::LVL_MSG_INFO);
            }
        }
    }


}
