<?php

namespace ComboStrap;

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



    const EXPORT_PREFIX = "export_";
    const CANONICAL = "url_rewrite";



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
        $rewrite = Site::getUrlRewrite();
        try {
            $path = $url->getPath();
        } catch (ExceptionNotFound $e) {
            // no path, no rewrite
            return;
        }
        switch ($rewrite) {
            case self::WEB_SERVER_REWRITE:

                switch ($path) {
                    case UrlEndpoint::LIB_EXE_FETCH_PHP:
                        try {
                            $id = $url->getQueryPropertyValueAndRemoveIfPresent(FetcherRawLocalPath::$MEDIA_QUERY_PARAMETER);
                        } catch (ExceptionNotFound $e) {
                            LogUtility::internalError("The media query should be present for a fetch. No Url rewrite could be done.");
                            return;
                        }
                        $idPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $id);
                        $url->setPath("/_media/$idPath");
                        break;
                    case UrlEndpoint::LIB_EXE_DETAIL_PHP:
                        try {
                            $id = $url->getQueryPropertyValueAndRemoveIfPresent(FetcherRawLocalPath::$MEDIA_QUERY_PARAMETER);
                        } catch (ExceptionNotFound $e) {
                            LogUtility::internalError("The media query should be present for a fetch. No Url rewrite could be done.");
                            return;
                        }
                        $idPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $id);
                        $url->setPath("/_detail/$idPath");
                        break;
                    case UrlEndpoint::DOKU_PHP:
                        try {
                            $do = $url->getQueryPropertyValueAndRemoveIfPresent("do");
                            if (strpos($do, self::EXPORT_PREFIX) === 0) {
                                $exportFormat = substr($do, strlen(self::EXPORT_PREFIX));
                                $id = $url->getQueryPropertyValueAndRemoveIfPresent(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE);
                                $idPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $id);
                                $url->setPath("_export/$exportFormat/$idPath");
                                return;
                            }
                        } catch (ExceptionNotFound $e) {
                            // no do
                        }
                        try {
                            $id = $url->getQueryPropertyValueAndRemoveIfPresent(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE);
                            $idPath = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $id);
                            $url->setPath("/$idPath");
                            return;
                        } catch (ExceptionNotFound $e) {
                            LogUtility::internalError("The id should be present for a doku script. No Url rewrite could be done.");
                            // no id
                        }

                }
                break;
            case self::VALUE_DOKU_REWRITE:
                if ($path === UrlEndpoint::DOKU_PHP) {
                    try {
                        $id = $url->getQueryPropertyValueAndRemoveIfPresent(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE);
                        $url->setPath("$path/$id");
                    } catch (ExceptionNotFound $e) {
                        LogUtility::internalError("The id should be present for a doku script. No Dokuwiki Url rewrite could be done.",self::CANONICAL);
                    }
                }
                break;
            case self::NO_REWRITE:
            default:
                break;
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

}
