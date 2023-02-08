<?php

use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;
use ComboStrap\Metadata;
use ComboStrap\MarkupPath;
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
     * @throws ExceptionNotFound
     */
    function meta_keywords(&$event, $param)
    {


        try {
            $page = action_plugin_combo_metacanonical::getContextPageForHeadHtmlMeta();
        } catch (ExceptionNotFound $e) {
            return;
        }

        try {
            $keywords = $page->getKeywordsOrDefault();
        } catch (ExceptionNotFound $e) {
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
