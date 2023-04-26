<?php

namespace ComboStrap\Web;

use ComboStrap\DokuWikiId;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FetcherRawLocalPath;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\MediaMarkup;
use ComboStrap\PageUrlPath;
use ComboStrap\Site;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;
use ComboStrap\WikiPath;

/**
 * Dokuwiki Rewrite
 */
class UrlRewrite
{

    public const CONF_KEY = 'userewrite';

    public const NO_REWRITE_DOKU_VALUE = 0;
    public const NO_REWRITE = "no_rewrite";
    public const WEB_SERVER_REWRITE_DOKU_VALUE = 1;
    public const WEB_SERVER_REWRITE = "web_server";
    public const DOKU_REWRITE_DOKU_VALUE = 2;
    /**
     * Doku Rewrite is value 2
     * https://www.dokuwiki.org/rewrite#further_details_for_the_technically_savvy
     */
    public const VALUE_DOKU_REWRITE = "doku_rewrite";


    const EXPORT_DO_PREFIX = "export_";
    const CANONICAL = "url_rewrite";
    const MEDIA_PREFIX = "/_media";
    const EXPORT_PATH_PREFIX = "/_export";


    /**
     * Apply all rewrite URL logic (from relative to absolute
     * passing by web server url rewrite)
     *
     * Note that an URL may already have been rewritten
     *
     */
    public static function rewrite(Url $url)
    {

        try {
            $scheme = $url->getScheme();
        } catch (ExceptionNotFound $e) {
            /**
             * we don't set, we just tell that that this is a http scheme
             * the conditional {@link Url::toAbsoluteUrlString()}
             * will set it
             */
            $scheme = "http";
        }
        switch ($scheme) {
            case "https":
            case "http":
                self::pathRewrite($url);
                self::baseRewrite($url);
                if (Site::shouldEndpointUrlBeAbsolute()) {
                    $url->toAbsoluteUrl();
                }
                break;
        }


    }

    /**
     * Rewrite the path
     *
     * Doc: https://www.dokuwiki.org/rewrite
     * https://www.dokuwiki.org/config:userewrite
     * @param Url $url
     * @return void
     */
    private static function pathRewrite(Url $url)
    {

        try {
            $path = $url->getPath();
        } catch (ExceptionNotFound $e) {
            // no path, no rewrite
            return;
        }

        $rewrite = Site::getUrlRewrite();
        switch ($path) {
            case UrlEndpoint::LIB_EXE_FETCH_PHP:
                if ($rewrite !== self::WEB_SERVER_REWRITE) {
                    return;
                }
                try {
                    $dokuwikiId = $url->getQueryPropertyValueAndRemoveIfPresent(MediaMarkup::$MEDIA_QUERY_PARAMETER);
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The media query should be present for a fetch. No Url rewrite could be done.");
                    return;
                }
                $webUrlPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $dokuwikiId);
                $url->setPath(self::MEDIA_PREFIX . "/$webUrlPath");
                return;
            case UrlEndpoint::LIB_EXE_DETAIL_PHP:
                if ($rewrite !== self::WEB_SERVER_REWRITE) {
                    return;
                }
                try {
                    $dokuwikiId = $url->getQueryPropertyValueAndRemoveIfPresent(MediaMarkup::$MEDIA_QUERY_PARAMETER);
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The media query should be present for a detail page fetch. No Url rewrite could be done.");
                    return;
                }
                $webUrlPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $dokuwikiId);
                $url->setPath("/_detail/$webUrlPath");
                return;
            case UrlEndpoint::DOKU_PHP:
                try {
                    $dokuwikiId = $url->getQueryPropertyValueAndRemoveIfPresent(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE);
                } catch (ExceptionNotFound $e) {
                    // no id (case of action such as login, ...)
                    return;
                }

                /**
                 * Permanent Id Rewrite
                 * The page url path will return the original dokuwiki id
                 * if there is no configuration
                 */
                $urlId = PageUrlPath::createForPage(MarkupPath::createMarkupFromId($dokuwikiId))->getValueOrDefaultAsWikiId();

                /**
                 * Rewrite Processing
                 */
                switch ($rewrite) {
                    case self::WEB_SERVER_REWRITE:
                        try {
                            $do = $url->getQueryPropertyValueAndRemoveIfPresent("do");
                            if (strpos($do, self::EXPORT_DO_PREFIX) === 0) {
                                $exportFormat = substr($do, strlen(self::EXPORT_DO_PREFIX));
                                $webUrlPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $urlId);
                                $url->setPath(self::EXPORT_PATH_PREFIX . "/$exportFormat/$webUrlPath");
                                return;
                            }
                        } catch (ExceptionNotFound $e) {
                            // no do
                        }
                        $webUrlPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $urlId);
                        $url->setPath($webUrlPath);
                        return;
                    case self::VALUE_DOKU_REWRITE:
                        $url->setPath("$path/$urlId");
                        return;
                    default:
                        $url->setQueryParameter(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $urlId);
                        return;
                }

        }

    }

    private
    static function baseRewrite(Url $url)
    {
        try {
            $urlPathBaseDir = Site::getUrlPathBaseDir();
        } catch (ExceptionNotFound $e) {
            // ok, no base dir
            return;
        }
        try {
            $path = $url->getPath();
        } catch (ExceptionNotFound $e) {
            return;
        }
        if (strpos($path, $urlPathBaseDir) === 0) {
            /**
             * The base dir is already present
             */
            return;
        }
        if ($urlPathBaseDir[strlen($urlPathBaseDir) - 1] === "/") {
            $url->setPath("$urlPathBaseDir$path");
        } else {
            $url->setPath("$urlPathBaseDir/$path");
        }

    }

    public static function sendErrorMessage()
    {
        $rewriteOption2 = "https://www.dokuwiki.org/rewrite#option_2dokuwiki";
        $rewriteOption1 = "https://www.dokuwiki.org/rewrite#option_1web_server";
        $hrefPermanentFunctionality = "https://combostrap.com/page/canonical-url-4kxbb9fd#permanent";
        $hrefNiceUrl = "https://combostrap.com/admin/nice-url-noln5keo";
        LogUtility::error("Combostrap does not support the <a href=\"$rewriteOption2\">Url Dokuwiki Rewriting (Option 2)</a> because of the <a href=\"$hrefPermanentFunctionality\"> permanent Url functionality</a>. You should disable it and use the <a href=\"$rewriteOption1\">Web Server Option (Option 1)</a> if you want <a href=\"$hrefNiceUrl\">nice URL</a>.", self::CANONICAL);
    }

}
