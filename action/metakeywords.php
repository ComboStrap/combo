<?php

use ComboStrap\LogUtility;
use ComboStrap\Metadata;
use ComboStrap\Page;
use ComboStrap\PageKeywords;


/**
 *
 * https://developers.google.com/search/blog/2009/09/google-does-not-use-keywords-meta-tag
 */
class action_plugin_combo_metakeywords extends DokuWiki_Action_Plugin
{





    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_keywords', array());
    }

    /**
     * Add a key words description
     * @param $event
     * @param $param
     */
    function meta_keywords(&$event, $param)
    {

        global $ID;
        if (empty($ID)) {
            return;  // Admin call for instance
        }


        $page = Page::createPageFromRequestedPage();

        $keywords = $page->getKeywordsOrDefault();
        if ($keywords === null) {
            return;
        }

        Metadata::upsertMetaOnUniqueAttribute(
            $event->data['meta'],
            "name",
            [
                "name" => PageKeywords::PROPERTY_NAME,
                "content" => implode(",", $keywords)
            ]
        );


    }


}
