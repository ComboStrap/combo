<?php

namespace ComboStrap;

class UrlEndpoint
{

    const NO_REWRITE = "no_rewrite";
    const WEB_SERVER_REWRITE = "web_server";
    const DOKU_REWRITE = "doku_rewrite";

    public static function createFetchUrl(): Url
    {

        return self::createEndPointUrl('lib/exe/fetch.php', '_media');


    }

    public static function createDetailUrl(): Url
    {
        return self::createEndPointUrl('lib/exe/detail.php', '_detail');
    }

    public static function createEndPointUrl($relativeNormalPath, $relativeRewritePath): Url
    {

        $rewrite = Site::getUrlRewrite();
        switch ($rewrite) {
            case self::WEB_SERVER_REWRITE:
                $path = $relativeRewritePath;
                break;
            case self::DOKU_REWRITE:
            case self::NO_REWRITE:
            default:
                $path = $relativeNormalPath;
                break;
        }
        try {
            $urlPathBaseDir = Site::getUrlPathBaseDir();
            $path = "$urlPathBaseDir/$path";
        } catch (ExceptionNotFound $e) {
            // ok
            $path = "/$path";
        }
        $url = Url::createEmpty()
            ->setPath($path);
        if (Site::shouldEndpointUrlBeAbsolute()) {
            $url->toAbsoluteUrl();
        }
        return $url;

    }

    public static function createComboStrapUrl(): Url
    {

        return Url::createEmpty()
            ->setScheme("https")
            ->setHost("combostrap.com");

    }

    public static function createSupportUrl(): Url
    {

        return self::createComboStrapUrl()
            ->setPath("support");

    }

    public static function createDokuUrl(): Url
    {

        return self::createEndPointUrl('doku.php', '');

    }


}
