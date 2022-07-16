<?php

namespace ComboStrap;

/**
 * A class that helps get the header
 * of a page that should not have all
 * social meta tag
 */
class HtmlHeadTags
{

    public static function create(): HtmlHeadTags
    {
        return new HtmlHeadTags();
    }

    public function get(): string
    {
        if (Site::isStrapTemplate()) {

            /**
             * The strap header function
             */
            try {
                Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
                TplUtility::registerHeaderHandler();
            } catch (ExceptionCompile $e) {
                LogUtility::log2file("Error while registering the header handler on webcode ajax call. Error: {$e->getMessage()}");
            }

        }

        /**
         * Meta headers
         * To delete the not needed headers for an export
         * such as manifest, alternate, ...
         */
        global $EVENT_HANDLER;
        $EVENT_HANDLER->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'deleteNotNeededHeaders');
        return PageLayout::getHtmlHeadTags();
    }

    /**
     * Dynamically called in the previous function
     * to delete the head
     * @param $event
     */
    public function deleteNotNeededHeaders(&$event)
    {
        $data = &$event->data;

        foreach ($data as $tag => &$heads) {
            switch ($tag) {
                case "link":
                    $deletedRel = ["manifest", "search", "start", "alternate", "canonical"];
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['rel'])) {
                            $rel = $headAttributes['rel'];
                            if (in_array($rel, $deletedRel)) {
                                unset($heads[$id]);
                            }
                        }
                    }
                    break;
                case "meta":
                    $deletedMeta = ["robots", "og:url", "og:description", "description"];
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['name']) || isset($headAttributes['property'])) {
                            $rel = $headAttributes['name'];
                            if ($rel === null) {
                                $rel = $headAttributes['property'];
                            }
                            if (in_array($rel, $deletedMeta)) {
                                unset($heads[$id]);
                            }
                        }
                    }
                    break;
                case "script":
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['src'])) {
                            $src = $headAttributes['src'];
                            if (strpos($src, "lib/exe/js.php") !== false) {
                                unset($heads[$id]);
                            }
                        }
                    }
            }
        }
    }

}
