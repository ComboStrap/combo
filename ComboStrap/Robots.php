<?php

namespace ComboStrap;

use ComboStrap\Web\Url;

class Robots
{

    const INDEX_DELAY = 'indexdelay';

    /**
     * @throws ExceptionNotEnabled - if the page can be indexed
     */
    public static function canBeIndexedAndGetFollowValue(MarkupPath $page, ExecutionContext $executionContext): string
    {

        $action = $executionContext->getExecutingAction();
        if ($action !== ExecutionContext::SHOW_ACTION) {
            return "nofollow";
        }

        /**
         * No indexing for slot page
         */
        if ($page->isSlot()) {
            return "follow";
        }

        /**
         * Resolution of the Google Search Console Issue
         * `Alternative page with proper canonical tag`
         * when Google crawls URl with functional Query String such as
         *
         * https://datacadamia.com/lang/java/comment?redirectId=java:comment&redirectOrigin=canonical
         * https://datacadamia.com/os/windows/pathext?referer=https://gerardnico.com/os/windows/pathext
         *
         * TODO: Ultimately, we should use a script that returns only the good url
         *   doing a redirect with a query to the resource is not Search Engine friendly
         */
        $queryProperties = Url::createFromGetOrPostGlobalVariable()->getQueryProperties();
        foreach ($queryProperties as $key => $value) {
            if ($key !== DokuwikiId::DOKUWIKI_ID_ATTRIBUTE) {
                // follow but no index as we return a value
                return "follow";
            }
        }

        if ($page->isLowQualityPage() && LowQualityPage::isProtectionEnabled()) {
            if (LowQualityPage::getLowQualityProtectionMode() !== PageProtection::CONF_VALUE_ACL) {
                return "follow";
            }
        }
        if ($page->isLatePublication() && PagePublicationDate::isLatePublicationProtectionEnabled()) {
            if (PagePublicationDate::getLatePublicationProtectionMode() == PageProtection::CONF_VALUE_ACL) {
                return "nofollow";
            } else {
                return "follow";
            }
        }
        throw new ExceptionNotEnabled();
    }
}
